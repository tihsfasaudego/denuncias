<?php
/**
 * Endpoint Server-Sent Events para notificações em tempo real
 */

// Definir caminho base
define('BASE_PATH', dirname(__DIR__));

// Carregar configurações
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/app/Core/NotificationManager.php';
require_once BASE_PATH . '/app/Core/Auth.php';

// Verificar autenticação
session_start();

if (!Auth::check()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

try {
    // Obter ID do usuário atual
    $userId = Auth::id();
    
    // Log da conexão SSE
    $logger = Logger::getInstance();
    $logger->info('SSE connection established', [
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Iniciar stream SSE
    $notificationManager = NotificationManager::getInstance();
    $notificationManager->streamSSE($userId);
    
} catch (Exception $e) {
    error_log("SSE Error: " . $e->getMessage());
    
    // Enviar erro via SSE
    header('Content-Type: text/event-stream');
    echo "event: error\n";
    echo "data: " . json_encode(['message' => 'Erro interno do servidor']) . "\n\n";
    flush();
}
