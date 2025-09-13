<?php
/**
 * Controlador de notificações
 * Gerencia interface web das notificações
 */

require_once __DIR__ . '/../Core/NotificationManager.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Logger.php';

class NotificationController {
    private $notificationManager;
    private $logger;
    
    public function __construct() {
        $this->notificationManager = NotificationManager::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Lista notificações do usuário (AJAX)
     */
    public function list() {
        $this->requireAuth();
        
        $userId = Auth::id();
        $limit = (int)($_GET['limit'] ?? 20);
        $onlyUnread = isset($_GET['unread']) && $_GET['unread'] === 'true';
        
        $notifications = $this->notificationManager->getUserNotifications($userId, $limit, $onlyUnread);
        $unreadCount = $this->notificationManager->getUnreadCount($userId);
        
        $this->jsonResponse([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total' => count($notifications)
        ]);
    }
    
    /**
     * Marca notificação como lida
     */
    public function markAsRead() {
        $this->requireAuth();
        
        $input = $this->getJsonInput();
        $notificationId = $input['notification_id'] ?? null;
        
        if (!$notificationId) {
            $this->jsonError('ID da notificação é obrigatório', 400);
        }
        
        $userId = Auth::id();
        $success = $this->notificationManager->markAsRead($notificationId, $userId);
        
        if ($success) {
            $this->jsonResponse(['message' => 'Notificação marcada como lida']);
        } else {
            $this->jsonError('Notificação não encontrada ou sem permissão', 404);
        }
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead() {
        $this->requireAuth();
        
        $userId = Auth::id();
        $count = $this->notificationManager->markAllAsRead($userId);
        
        $this->jsonResponse([
            'message' => "Todas as notificações foram marcadas como lidas",
            'count' => $count
        ]);
    }
    
    /**
     * Remove notificação
     */
    public function delete() {
        $this->requireAuth();
        
        $input = $this->getJsonInput();
        $notificationId = $input['notification_id'] ?? null;
        
        if (!$notificationId) {
            $this->jsonError('ID da notificação é obrigatório', 400);
        }
        
        $userId = Auth::id();
        $success = $this->notificationManager->delete($notificationId, $userId);
        
        if ($success) {
            $this->jsonResponse(['message' => 'Notificação removida']);
        } else {
            $this->jsonError('Notificação não encontrada ou sem permissão', 404);
        }
    }
    
    /**
     * Obtém contador de não lidas
     */
    public function getUnreadCount() {
        $this->requireAuth();
        
        $userId = Auth::id();
        $count = $this->notificationManager->getUnreadCount($userId);
        
        $this->jsonResponse(['unread_count' => $count]);
    }
    
    /**
     * Cria notificação de teste (apenas para admins)
     */
    public function createTest() {
        $this->requireAuth();
        
        if (!Auth::hasRole('admin')) {
            $this->jsonError('Sem permissão', 403);
        }
        
        $input = $this->getJsonInput();
        
        $title = $input['title'] ?? 'Notificação de Teste';
        $message = $input['message'] ?? 'Esta é uma notificação de teste.';
        $type = $input['type'] ?? NotificationManager::TYPE_INFO;
        
        $notificationId = $this->notificationManager->create(
            $title,
            $message,
            $type,
            [
                'user_id' => Auth::id(),
                'channels' => [NotificationManager::CHANNEL_BROWSER]
            ]
        );
        
        $this->jsonResponse([
            'message' => 'Notificação de teste criada',
            'notification_id' => $notificationId
        ]);
    }
    
    /**
     * Configurações de notificação do usuário
     */
    public function getSettings() {
        $this->requireAuth();
        
        $userId = Auth::id();
        $cache = Cache::getInstance();
        
        $settings = $cache->get("notification_settings_{$userId}", [
            'email_enabled' => true,
            'browser_enabled' => true,
            'sound_enabled' => true,
            'denuncia_created' => true,
            'denuncia_updated' => true,
            'denuncia_assigned' => true,
            'system_alerts' => true
        ]);
        
        $this->jsonResponse($settings);
    }
    
    /**
     * Atualiza configurações de notificação
     */
    public function updateSettings() {
        $this->requireAuth();
        
        $input = $this->getJsonInput();
        $userId = Auth::id();
        $cache = Cache::getInstance();
        
        // Validar configurações
        $allowedSettings = [
            'email_enabled', 'browser_enabled', 'sound_enabled',
            'denuncia_created', 'denuncia_updated', 'denuncia_assigned', 'system_alerts'
        ];
        
        $settings = [];
        foreach ($allowedSettings as $setting) {
            if (isset($input[$setting])) {
                $settings[$setting] = (bool)$input[$setting];
            }
        }
        
        // Salvar configurações
        $cache->set("notification_settings_{$userId}", $settings, 86400 * 30); // 30 dias
        
        $this->logger->audit('notification_settings_updated', 'user', $userId, [
            'settings' => $settings
        ]);
        
        $this->jsonResponse([
            'message' => 'Configurações atualizadas',
            'settings' => $settings
        ]);
    }
    
    /**
     * Testa envio de email
     */
    public function testEmail() {
        $this->requireAuth();
        
        if (!Auth::hasRole('admin')) {
            $this->jsonError('Sem permissão', 403);
        }
        
        $input = $this->getJsonInput();
        $email = $input['email'] ?? Auth::getUser()['email'] ?? null;
        
        if (!$email) {
            $this->jsonError('Email não encontrado', 400);
        }
        
        try {
            $notificationId = $this->notificationManager->create(
                'Teste de Email',
                'Este é um email de teste do sistema de notificações.',
                NotificationManager::TYPE_INFO,
                [
                    'user_id' => Auth::id(),
                    'channels' => [NotificationManager::CHANNEL_EMAIL]
                ]
            );
            
            $this->jsonResponse([
                'message' => 'Email de teste enviado',
                'notification_id' => $notificationId
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao enviar email de teste: ' . $e->getMessage());
            $this->jsonError('Erro ao enviar email: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Painel de notificações (view)
     */
    public function panel() {
        $this->requireAuth();
        
        $userId = Auth::id();
        $notifications = $this->notificationManager->getUserNotifications($userId, 10);
        $unreadCount = $this->notificationManager->getUnreadCount($userId);
        
        include __DIR__ . '/../Views/admin/notifications.php';
    }
    
    /**
     * Verifica autenticação
     */
    private function requireAuth() {
        if (!Auth::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Não autenticado']);
            exit;
        }
    }
    
    /**
     * Obtém input JSON
     */
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonError('JSON inválido', 400);
        }
        
        return $data ?? [];
    }
    
    /**
     * Resposta JSON de sucesso
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Resposta JSON de erro
     */
    private function jsonError($message, $status = 500) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ],
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
