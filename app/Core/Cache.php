<?php
/**
 * Sistema de cache flexível
 * Suporta Redis, Memcached e cache de arquivos
 */
class Cache {
    private static $instance = null;
    private $driver;
    private $redis = null;
    private $memcached = null;
    private $cacheDir;
    private $defaultTTL;
    
    private function __construct() {
        $this->driver = Environment::get('CACHE_DRIVER', 'file');
        $this->defaultTTL = Environment::get('CACHE_TTL', 3600);
        $this->cacheDir = BASE_PATH . '/storage/cache';
        
        $this->initializeDriver();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializa o driver de cache apropriado
     */
    private function initializeDriver() {
        switch ($this->driver) {
            case 'redis':
                $this->initializeRedis();
                break;
                
            case 'memcached':
                $this->initializeMemcached();
                break;
                
            case 'file':
            default:
                $this->initializeFileCache();
                break;
        }
    }
    
    /**
     * Inicializa Redis
     */
    private function initializeRedis() {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $host = Environment::get('REDIS_HOST', '127.0.0.1');
                $port = Environment::get('REDIS_PORT', 6379);
                $password = Environment::get('REDIS_PASSWORD', null);
                $database = Environment::get('REDIS_DATABASE', 0);
                
                $this->redis->connect($host, $port);
                
                if ($password) {
                    $this->redis->auth($password);
                }
                
                $this->redis->select($database);
                
                // Testar conexão
                $this->redis->ping();
                
                error_log("Cache Redis inicializado com sucesso");
            } catch (Exception $e) {
                error_log("Erro ao conectar Redis, usando cache de arquivo: " . $e->getMessage());
                $this->driver = 'file';
                $this->initializeFileCache();
            }
        } else {
            error_log("Extensão Redis não encontrada, usando cache de arquivo");
            $this->driver = 'file';
            $this->initializeFileCache();
        }
    }
    
    /**
     * Inicializa Memcached
     */
    private function initializeMemcached() {
        if (class_exists('Memcached')) {
            try {
                $this->memcached = new Memcached();
                $host = Environment::get('MEMCACHED_HOST', '127.0.0.1');
                $port = Environment::get('MEMCACHED_PORT', 11211);
                
                $this->memcached->addServer($host, $port);
                
                // Testar conexão
                $stats = $this->memcached->getStats();
                if (empty($stats)) {
                    throw new Exception("Não foi possível conectar ao Memcached");
                }
                
                error_log("Cache Memcached inicializado com sucesso");
            } catch (Exception $e) {
                error_log("Erro ao conectar Memcached, usando cache de arquivo: " . $e->getMessage());
                $this->driver = 'file';
                $this->initializeFileCache();
            }
        } else {
            error_log("Extensão Memcached não encontrada, usando cache de arquivo");
            $this->driver = 'file';
            $this->initializeFileCache();
        }
    }
    
    /**
     * Inicializa cache de arquivos
     */
    private function initializeFileCache() {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new Exception("Não foi possível criar diretório de cache: " . $this->cacheDir);
            }
        }
        
        // Criar arquivo .htaccess para segurança
        $htaccessPath = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Require all denied\n");
        }
        
        error_log("Cache de arquivo inicializado: " . $this->cacheDir);
    }
    
    /**
     * Armazena um valor no cache
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?: $this->defaultTTL;
        $key = $this->sanitizeKey($key);
        
        try {
            switch ($this->driver) {
                case 'redis':
                    if ($this->redis) {
                        return $this->redis->setex($key, $ttl, serialize($value));
                    }
                    break;
                    
                case 'memcached':
                    if ($this->memcached) {
                        return $this->memcached->set($key, $value, $ttl);
                    }
                    break;
                    
                case 'file':
                default:
                    return $this->setFileCache($key, $value, $ttl);
            }
        } catch (Exception $e) {
            error_log("Erro ao definir cache: " . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Recupera um valor do cache
     */
    public function get($key, $default = null) {
        $key = $this->sanitizeKey($key);
        
        try {
            switch ($this->driver) {
                case 'redis':
                    if ($this->redis) {
                        $value = $this->redis->get($key);
                        return $value !== false ? unserialize($value) : $default;
                    }
                    break;
                    
                case 'memcached':
                    if ($this->memcached) {
                        $value = $this->memcached->get($key);
                        return $this->memcached->getResultCode() === Memcached::RES_SUCCESS ? $value : $default;
                    }
                    break;
                    
                case 'file':
                default:
                    return $this->getFileCache($key, $default);
            }
        } catch (Exception $e) {
            error_log("Erro ao recuperar cache: " . $e->getMessage());
            return $default;
        }
        
        return $default;
    }
    
    /**
     * Remove um valor do cache
     */
    public function delete($key) {
        $key = $this->sanitizeKey($key);
        
        try {
            switch ($this->driver) {
                case 'redis':
                    if ($this->redis) {
                        return $this->redis->del($key) > 0;
                    }
                    break;
                    
                case 'memcached':
                    if ($this->memcached) {
                        return $this->memcached->delete($key);
                    }
                    break;
                    
                case 'file':
                default:
                    return $this->deleteFileCache($key);
            }
        } catch (Exception $e) {
            error_log("Erro ao deletar cache: " . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Limpa todo o cache
     */
    public function flush() {
        try {
            switch ($this->driver) {
                case 'redis':
                    if ($this->redis) {
                        return $this->redis->flushDB();
                    }
                    break;
                    
                case 'memcached':
                    if ($this->memcached) {
                        return $this->memcached->flush();
                    }
                    break;
                    
                case 'file':
                default:
                    return $this->flushFileCache();
            }
        } catch (Exception $e) {
            error_log("Erro ao limpar cache: " . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Busca ou executa callback se não existir
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Cache de arquivo - Set
     */
    private function setFileCache($key, $value, $ttl) {
        $filePath = $this->getCacheFilePath($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        
        return file_put_contents($filePath, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * Cache de arquivo - Get
     */
    private function getFileCache($key, $default) {
        $filePath = $this->getCacheFilePath($key);
        
        if (!file_exists($filePath)) {
            return $default;
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return $default;
        }
        
        $data = unserialize($content);
        if (!$data || !isset($data['expires_at'])) {
            return $default;
        }
        
        if (time() > $data['expires_at']) {
            unlink($filePath);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Cache de arquivo - Delete
     */
    private function deleteFileCache($key) {
        $filePath = $this->getCacheFilePath($key);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Cache de arquivo - Flush
     */
    private function flushFileCache() {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Gera caminho do arquivo de cache
     */
    private function getCacheFilePath($key) {
        return $this->cacheDir . '/' . $key . '.cache';
    }
    
    /**
     * Sanitiza a chave do cache
     */
    private function sanitizeKey($key) {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    }
    
    /**
     * Obtém estatísticas do cache
     */
    public function getStats() {
        switch ($this->driver) {
            case 'redis':
                if ($this->redis) {
                    return $this->redis->info();
                }
                break;
                
            case 'memcached':
                if ($this->memcached) {
                    return $this->memcached->getStats();
                }
                break;
                
            case 'file':
            default:
                $files = glob($this->cacheDir . '/*.cache');
                return [
                    'driver' => 'file',
                    'files' => count($files),
                    'size' => array_sum(array_map('filesize', $files))
                ];
        }
        
        return [];
    }
    
    /**
     * Incrementa um valor no cache
     */
    public function increment($key, $value = 1) {
        switch ($this->driver) {
            case 'redis':
                if ($this->redis) {
                    return $this->redis->incrBy($this->sanitizeKey($key), $value);
                }
                break;
                
            case 'memcached':
                if ($this->memcached) {
                    return $this->memcached->increment($this->sanitizeKey($key), $value);
                }
                break;
                
            case 'file':
            default:
                $current = (int)$this->get($key, 0);
                $new = $current + $value;
                $this->set($key, $new);
                return $new;
        }
        
        return false;
    }
    
    /**
     * Decrementa um valor no cache
     */
    public function decrement($key, $value = 1) {
        return $this->increment($key, -$value);
    }
}
