<?php
/**
 * Gerenciador de notificações em tempo real
 * Suporta SSE, WebSockets e notificações por email
 */

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/EmailService.php';

class NotificationManager {
    private static $instance = null;
    private $logger;
    private $cache;
    private $emailService;
    
    // Tipos de notificação
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_SYSTEM = 'system';
    
    // Canais de notificação
    const CHANNEL_BROWSER = 'browser';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_WEBHOOK = 'webhook';
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->cache = Cache::getInstance();
        $this->emailService = EmailService::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Cria uma nova notificação
     */
    public function create($title, $message, $type = self::TYPE_INFO, $options = []) {
        $notification = [
            'id' => uniqid('notif_', true),
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'timestamp' => time(),
            'read' => false,
            'user_id' => $options['user_id'] ?? null,
            'role' => $options['role'] ?? null,
            'channels' => $options['channels'] ?? [self::CHANNEL_BROWSER],
            'data' => $options['data'] ?? [],
            'expires_at' => $options['expires_at'] ?? (time() + 86400), // 24h padrão
            'action_url' => $options['action_url'] ?? null,
            'action_text' => $options['action_text'] ?? null
        ];
        
        // Salvar notificação
        $this->saveNotification($notification);
        
        // Enviar pelos canais especificados
        foreach ($notification['channels'] as $channel) {
            $this->sendToChannel($notification, $channel);
        }
        
        // Log da notificação
        $this->logger->info('Notification created', [
            'notification_id' => $notification['id'],
            'type' => $type,
            'user_id' => $notification['user_id'],
            'channels' => $notification['channels']
        ]);
        
        return $notification['id'];
    }
    
    /**
     * Salva notificação no cache/banco
     */
    private function saveNotification($notification) {
        // Salvar notificação individual
        $this->cache->set(
            "notification_{$notification['id']}", 
            $notification, 
            $notification['expires_at'] - time()
        );
        
        // Adicionar à lista de notificações do usuário
        if ($notification['user_id']) {
            $userNotifications = $this->getUserNotifications($notification['user_id']);
            array_unshift($userNotifications, $notification['id']);
            
            // Manter apenas as últimas 50 notificações
            $userNotifications = array_slice($userNotifications, 0, 50);
            
            $this->cache->set(
                "user_notifications_{$notification['user_id']}", 
                $userNotifications, 
                86400
            );
        }
        
        // Adicionar à lista global de notificações
        $globalNotifications = $this->cache->get('global_notifications', []);
        array_unshift($globalNotifications, $notification['id']);
        
        // Manter apenas as últimas 100 notificações globais
        $globalNotifications = array_slice($globalNotifications, 0, 100);
        
        $this->cache->set('global_notifications', $globalNotifications, 86400);
        
        // Incrementar contador de notificações não lidas
        if ($notification['user_id']) {
            $this->cache->increment("unread_notifications_{$notification['user_id']}");
        }
    }
    
    /**
     * Envia notificação para canal específico
     */
    private function sendToChannel($notification, $channel) {
        try {
            switch ($channel) {
                case self::CHANNEL_BROWSER:
                    $this->sendToBrowser($notification);
                    break;
                    
                case self::CHANNEL_EMAIL:
                    $this->sendToEmail($notification);
                    break;
                    
                case self::CHANNEL_SMS:
                    $this->sendToSMS($notification);
                    break;
                    
                case self::CHANNEL_WEBHOOK:
                    $this->sendToWebhook($notification);
                    break;
            }
        } catch (Exception $e) {
            $this->logger->error('Erro ao enviar notificação', [
                'notification_id' => $notification['id'],
                'channel' => $channel,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Envia notificação para browser (SSE)
     */
    private function sendToBrowser($notification) {
        // Adicionar à fila de SSE
        $sseQueue = $this->cache->get('sse_queue', []);
        $sseQueue[] = [
            'event' => 'notification',
            'data' => json_encode($notification),
            'timestamp' => time()
        ];
        
        // Manter apenas os últimos 10 eventos
        $sseQueue = array_slice($sseQueue, -10);
        
        $this->cache->set('sse_queue', $sseQueue, 300); // 5 minutos
    }
    
    /**
     * Envia notificação por email
     */
    private function sendToEmail($notification) {
        if (!$notification['user_id']) {
            return;
        }
        
        // Buscar email do usuário
        $userEmail = $this->getUserEmail($notification['user_id']);
        
        if (!$userEmail) {
            return;
        }
        
        $subject = $notification['title'];
        $body = $this->renderEmailTemplate($notification);
        
        $this->emailService->send($userEmail, $subject, $body);
    }
    
    /**
     * Envia notificação por SMS
     */
    private function sendToSMS($notification) {
        // Implementar integração com serviço de SMS
        // Por enquanto, apenas log
        $this->logger->info('SMS notification would be sent', [
            'notification_id' => $notification['id'],
            'message' => $notification['message']
        ]);
    }
    
    /**
     * Envia notificação via webhook
     */
    private function sendToWebhook($notification) {
        $webhookUrl = Environment::get('WEBHOOK_URL');
        
        if (!$webhookUrl) {
            return;
        }
        
        $payload = [
            'id' => $notification['id'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'timestamp' => $notification['timestamp'],
            'data' => $notification['data']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: HSFA-Denuncias-Webhook/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $this->logger->info('Webhook notification sent', [
                'notification_id' => $notification['id'],
                'webhook_url' => $webhookUrl,
                'http_code' => $httpCode
            ]);
        } else {
            $this->logger->error('Webhook notification failed', [
                'notification_id' => $notification['id'],
                'webhook_url' => $webhookUrl,
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }
    }
    
    /**
     * Obtém notificações do usuário
     */
    public function getUserNotifications($userId, $limit = 20, $onlyUnread = false) {
        $notificationIds = $this->cache->get("user_notifications_{$userId}", []);
        $notifications = [];
        
        foreach (array_slice($notificationIds, 0, $limit) as $id) {
            $notification = $this->cache->get("notification_{$id}");
            
            if ($notification && (!$onlyUnread || !$notification['read'])) {
                $notifications[] = $notification;
            }
        }
        
        return $notifications;
    }
    
    /**
     * Marca notificação como lida
     */
    public function markAsRead($notificationId, $userId = null) {
        $notification = $this->cache->get("notification_{$notificationId}");
        
        if (!$notification) {
            return false;
        }
        
        // Verificar se o usuário pode marcar esta notificação
        if ($userId && $notification['user_id'] !== $userId) {
            return false;
        }
        
        $notification['read'] = true;
        
        $this->cache->set(
            "notification_{$notificationId}", 
            $notification, 
            $notification['expires_at'] - time()
        );
        
        // Decrementar contador de não lidas
        if ($notification['user_id']) {
            $this->cache->decrement("unread_notifications_{$notification['user_id']}");
        }
        
        return true;
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead($userId) {
        $notifications = $this->getUserNotifications($userId, 50, true);
        
        foreach ($notifications as $notification) {
            $this->markAsRead($notification['id'], $userId);
        }
        
        // Zerar contador
        $this->cache->set("unread_notifications_{$userId}", 0, 86400);
        
        return count($notifications);
    }
    
    /**
     * Obtém contador de notificações não lidas
     */
    public function getUnreadCount($userId) {
        return (int)$this->cache->get("unread_notifications_{$userId}", 0);
    }
    
    /**
     * Remove notificação
     */
    public function delete($notificationId, $userId = null) {
        $notification = $this->cache->get("notification_{$notificationId}");
        
        if (!$notification) {
            return false;
        }
        
        // Verificar se o usuário pode deletar esta notificação
        if ($userId && $notification['user_id'] !== $userId) {
            return false;
        }
        
        // Remover do cache
        $this->cache->delete("notification_{$notificationId}");
        
        // Remover da lista do usuário
        if ($notification['user_id']) {
            $userNotifications = $this->cache->get("user_notifications_{$notification['user_id']}", []);
            $userNotifications = array_filter($userNotifications, function($id) use ($notificationId) {
                return $id !== $notificationId;
            });
            $this->cache->set("user_notifications_{$notification['user_id']}", $userNotifications, 86400);
        }
        
        return true;
    }
    
    /**
     * Stream SSE para notificações em tempo real
     */
    public function streamSSE($userId = null) {
        // Configurar headers SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Cache-Control');
        
        // Evitar timeout
        ignore_user_abort(false);
        set_time_limit(0);
        
        $lastEventId = $_SERVER['HTTP_LAST_EVENT_ID'] ?? 0;
        
        while (true) {
            // Verificar se cliente ainda está conectado
            if (connection_aborted()) {
                break;
            }
            
            // Obter eventos da fila
            $events = $this->getSSEEvents($lastEventId, $userId);
            
            foreach ($events as $event) {
                echo "id: {$event['id']}\n";
                echo "event: {$event['event']}\n";
                echo "data: {$event['data']}\n\n";
                
                $lastEventId = $event['id'];
            }
            
            // Enviar heartbeat
            echo "event: heartbeat\n";
            echo "data: " . json_encode(['timestamp' => time()]) . "\n\n";
            
            ob_flush();
            flush();
            
            // Aguardar 2 segundos antes do próximo check
            sleep(2);
        }
    }
    
    /**
     * Obtém eventos SSE
     */
    private function getSSEEvents($lastEventId, $userId = null) {
        $sseQueue = $this->cache->get('sse_queue', []);
        $events = [];
        
        foreach ($sseQueue as $index => $item) {
            $eventId = $item['timestamp'] . '_' . $index;
            
            if ($eventId > $lastEventId) {
                // Verificar se o evento é para este usuário
                if ($userId) {
                    $data = json_decode($item['data'], true);
                    if (isset($data['user_id']) && $data['user_id'] != $userId) {
                        continue;
                    }
                }
                
                $events[] = [
                    'id' => $eventId,
                    'event' => $item['event'],
                    'data' => $item['data']
                ];
            }
        }
        
        return $events;
    }
    
    /**
     * Template de email para notificações
     */
    private function renderEmailTemplate($notification) {
        $template = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h2 style="color: #333; margin: 0 0 15px 0;">' . htmlspecialchars($notification['title']) . '</h2>
                <p style="color: #666; line-height: 1.6; margin: 0 0 15px 0;">' . htmlspecialchars($notification['message']) . '</p>
                
                ' . ($notification['action_url'] ? '
                <div style="margin: 20px 0;">
                    <a href="' . htmlspecialchars($notification['action_url']) . '" 
                       style="background: #007bff; color: white; padding: 10px 20px; 
                              text-decoration: none; border-radius: 5px; display: inline-block;">
                        ' . htmlspecialchars($notification['action_text'] ?: 'Ver Detalhes') . '
                    </a>
                </div>
                ' : '') . '
                
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="color: #999; font-size: 12px; margin: 0;">
                    Esta notificação foi enviada automaticamente pelo Sistema de Denúncias HSFA.<br>
                    Data: ' . date('d/m/Y H:i', $notification['timestamp']) . '
                </p>
            </div>
        </div>';
        
        return $template;
    }
    
    /**
     * Obtém email do usuário
     */
    private function getUserEmail($userId) {
        // Primeiro tentar na tabela admin
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->prepare("SELECT email FROM admin WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['email']) {
            return $result['email'];
        }
        
        // Tentar na tabela users
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['email'] ?? null;
    }
    
    /**
     * Limpa notificações expiradas
     */
    public function cleanup() {
        $cleaned = 0;
        $globalNotifications = $this->cache->get('global_notifications', []);
        
        foreach ($globalNotifications as $id) {
            $notification = $this->cache->get("notification_{$id}");
            
            if (!$notification || $notification['expires_at'] < time()) {
                $this->cache->delete("notification_{$id}");
                $cleaned++;
            }
        }
        
        // Atualizar lista global
        $validNotifications = [];
        foreach ($globalNotifications as $id) {
            if ($this->cache->get("notification_{$id}")) {
                $validNotifications[] = $id;
            }
        }
        
        $this->cache->set('global_notifications', $validNotifications, 86400);
        
        return $cleaned;
    }
    
    /**
     * Notificações pré-definidas do sistema
     */
    public function denunciaCreated($denunciaId, $protocolo) {
        $this->create(
            'Nova Denúncia Recebida',
            "Uma nova denúncia foi registrada com o protocolo {$protocolo}.",
            self::TYPE_INFO,
            [
                'role' => 'admin',
                'channels' => [self::CHANNEL_BROWSER, self::CHANNEL_EMAIL],
                'data' => ['denuncia_id' => $denunciaId, 'protocolo' => $protocolo],
                'action_url' => "/admin/denuncias/{$protocolo}",
                'action_text' => 'Visualizar Denúncia'
            ]
        );
    }
    
    public function denunciaStatusChanged($denunciaId, $protocolo, $oldStatus, $newStatus, $userId) {
        $this->create(
            'Status da Denúncia Atualizado',
            "A denúncia {$protocolo} teve seu status alterado de '{$oldStatus}' para '{$newStatus}'.",
            self::TYPE_SUCCESS,
            [
                'user_id' => $userId,
                'channels' => [self::CHANNEL_BROWSER],
                'data' => [
                    'denuncia_id' => $denunciaId, 
                    'protocolo' => $protocolo,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ],
                'action_url' => "/admin/denuncias/{$protocolo}",
                'action_text' => 'Ver Detalhes'
            ]
        );
    }
    
    public function systemAlert($title, $message, $type = self::TYPE_WARNING) {
        $this->create(
            $title,
            $message,
            $type,
            [
                'role' => 'admin',
                'channels' => [self::CHANNEL_BROWSER, self::CHANNEL_EMAIL],
                'expires_at' => time() + 172800 // 48h
            ]
        );
    }
}
