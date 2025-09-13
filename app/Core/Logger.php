<?php
/**
 * Sistema de logs estruturados
 * Suporta diferentes níveis, formatação JSON e rotação automática
 */
class Logger {
    private static $instance = null;
    private $logDir;
    private $context = [];
    
    // Níveis de log (PSR-3 compatible)
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    private $levels = [
        self::EMERGENCY => 0,
        self::ALERT => 1,
        self::CRITICAL => 2,
        self::ERROR => 3,
        self::WARNING => 4,
        self::NOTICE => 5,
        self::INFO => 6,
        self::DEBUG => 7
    ];
    
    private function __construct() {
        $this->logDir = BASE_PATH . '/storage/logs';
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Contexto global
        $this->context = [
            'environment' => Environment::get('APP_ENV', 'production'),
            'version' => Environment::get('APP_VERSION', '1.0.0'),
            'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'php_version' => PHP_VERSION
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log genérico
     */
    public function log($level, $message, $context = []) {
        if (!$this->shouldLog($level)) {
            return false;
        }
        
        $logEntry = $this->formatLogEntry($level, $message, $context);
        $filename = $this->getLogFilename($level);
        
        return $this->writeLog($filename, $logEntry);
    }
    
    /**
     * Log de emergência - sistema inutilizável
     */
    public function emergency($message, $context = []) {
        return $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log de alerta - ação deve ser tomada imediatamente
     */
    public function alert($message, $context = []) {
        return $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log crítico - condições críticas
     */
    public function critical($message, $context = []) {
        return $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log de erro - erros runtime que não requerem ação imediata
     */
    public function error($message, $context = []) {
        return $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log de aviso - ocorrências excepcionais que não são erros
     */
    public function warning($message, $context = []) {
        return $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log de notificação - eventos normais mas significativos
     */
    public function notice($message, $context = []) {
        return $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log informativo - eventos interessantes
     */
    public function info($message, $context = []) {
        return $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log de debug - informações detalhadas de debug
     */
    public function debug($message, $context = []) {
        return $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log de segurança
     */
    public function security($message, $context = []) {
        $context['category'] = 'security';
        return $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log de auditoria
     */
    public function audit($action, $entity_type = null, $entity_id = null, $context = []) {
        $auditContext = array_merge($context, [
            'category' => 'audit',
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'user_id' => $_SESSION['user']['id'] ?? $_SESSION['admin']['id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return $this->log(self::INFO, "Audit: {$action}", $auditContext);
    }
    
    /**
     * Log de performance
     */
    public function performance($operation, $duration, $context = []) {
        $perfContext = array_merge($context, [
            'category' => 'performance',
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
        
        $level = $duration > 1.0 ? self::WARNING : self::INFO;
        return $this->log($level, "Performance: {$operation} took {$duration}s", $perfContext);
    }
    
    /**
     * Log de query SQL
     */
    public function query($sql, $duration = null, $params = []) {
        if (!Environment::isDebug()) {
            return false;
        }
        
        $context = [
            'category' => 'database',
            'sql' => $sql,
            'params' => $params
        ];
        
        if ($duration !== null) {
            $context['duration_ms'] = round($duration * 1000, 2);
        }
        
        return $this->log(self::DEBUG, "SQL Query executed", $context);
    }
    
    /**
     * Formata entrada de log
     */
    private function formatLogEntry($level, $message, $context = []) {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'extra' => [
                'memory_usage' => memory_get_usage(true),
                'request_id' => $this->getRequestId(),
                'process_id' => getmypid()
            ]
        ];
        
        // Adicionar stack trace para erros críticos
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            $entry['extra']['stack_trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }
        
        return json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    /**
     * Determina se deve logar baseado no nível
     */
    private function shouldLog($level) {
        $minLevel = Environment::get('LOG_LEVEL', self::INFO);
        
        if (!isset($this->levels[$level]) || !isset($this->levels[$minLevel])) {
            return true;
        }
        
        return $this->levels[$level] <= $this->levels[$minLevel];
    }
    
    /**
     * Obtém nome do arquivo de log
     */
    private function getLogFilename($level) {
        $date = date('Y-m-d');
        
        // Logs críticos em arquivo separado
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL])) {
            return "critical-{$date}.log";
        }
        
        // Logs de erro em arquivo separado
        if ($level === self::ERROR) {
            return "error-{$date}.log";
        }
        
        // Logs de segurança em arquivo separado
        if (isset($context['category']) && $context['category'] === 'security') {
            return "security-{$date}.log";
        }
        
        // Log geral
        return "app-{$date}.log";
    }
    
    /**
     * Escreve log no arquivo
     */
    private function writeLog($filename, $logEntry) {
        $filepath = $this->logDir . '/' . $filename;
        
        try {
            // Verificar se precisa rotacionar
            $this->rotateLogIfNeeded($filepath);
            
            // Escrever log
            $result = file_put_contents($filepath, $logEntry, FILE_APPEND | LOCK_EX);
            
            // Definir permissões seguras
            if ($result && !file_exists($filepath . '.lock')) {
                chmod($filepath, 0640);
                touch($filepath . '.lock');
            }
            
            return $result !== false;
            
        } catch (Exception $e) {
            error_log("Erro ao escrever log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rotaciona log se necessário
     */
    private function rotateLogIfNeeded($filepath) {
        if (!file_exists($filepath)) {
            return;
        }
        
        $maxSize = Environment::get('LOG_MAX_SIZE', 50 * 1024 * 1024); // 50MB
        $maxFiles = Environment::get('LOG_MAX_FILES', 10);
        
        if (filesize($filepath) > $maxSize) {
            // Rotacionar arquivos existentes
            for ($i = $maxFiles - 1; $i > 0; $i--) {
                $oldFile = $filepath . '.' . $i;
                $newFile = $filepath . '.' . ($i + 1);
                
                if (file_exists($oldFile)) {
                    if ($i === $maxFiles - 1) {
                        unlink($oldFile); // Remover arquivo mais antigo
                    } else {
                        rename($oldFile, $newFile);
                    }
                }
            }
            
            // Mover arquivo atual para .1
            rename($filepath, $filepath . '.1');
        }
    }
    
    /**
     * Obtém ID único da requisição
     */
    private function getRequestId() {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = uniqid('req_', true);
        }
        
        return $requestId;
    }
    
    /**
     * Adiciona contexto global
     */
    public function addContext($key, $value) {
        $this->context[$key] = $value;
    }
    
    /**
     * Remove contexto global
     */
    public function removeContext($key) {
        unset($this->context[$key]);
    }
    
    /**
     * Limpa logs antigos
     */
    public function cleanup($days = 30) {
        $files = glob($this->logDir . '/*.log*');
        $cutoff = time() - ($days * 24 * 60 * 60);
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Analisa logs para estatísticas
     */
    public function analyze($days = 7) {
        $files = glob($this->logDir . '/*.log');
        $stats = [
            'total_entries' => 0,
            'by_level' => [],
            'by_category' => [],
            'errors' => [],
            'top_messages' => []
        ];
        
        $cutoff = time() - ($days * 24 * 60 * 60);
        $messages = [];
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                continue;
            }
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                
                if (!$entry) {
                    continue;
                }
                
                $stats['total_entries']++;
                
                // Por nível
                $level = $entry['level'] ?? 'UNKNOWN';
                $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
                
                // Por categoria
                $category = $entry['context']['category'] ?? 'general';
                $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;
                
                // Erros críticos
                if (in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'])) {
                    $stats['errors'][] = [
                        'timestamp' => $entry['timestamp'],
                        'level' => $level,
                        'message' => $entry['message']
                    ];
                }
                
                // Mensagens mais comuns
                $message = $entry['message'];
                $messages[$message] = ($messages[$message] ?? 0) + 1;
            }
        }
        
        // Top 10 mensagens
        arsort($messages);
        $stats['top_messages'] = array_slice($messages, 0, 10, true);
        
        return $stats;
    }
    
    /**
     * Busca logs por critério
     */
    public function search($criteria = [], $limit = 100) {
        $files = glob($this->logDir . '/*.log');
        $results = [];
        $count = 0;
        
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach (array_reverse($lines) as $line) {
                if ($count >= $limit) {
                    break 2;
                }
                
                $entry = json_decode($line, true);
                
                if (!$entry) {
                    continue;
                }
                
                // Aplicar critérios de busca
                $match = true;
                
                if (isset($criteria['level']) && $entry['level'] !== strtoupper($criteria['level'])) {
                    $match = false;
                }
                
                if (isset($criteria['message']) && strpos($entry['message'], $criteria['message']) === false) {
                    $match = false;
                }
                
                if (isset($criteria['category']) && ($entry['context']['category'] ?? '') !== $criteria['category']) {
                    $match = false;
                }
                
                if (isset($criteria['user_id']) && ($entry['context']['user_id'] ?? '') !== $criteria['user_id']) {
                    $match = false;
                }
                
                if ($match) {
                    $results[] = $entry;
                    $count++;
                }
            }
        }
        
        return $results;
    }
}
