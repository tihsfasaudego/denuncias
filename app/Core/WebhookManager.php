<?php
/**
 * Gerenciador de webhooks e integrações externas
 * Permite notificar sistemas externos sobre eventos do sistema
 */

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/NotificationManager.php';

class WebhookManager {
    private static $instance = null;
    private $logger;
    private $cache;
    private $notificationManager;
    
    // Eventos disponíveis
    const EVENT_DENUNCIA_CREATED = 'denuncia.created';
    const EVENT_DENUNCIA_UPDATED = 'denuncia.updated';
    const EVENT_DENUNCIA_ASSIGNED = 'denuncia.assigned';
    const EVENT_DENUNCIA_CONCLUDED = 'denuncia.concluded';
    const EVENT_USER_CREATED = 'user.created';
    const EVENT_LOGIN_FAILED = 'login.failed';
    const EVENT_BACKUP_COMPLETED = 'backup.completed';
    const EVENT_SYSTEM_ERROR = 'system.error';
    
    // Status de webhook
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRY = 'retry';
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->cache = Cache::getInstance();
        $this->notificationManager = NotificationManager::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Registra webhook
     */
    public function registerWebhook($url, $events = [], $options = []) {
        $webhook = [
            'id' => uniqid('webhook_', true),
            'url' => $url,
            'events' => $events,
            'secret' => $options['secret'] ?? $this->generateSecret(),
            'enabled' => $options['enabled'] ?? true,
            'retry_attempts' => $options['retry_attempts'] ?? 3,
            'timeout' => $options['timeout'] ?? 10,
            'headers' => $options['headers'] ?? [],
            'created_at' => time(),
            'last_sent' => null,
            'success_count' => 0,
            'failure_count' => 0
        ];
        
        // Salvar webhook
        $this->saveWebhook($webhook);
        
        $this->logger->audit('webhook_registered', 'webhook', $webhook['id'], [
            'url' => $url,
            'events' => $events
        ]);
        
        return $webhook['id'];
    }
    
    /**
     * Dispara evento para webhooks
     */
    public function fireEvent($event, $data, $context = []) {
        $webhooks = $this->getWebhooksForEvent($event);
        
        if (empty($webhooks)) {
            return;
        }
        
        $payload = [
            'event' => $event,
            'timestamp' => time(),
            'data' => $data,
            'context' => $context,
            'source' => [
                'application' => 'HSFA-Denuncias',
                'version' => Environment::get('APP_VERSION', '1.0.0'),
                'environment' => Environment::get('APP_ENV', 'production')
            ]
        ];
        
        foreach ($webhooks as $webhook) {
            $this->queueWebhookDelivery($webhook, $payload);
        }
        
        $this->logger->info('Webhook event fired', [
            'event' => $event,
            'webhooks_count' => count($webhooks),
            'data_keys' => array_keys($data)
        ]);
    }
    
    /**
     * Enfileira entrega de webhook
     */
    private function queueWebhookDelivery($webhook, $payload) {
        $delivery = [
            'id' => uniqid('delivery_', true),
            'webhook_id' => $webhook['id'],
            'webhook_url' => $webhook['url'],
            'payload' => $payload,
            'signature' => $this->generateSignature($payload, $webhook['secret']),
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'max_attempts' => $webhook['retry_attempts'],
            'timeout' => $webhook['timeout'],
            'headers' => $webhook['headers'],
            'created_at' => time(),
            'next_attempt' => time(),
            'last_error' => null,
            'response_code' => null,
            'response_body' => null
        ];
        
        // Adicionar à fila
        $queue = $this->cache->get('webhook_queue', []);
        $queue[] = $delivery;
        
        // Manter apenas as últimas 1000 entregas na fila
        $queue = array_slice($queue, -1000);
        
        $this->cache->set('webhook_queue', $queue, 86400); // 24h
        
        // Processar imediatamente se não há muitas entregas pendentes
        if (count($queue) < 10) {
            $this->processDelivery($delivery);
        }
    }
    
    /**
     * Processa entrega de webhook
     */
    private function processDelivery($delivery) {
        $delivery['attempts']++;
        $delivery['status'] = self::STATUS_PENDING;
        
        try {
            $response = $this->sendWebhook($delivery);
            
            if ($response['success']) {
                $delivery['status'] = self::STATUS_SENT;
                $delivery['response_code'] = $response['http_code'];
                $delivery['response_body'] = substr($response['body'], 0, 1000); // Limitar tamanho
                
                // Atualizar estatísticas do webhook
                $this->updateWebhookStats($delivery['webhook_id'], true);
                
                $this->logger->info('Webhook delivered successfully', [
                    'delivery_id' => $delivery['id'],
                    'webhook_id' => $delivery['webhook_id'],
                    'url' => $delivery['webhook_url'],
                    'http_code' => $response['http_code'],
                    'attempts' => $delivery['attempts']
                ]);
                
            } else {
                throw new Exception("HTTP {$response['http_code']}: {$response['body']}");
            }
            
        } catch (Exception $e) {
            $delivery['last_error'] = $e->getMessage();
            
            if ($delivery['attempts'] >= $delivery['max_attempts']) {
                $delivery['status'] = self::STATUS_FAILED;
                
                // Atualizar estatísticas do webhook
                $this->updateWebhookStats($delivery['webhook_id'], false);
                
                // Notificar administradores sobre falha
                $this->notifyWebhookFailure($delivery);
                
                $this->logger->error('Webhook delivery failed permanently', [
                    'delivery_id' => $delivery['id'],
                    'webhook_id' => $delivery['webhook_id'],
                    'url' => $delivery['webhook_url'],
                    'error' => $e->getMessage(),
                    'attempts' => $delivery['attempts']
                ]);
                
            } else {
                $delivery['status'] = self::STATUS_RETRY;
                $delivery['next_attempt'] = time() + $this->calculateRetryDelay($delivery['attempts']);
                
                $this->logger->warning('Webhook delivery failed, will retry', [
                    'delivery_id' => $delivery['id'],
                    'webhook_id' => $delivery['webhook_id'],
                    'url' => $delivery['webhook_url'],
                    'error' => $e->getMessage(),
                    'attempts' => $delivery['attempts'],
                    'next_attempt' => date('c', $delivery['next_attempt'])
                ]);
            }
        }
        
        // Salvar status da entrega
        $this->saveDelivery($delivery);
    }
    
    /**
     * Envia webhook via HTTP
     */
    private function sendWebhook($delivery) {
        $ch = curl_init();
        
        // Headers padrão
        $headers = [
            'Content-Type: application/json',
            'User-Agent: HSFA-Denuncias-Webhook/1.0',
            'X-Webhook-ID: ' . $delivery['webhook_id'],
            'X-Delivery-ID: ' . $delivery['id'],
            'X-Signature: ' . $delivery['signature'],
            'X-Timestamp: ' . $delivery['payload']['timestamp']
        ];
        
        // Adicionar headers customizados
        foreach ($delivery['headers'] as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $delivery['webhook_url'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($delivery['payload']),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $delivery['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => Environment::isProduction(),
            CURLOPT_USERAGENT => 'HSFA-Denuncias-Webhook/1.0'
        ]);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $duration = microtime(true) - $startTime;
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }
        
        $success = $httpCode >= 200 && $httpCode < 300;
        
        // Log da performance
        $this->logger->performance('webhook_delivery', $duration, [
            'webhook_id' => $delivery['webhook_id'],
            'url' => $delivery['webhook_url'],
            'http_code' => $httpCode,
            'success' => $success
        ]);
        
        return [
            'success' => $success,
            'http_code' => $httpCode,
            'body' => $response,
            'duration' => $duration
        ];
    }
    
    /**
     * Processa fila de webhooks
     */
    public function processQueue() {
        $queue = $this->cache->get('webhook_queue', []);
        $processed = 0;
        $now = time();
        
        foreach ($queue as $index => $delivery) {
            // Processar apenas entregas pendentes ou que chegaram a hora do retry
            if (($delivery['status'] === self::STATUS_PENDING || 
                 $delivery['status'] === self::STATUS_RETRY) &&
                $delivery['next_attempt'] <= $now) {
                
                $this->processDelivery($delivery);
                $processed++;
                
                // Limitar processamento para evitar sobrecarga
                if ($processed >= 20) {
                    break;
                }
            }
        }
        
        // Limpar entregas antigas da fila
        $this->cleanupQueue();
        
        return $processed;
    }
    
    /**
     * Limpa fila de webhooks
     */
    private function cleanupQueue() {
        $queue = $this->cache->get('webhook_queue', []);
        $cutoff = time() - 86400; // 24 horas
        
        $queue = array_filter($queue, function($delivery) use ($cutoff) {
            // Manter entregas recentes ou ainda pendentes/retry
            return $delivery['created_at'] > $cutoff || 
                   in_array($delivery['status'], [self::STATUS_PENDING, self::STATUS_RETRY]);
        });
        
        $this->cache->set('webhook_queue', array_values($queue), 86400);
    }
    
    /**
     * Calcula delay para retry
     */
    private function calculateRetryDelay($attempt) {
        // Backoff exponencial: 2^attempt minutos
        return min(pow(2, $attempt) * 60, 3600); // Máximo 1 hora
    }
    
    /**
     * Gera assinatura para webhook
     */
    private function generateSignature($payload, $secret) {
        $data = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $data, $secret);
    }
    
    /**
     * Verifica assinatura de webhook
     */
    public function verifySignature($payload, $signature, $secret) {
        $expectedSignature = $this->generateSignature($payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Gera secret para webhook
     */
    private function generateSecret() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Obtém webhooks para evento
     */
    private function getWebhooksForEvent($event) {
        $allWebhooks = $this->cache->get('webhooks_list', []);
        
        return array_filter($allWebhooks, function($webhook) use ($event) {
            return $webhook['enabled'] && 
                   (empty($webhook['events']) || in_array($event, $webhook['events']));
        });
    }
    
    /**
     * Salva webhook
     */
    private function saveWebhook($webhook) {
        $this->cache->set("webhook_{$webhook['id']}", $webhook, 86400 * 365); // 1 ano
        
        // Atualizar lista
        $webhooks = $this->cache->get('webhooks_list', []);
        
        // Atualizar ou adicionar
        $found = false;
        foreach ($webhooks as &$item) {
            if ($item['id'] === $webhook['id']) {
                $item = $webhook;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $webhooks[] = $webhook;
        }
        
        $this->cache->set('webhooks_list', $webhooks, 86400 * 365);
    }
    
    /**
     * Salva entrega
     */
    private function saveDelivery($delivery) {
        $this->cache->set("delivery_{$delivery['id']}", $delivery, 86400 * 7); // 7 dias
        
        // Atualizar na fila
        $queue = $this->cache->get('webhook_queue', []);
        
        foreach ($queue as &$item) {
            if ($item['id'] === $delivery['id']) {
                $item = $delivery;
                break;
            }
        }
        
        $this->cache->set('webhook_queue', $queue, 86400);
    }
    
    /**
     * Atualiza estatísticas do webhook
     */
    private function updateWebhookStats($webhookId, $success) {
        $webhook = $this->cache->get("webhook_{$webhookId}");
        
        if ($webhook) {
            $webhook['last_sent'] = time();
            
            if ($success) {
                $webhook['success_count']++;
            } else {
                $webhook['failure_count']++;
            }
            
            $this->saveWebhook($webhook);
        }
    }
    
    /**
     * Notifica falha de webhook
     */
    private function notifyWebhookFailure($delivery) {
        $this->notificationManager->systemAlert(
            'Webhook Falhou',
            "Webhook {$delivery['webhook_id']} falhou após {$delivery['attempts']} tentativas. URL: {$delivery['webhook_url']}. Erro: {$delivery['last_error']}",
            NotificationManager::TYPE_WARNING
        );
    }
    
    /**
     * Lista webhooks
     */
    public function listWebhooks() {
        return $this->cache->get('webhooks_list', []);
    }
    
    /**
     * Obtém webhook específico
     */
    public function getWebhook($webhookId) {
        return $this->cache->get("webhook_{$webhookId}");
    }
    
    /**
     * Atualiza webhook
     */
    public function updateWebhook($webhookId, $updates) {
        $webhook = $this->getWebhook($webhookId);
        
        if (!$webhook) {
            throw new Exception("Webhook não encontrado");
        }
        
        // Campos permitidos para atualização
        $allowedFields = ['url', 'events', 'enabled', 'retry_attempts', 'timeout', 'headers'];
        
        foreach ($updates as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $webhook[$field] = $value;
            }
        }
        
        $this->saveWebhook($webhook);
        
        $this->logger->audit('webhook_updated', 'webhook', $webhookId, $updates);
        
        return $webhook;
    }
    
    /**
     * Remove webhook
     */
    public function deleteWebhook($webhookId) {
        $webhook = $this->getWebhook($webhookId);
        
        if (!$webhook) {
            throw new Exception("Webhook não encontrado");
        }
        
        // Remover webhook
        $this->cache->delete("webhook_{$webhookId}");
        
        // Remover da lista
        $webhooks = $this->cache->get('webhooks_list', []);
        $webhooks = array_filter($webhooks, function($item) use ($webhookId) {
            return $item['id'] !== $webhookId;
        });
        $this->cache->set('webhooks_list', array_values($webhooks), 86400 * 365);
        
        $this->logger->audit('webhook_deleted', 'webhook', $webhookId);
        
        return true;
    }
    
    /**
     * Testa webhook
     */
    public function testWebhook($webhookId) {
        $webhook = $this->getWebhook($webhookId);
        
        if (!$webhook) {
            throw new Exception("Webhook não encontrado");
        }
        
        $testPayload = [
            'event' => 'webhook.test',
            'timestamp' => time(),
            'data' => [
                'message' => 'Este é um teste de webhook',
                'webhook_id' => $webhookId
            ],
            'context' => [
                'test' => true,
                'initiated_by' => 'manual'
            ],
            'source' => [
                'application' => 'HSFA-Denuncias',
                'version' => Environment::get('APP_VERSION', '1.0.0'),
                'environment' => Environment::get('APP_ENV', 'production')
            ]
        ];
        
        $delivery = [
            'id' => uniqid('test_', true),
            'webhook_id' => $webhook['id'],
            'webhook_url' => $webhook['url'],
            'payload' => $testPayload,
            'signature' => $this->generateSignature($testPayload, $webhook['secret']),
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'max_attempts' => 1,
            'timeout' => $webhook['timeout'],
            'headers' => $webhook['headers'],
            'created_at' => time(),
            'next_attempt' => time()
        ];
        
        $this->processDelivery($delivery);
        
        return $this->cache->get("delivery_{$delivery['id']}");
    }
    
    /**
     * Obtém estatísticas de webhooks
     */
    public function getWebhookStats($webhookId = null) {
        if ($webhookId) {
            $webhook = $this->getWebhook($webhookId);
            
            if (!$webhook) {
                throw new Exception("Webhook não encontrado");
            }
            
            return [
                'id' => $webhook['id'],
                'url' => $webhook['url'],
                'enabled' => $webhook['enabled'],
                'success_count' => $webhook['success_count'],
                'failure_count' => $webhook['failure_count'],
                'last_sent' => $webhook['last_sent'],
                'success_rate' => $webhook['success_count'] + $webhook['failure_count'] > 0 ?
                    round(($webhook['success_count'] / ($webhook['success_count'] + $webhook['failure_count'])) * 100, 2) : 0
            ];
        }
        
        // Estatísticas globais
        $webhooks = $this->listWebhooks();
        $totalSuccess = 0;
        $totalFailure = 0;
        $activeWebhooks = 0;
        
        foreach ($webhooks as $webhook) {
            if ($webhook['enabled']) {
                $activeWebhooks++;
            }
            $totalSuccess += $webhook['success_count'];
            $totalFailure += $webhook['failure_count'];
        }
        
        return [
            'total_webhooks' => count($webhooks),
            'active_webhooks' => $activeWebhooks,
            'total_deliveries' => $totalSuccess + $totalFailure,
            'success_count' => $totalSuccess,
            'failure_count' => $totalFailure,
            'success_rate' => $totalSuccess + $totalFailure > 0 ?
                round(($totalSuccess / ($totalSuccess + $totalFailure)) * 100, 2) : 0
        ];
    }
    
    /**
     * Eventos pré-definidos do sistema
     */
    public function denunciaCreated($denunciaId, $protocolo, $denunciaData) {
        $this->fireEvent(self::EVENT_DENUNCIA_CREATED, [
            'denuncia_id' => $denunciaId,
            'protocolo' => $protocolo,
            'status' => $denunciaData['status'] ?? 'Pendente',
            'descricao' => substr($denunciaData['descricao'] ?? '', 0, 200),
            'data_criacao' => $denunciaData['data_criacao'] ?? date('c')
        ], [
            'action' => 'created',
            'entity_type' => 'denuncia'
        ]);
    }
    
    public function denunciaUpdated($denunciaId, $protocolo, $oldStatus, $newStatus, $adminId) {
        $this->fireEvent(self::EVENT_DENUNCIA_UPDATED, [
            'denuncia_id' => $denunciaId,
            'protocolo' => $protocolo,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'admin_id' => $adminId,
            'updated_at' => date('c')
        ], [
            'action' => 'status_changed',
            'entity_type' => 'denuncia'
        ]);
    }
    
    public function loginFailed($username, $ip, $userAgent) {
        $this->fireEvent(self::EVENT_LOGIN_FAILED, [
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => date('c')
        ], [
            'action' => 'authentication_failed',
            'security_event' => true
        ]);
    }
    
    public function backupCompleted($backupId, $backupType, $size, $duration) {
        $this->fireEvent(self::EVENT_BACKUP_COMPLETED, [
            'backup_id' => $backupId,
            'backup_type' => $backupType,
            'size_bytes' => $size,
            'duration_seconds' => $duration,
            'completed_at' => date('c')
        ], [
            'action' => 'backup_completed',
            'entity_type' => 'backup'
        ]);
    }
}
