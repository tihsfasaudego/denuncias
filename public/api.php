<?php
/**
 * Endpoint principal da API REST
 * Ponto de entrada para todas as requisições da API
 */

// Definir caminho base
define('BASE_PATH', dirname(__DIR__));

// Carregar configurações
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/app/Controllers/ApiController.php';

// Configurações de erro para API
if (Environment::isProduction()) {
    ini_set('display_errors', 0);
    error_reporting(E_ERROR | E_PARSE);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

try {
    // Verificar se API está habilitada
    if (!Environment::get('API_ENABLED', true)) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => 'API temporariamente indisponível',
                'code' => 503
            ]
        ]);
        exit;
    }
    
    // Rate limiting para API
    if (class_exists('Security')) {
        $clientId = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimit = Environment::get('API_RATE_LIMIT', 100); // 100 requests por minuto
        
        if (!Security::checkRateLimit("api_{$clientId}", $rateLimit, 60)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => [
                    'message' => 'Rate limit excedido. Tente novamente em alguns instantes.',
                    'code' => 429
                ]
            ]);
            exit;
        }
    }
    
    // Instanciar e executar controlador da API
    $apiController = new ApiController();
    $apiController->route();
    
} catch (Throwable $e) {
    // Log do erro
    error_log("API Error: " . $e->getMessage());
    
    // Resposta de erro genérica
    http_response_code(500);
    header('Content-Type: application/json');
    
    $response = [
        'success' => false,
        'error' => [
            'message' => Environment::isProduction() ? 
                'Erro interno do servidor' : 
                $e->getMessage(),
            'code' => 500
        ],
        'timestamp' => date('c')
    ];
    
    // Adicionar stack trace em desenvolvimento
    if (!Environment::isProduction()) {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
