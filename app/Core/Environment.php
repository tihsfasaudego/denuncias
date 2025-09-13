<?php
/**
 * Classe para gerenciamento de variáveis de ambiente
 * Carrega e processa arquivos .env para configuração segura
 */
class Environment {
    private static $loaded = false;
    private static $variables = [];
    
    /**
     * Carrega as variáveis de ambiente do arquivo .env
     * 
     * @param string $path Caminho para o arquivo .env
     * @return bool True se carregado com sucesso
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return true;
        }
        
        if ($path === null) {
            $path = dirname(dirname(__DIR__)) . '/.env';
        }
        
        if (!file_exists($path)) {
            error_log("Arquivo .env não encontrado em: {$path}");
            return false;
        }
        
        try {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorar comentários
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Processar linha key=value
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remover aspas se existirem
                    if (strlen($value) > 1 && 
                        (($value[0] === '"' && $value[-1] === '"') || 
                         ($value[0] === "'" && $value[-1] === "'"))) {
                        $value = substr($value, 1, -1);
                    }
                    
                    // Armazenar na variável global $_ENV
                    $_ENV[$key] = $value;
                    self::$variables[$key] = $value;
                    
                    // Também disponibilizar via putenv para compatibilidade
                    putenv("{$key}={$value}");
                }
            }
            
            self::$loaded = true;
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao carregar .env: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uma variável de ambiente
     * 
     * @param string $key Nome da variável
     * @param mixed $default Valor padrão se não encontrada
     * @return mixed Valor da variável ou padrão
     */
    public static function get($key, $default = null) {
        // Tentar $_ENV primeiro
        if (isset($_ENV[$key])) {
            return self::convertType($_ENV[$key]);
        }
        
        // Tentar getenv como fallback
        $value = getenv($key);
        if ($value !== false) {
            return self::convertType($value);
        }
        
        // Tentar variáveis carregadas
        if (isset(self::$variables[$key])) {
            return self::convertType(self::$variables[$key]);
        }
        
        return $default;
    }
    
    /**
     * Converte strings para tipos apropriados
     * 
     * @param string $value Valor a ser convertido
     * @return mixed Valor convertido
     */
    private static function convertType($value) {
        if ($value === 'true' || $value === 'TRUE') {
            return true;
        }
        
        if ($value === 'false' || $value === 'FALSE') {
            return false;
        }
        
        if ($value === 'null' || $value === 'NULL') {
            return null;
        }
        
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Verifica se o ambiente é de desenvolvimento
     * 
     * @return bool True se é desenvolvimento
     */
    public static function isDevelopment() {
        return self::get('APP_ENV', 'production') === 'development';
    }
    
    /**
     * Verifica se o ambiente é de produção
     * 
     * @return bool True se é produção
     */
    public static function isProduction() {
        return self::get('APP_ENV', 'production') === 'production';
    }
    
    /**
     * Verifica se o debug está habilitado
     * 
     * @return bool True se debug habilitado
     */
    public static function isDebug() {
        return (bool)self::get('APP_DEBUG', false);
    }
    
    /**
     * Obtém todas as variáveis carregadas (para debug)
     * Só funciona em desenvolvimento
     * 
     * @return array Variáveis carregadas
     */
    public static function getAll() {
        if (!self::isDevelopment()) {
            return [];
        }
        
        return self::$variables;
    }
}
