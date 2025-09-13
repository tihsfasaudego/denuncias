<?php
/**
 * Arquivo de entrada principal da aplicação
 * 
 * Sistema de rotas centralizado e organizado
 */

// Definir constantes
define('BASE_PATH', dirname(__DIR__));

// Configuração de erros baseada no ambiente
if (defined('APP_ENV') && APP_ENV === 'production') {
    // Produção: Log erros mas não exibir
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Desenvolvimento: Exibir erros se debug habilitado
    if (defined('APP_DEBUG') && APP_DEBUG) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    }
    ini_set('log_errors', 1);
}
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Verificar e criar diretório de logs se não existir
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0777, true);
}

// Carregar configuração
require_once BASE_PATH . '/config/config.php';

// Auto-loader para controllers, models e classes Core
spl_autoload_register(function ($className) {
    // Verificar se é um controller
    if (strpos($className, 'Controller') !== false) {
        $file = BASE_PATH . '/app/Controllers/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Verificar se é um model
    $file = BASE_PATH . '/app/Models/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Verificar se é uma classe Core
    $file = BASE_PATH . '/app/Core/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Classe Config
    $file = BASE_PATH . '/app/Config/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

try {
    // Carregar e executar o gerenciador de rotas
    require_once BASE_PATH . '/app/Core/RouteManager.php';
    
    $routeManager = new RouteManager();
    $routeManager->registerRoutes();
    $routeManager->run();
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro crítico na aplicação: " . $e->getMessage());
    
    // Exibir erro amigável
    http_response_code(500);
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Erro na Aplicação</h1>";
        echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "<h1>Erro interno do servidor</h1>";
        echo "<p>Ocorreu um erro interno. Por favor, tente novamente mais tarde.</p>";
    }
    exit;
}
?>
