<?php
// Carregar variáveis de ambiente
require_once __DIR__ . '/../app/Core/Environment.php';
require_once __DIR__ . '/../app/Core/Security.php';

// Carregar arquivo .env se existir, senão definir variáveis padrão
if (file_exists(__DIR__ . '/../.env')) {
    Environment::load();
} else {
    // Definir variáveis de ambiente padrão diretamente
    error_log('Arquivo .env não encontrado, definindo configurações padrão');
    
    // Configurações do Banco de Dados
    $_ENV['DB_HOST'] = '192.168.2.40';
    $_ENV['DB_NAME'] = 'denuncias';
    $_ENV['DB_USER'] = 'admin_user';
    $_ENV['DB_PASS'] = 'wYynE4Q2Qy';
    $_ENV['DB_CHARSET'] = 'utf8mb4';
    
    // Configurações da Aplicação
    $_ENV['APP_NAME'] = 'Canal de Denúncias - HSFA';
    $_ENV['APP_URL'] = 'https://denuncias.hsfasaude.com.br:8444';
    $_ENV['APP_ENV'] = 'production';
    $_ENV['APP_DEBUG'] = 'true';
    
    // Configurações de Segurança
    $_ENV['SESSION_SECURE'] = 'true';
    $_ENV['SESSION_TIMEOUT'] = '1800';
    $_ENV['MAX_LOGIN_ATTEMPTS'] = '5';
    $_ENV['LOCKOUT_DURATION'] = '1800';
    
    // Configurações de Upload
    $_ENV['UPLOAD_MAX_SIZE'] = '10485760';
    $_ENV['UPLOAD_ALLOWED_TYPES'] = 'jpg,jpeg,png,pdf';
    $_ENV['UPLOAD_PATH'] = 'public/uploads';
    
    // Configurações de Cache
    $_ENV['CACHE_DRIVER'] = 'file';
    $_ENV['CACHE_TTL'] = '3600';
    
    // Configurações de Assets
    $_ENV['ASSETS_VERSION'] = '1.0.0';
}

// Aplicar configurações de segurança (desabilitado temporariamente para debug)
// Security::enforceHTTPS();
// Security::setSecurityHeaders();

// Configurações do Banco de Dados (usando variáveis de ambiente)
if (!defined('DB_HOST')) define('DB_HOST', Environment::get('DB_HOST', '192.168.2.40'));
if (!defined('DB_NAME')) define('DB_NAME', Environment::get('DB_NAME', 'denuncias'));
if (!defined('DB_USER')) define('DB_USER', Environment::get('DB_USER', 'admin_user'));
if (!defined('DB_PASS')) define('DB_PASS', Environment::get('DB_PASS', 'wYynE4Q2Qy'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', Environment::get('DB_CHARSET', 'utf8mb4'));

// Configurações da Aplicação
if (!defined('APP_NAME')) define('APP_NAME', Environment::get('APP_NAME', 'Canal de Denúncias - HSFA'));
if (!defined('APP_URL')) define('APP_URL', Environment::get('APP_URL', 'http://localhost'));
if (!defined('APP_ENV')) define('APP_ENV', Environment::get('APP_ENV', 'production'));
if (!defined('APP_DEBUG')) define('APP_DEBUG', Environment::get('APP_DEBUG', false));

// Configurações de Upload
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../' . Environment::get('UPLOAD_PATH', 'public/uploads'));
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', Environment::get('UPLOAD_MAX_SIZE', 10 * 1024 * 1024)); // 10MB padrão
if (!defined('ALLOWED_UPLOAD_TYPES')) define('ALLOWED_UPLOAD_TYPES', explode(',', Environment::get('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,pdf')));

// Configurações de Segurança
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', Environment::get('SESSION_TIMEOUT', 1800)); // 30 minutos
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', Environment::get('MAX_LOGIN_ATTEMPTS', 5));
if (!defined('LOCKOUT_DURATION')) define('LOCKOUT_DURATION', Environment::get('LOCKOUT_DURATION', 1800)); // 30 minutos

// Criar diretórios necessários
$directories = [
    UPLOAD_DIR => 'uploads',
    BASE_PATH . '/storage' => 'storage',
    BASE_PATH . '/storage/cache' => 'cache',
    BASE_PATH . '/storage/logs' => 'logs estruturados',
    BASE_PATH . '/storage/temp' => 'arquivos temporários'
];

foreach ($directories as $dir => $description) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Erro: Não foi possível criar o diretório de {$description}: " . $dir);
        }
    }
}

// Configurar ambiente baseado nas variáveis
if (Environment::isProduction()) {
    // Produção: Desabilitar exibição de erros
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Desenvolvimento: Habilitar exibição de erros
    if (Environment::isDebug()) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }
}

// Configurações de Sessão (somente se a sessão ainda não estiver iniciada)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', Environment::get('SESSION_SECURE', 0)); // 1 para HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    session_start();
}
?>
