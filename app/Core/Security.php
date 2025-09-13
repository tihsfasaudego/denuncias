<?php
/**
 * Classe para gerenciamento de segurança da aplicação
 * Implementa headers de segurança, validações e proteções
 */
class Security {
    
    /**
     * Configura headers de segurança HTTP
     */
    public static function setSecurityHeaders() {
        // Só aplicar em produção ou se explicitamente habilitado
        if (!Environment::isProduction() && !Environment::get('FORCE_SECURITY_HEADERS', false)) {
            return;
        }
        
        // Prevenir clickjacking
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            
            // Prevenir MIME sniffing
            header('X-Content-Type-Options: nosniff');
            
            // XSS Protection
            header('X-XSS-Protection: 1; mode=block');
            
            // Referrer Policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // HSTS apenas se HTTPS
            if (self::isHTTPS()) {
                $maxAge = Environment::get('HSTS_MAX_AGE', 31536000); // 1 ano
                header("Strict-Transport-Security: max-age={$maxAge}; includeSubDomains; preload");
            }
            
            // Content Security Policy
            $csp = self::buildCSP();
            if ($csp) {
                header("Content-Security-Policy: {$csp}");
            }
        }
    }
    
    /**
     * Constrói a política de Content Security Policy
     */
    private static function buildCSP() {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com code.jquery.com",
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com",
            "font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'"
        ];
        
        return implode('; ', $policies);
    }
    
    /**
     * Verifica se a conexão é HTTPS
     */
    public static function isHTTPS() {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
    
    /**
     * Força redirecionamento para HTTPS em produção
     */
    public static function enforceHTTPS() {
        if (Environment::isProduction() && !self::isHTTPS()) {
            $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: {$httpsUrl}", true, 301);
            exit;
        }
    }
    
    /**
     * Sanitiza entrada de dados
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'html':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
            
            case 'string':
            default:
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Valida entrada de dados
     */
    public static function validateInput($input, $type, $options = []) {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;
            
            case 'int':
                $min = $options['min'] ?? null;
                $max = $options['max'] ?? null;
                $flags = 0;
                $filterOptions = [];
                
                if ($min !== null) {
                    $filterOptions['min_range'] = $min;
                }
                if ($max !== null) {
                    $filterOptions['max_range'] = $max;
                }
                
                if (!empty($filterOptions)) {
                    return filter_var($input, FILTER_VALIDATE_INT, ['options' => $filterOptions]) !== false;
                }
                
                return filter_var($input, FILTER_VALIDATE_INT) !== false;
            
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
            
            case 'required':
                return !empty(trim($input));
            
            case 'length':
                $min = $options['min'] ?? 0;
                $max = $options['max'] ?? PHP_INT_MAX;
                $length = strlen($input);
                return $length >= $min && $length <= $max;
            
            case 'regex':
                $pattern = $options['pattern'] ?? null;
                if (!$pattern) {
                    return false;
                }
                return preg_match($pattern, $input) === 1;
            
            default:
                return true;
        }
    }
    
    /**
     * Gera token CSRF seguro
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Valida token CSRF
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se existe token na sessão
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Verificar se o token não expirou (30 minutos)
        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge > 1800) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
        
        // Verificar se os tokens coincidem (timing-safe comparison)
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting simples baseado em IP
     */
    public static function checkRateLimit($action = 'default', $maxAttempts = null, $window = null) {
        if (!Environment::get('RATE_LIMIT_ENABLED', true)) {
            return true;
        }
        
        $maxAttempts = $maxAttempts ?: Environment::get('RATE_LIMIT_MAX_ATTEMPTS', 60);
        $window = $window ?: Environment::get('RATE_LIMIT_WINDOW', 60);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$action}_{$ip}";
        
        // Usar arquivo para armazenar rate limit (melhor seria Redis/Memcached)
        $rateLimitFile = sys_get_temp_dir() . "/rate_limit_{$action}_{$ip}.json";
        
        $currentTime = time();
        $attempts = [];
        
        // Carregar tentativas anteriores
        if (file_exists($rateLimitFile)) {
            $data = json_decode(file_get_contents($rateLimitFile), true);
            if ($data && isset($data['attempts'])) {
                $attempts = $data['attempts'];
            }
        }
        
        // Filtrar tentativas dentro da janela de tempo
        $attempts = array_filter($attempts, function($timestamp) use ($currentTime, $window) {
            return ($currentTime - $timestamp) < $window;
        });
        
        // Verificar se excedeu o limite
        if (count($attempts) >= $maxAttempts) {
            error_log("Rate limit excedido para IP {$ip} na ação {$action}");
            return false;
        }
        
        // Registrar nova tentativa
        $attempts[] = $currentTime;
        
        // Salvar dados atualizados
        file_put_contents($rateLimitFile, json_encode(['attempts' => $attempts]));
        
        return true;
    }
    
    /**
     * Registra tentativa de login falhada
     */
    public static function logFailedLogin($username, $ip = null) {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'username' => $username,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'action' => 'failed_login'
        ];
        
        error_log("Login falhou: " . json_encode($logEntry));
    }
    
    /**
     * Registra atividade suspeita
     */
    public static function logSuspiciousActivity($activity, $details = []) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'activity' => $activity,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'url' => $url,
            'details' => $details
        ];
        
        error_log("Atividade suspeita: " . json_encode($logEntry));
    }
    
    /**
     * Limpa dados antigos de rate limiting
     */
    public static function cleanupRateLimitData() {
        $tempDir = sys_get_temp_dir();
        $pattern = $tempDir . '/rate_limit_*.json';
        $files = glob($pattern);
        
        $currentTime = time();
        $maxAge = 3600; // 1 hora
        
        foreach ($files as $file) {
            if (($currentTime - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }
}
