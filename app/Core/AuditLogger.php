<?php
/**
 * Sistema de auditoria para operações críticas
 * Registra todas as operações de DELETE, UPDATE e outras ações sensíveis
 */

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Database.php';

class AuditLogger {
    private static $instance = null;
    private $logger;
    private $conn;
    
    // Tipos de operação
    const OP_INSERT = 'INSERT';
    const OP_UPDATE = 'UPDATE';
    const OP_DELETE = 'DELETE';
    const OP_LOGIN = 'LOGIN';
    const OP_LOGOUT = 'LOGOUT';
    const OP_ACCESS = 'ACCESS';
    const OP_BACKUP = 'BACKUP';
    const OP_CONFIG = 'CONFIG';
    
    // Níveis de criticidade
    const LEVEL_LOW = 'low';
    const LEVEL_MEDIUM = 'medium';
    const LEVEL_HIGH = 'high';
    const LEVEL_CRITICAL = 'critical';
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->conn = Database::getInstance()->getConnection();
        $this->ensureAuditTable();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Garante que a tabela de auditoria existe
     */
    private function ensureAuditTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS audit_log (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    user_name VARCHAR(100) NULL,
                    operation VARCHAR(20) NOT NULL,
                    table_name VARCHAR(100) NULL,
                    record_id VARCHAR(100) NULL,
                    level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                    description TEXT NOT NULL,
                    old_data JSON NULL,
                    new_data JSON NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    request_uri VARCHAR(500) NULL,
                    session_id VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_operation (operation),
                    INDEX idx_table_name (table_name),
                    INDEX idx_level (level),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->conn->query($sql);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to create audit table', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Registra uma operação de auditoria
     */
    public function log($operation, $description, $options = []) {
        try {
            $userId = $this->getCurrentUserId();
            $userName = $this->getCurrentUserName();
            
            $auditData = [
                'user_id' => $userId,
                'user_name' => $userName,
                'operation' => strtoupper($operation),
                'table_name' => $options['table'] ?? null,
                'record_id' => $options['record_id'] ?? null,
                'level' => $options['level'] ?? self::LEVEL_MEDIUM,
                'description' => $description,
                'old_data' => isset($options['old_data']) ? json_encode($options['old_data']) : null,
                'new_data' => isset($options['new_data']) ? json_encode($options['new_data']) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'session_id' => session_id() ?: null
            ];
            
            // Salvar no banco de dados
            $this->saveToDatabase($auditData);
            
            // Log estruturado para arquivo
            $this->logger->audit($operation, $auditData['table_name'], $auditData['record_id'], [
                'description' => $description,
                'level' => $auditData['level'],
                'user_name' => $userName,
                'old_data' => $options['old_data'] ?? null,
                'new_data' => $options['new_data'] ?? null
            ]);
            
            // Para operações críticas, enviar notificação
            if ($auditData['level'] === self::LEVEL_CRITICAL) {
                $this->sendCriticalAlert($auditData);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Audit logging failed', [
                'error' => $e->getMessage(),
                'operation' => $operation,
                'description' => $description
            ]);
            return false;
        }
    }
    
    /**
     * Salva dados de auditoria no banco
     */
    private function saveToDatabase($auditData) {
        $sql = "
            INSERT INTO audit_log (
                user_id, user_name, operation, table_name, record_id, 
                level, description, old_data, new_data, ip_address, 
                user_agent, request_uri, session_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param(
            'isssssssssss',
            $auditData['user_id'],
            $auditData['user_name'],
            $auditData['operation'],
            $auditData['table_name'],
            $auditData['record_id'],
            $auditData['level'],
            $auditData['description'],
            $auditData['old_data'],
            $auditData['new_data'],
            $auditData['ip_address'],
            $auditData['user_agent'],
            $auditData['request_uri'],
            $auditData['session_id']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Métodos específicos para diferentes tipos de operação
     */
    
    public function logDelete($table, $recordId, $oldData, $description = null) {
        $desc = $description ?: "Registro deletado da tabela {$table}";
        
        return $this->log(self::OP_DELETE, $desc, [
            'table' => $table,
            'record_id' => $recordId,
            'level' => self::LEVEL_HIGH,
            'old_data' => $oldData
        ]);
    }
    
    public function logUpdate($table, $recordId, $oldData, $newData, $description = null) {
        $desc = $description ?: "Registro atualizado na tabela {$table}";
        
        return $this->log(self::OP_UPDATE, $desc, [
            'table' => $table,
            'record_id' => $recordId,
            'level' => self::LEVEL_MEDIUM,
            'old_data' => $oldData,
            'new_data' => $newData
        ]);
    }
    
    public function logInsert($table, $recordId, $newData, $description = null) {
        $desc = $description ?: "Novo registro criado na tabela {$table}";
        
        return $this->log(self::OP_INSERT, $desc, [
            'table' => $table,
            'record_id' => $recordId,
            'level' => self::LEVEL_LOW,
            'new_data' => $newData
        ]);
    }
    
    public function logLogin($success = true, $username = null) {
        $desc = $success ? "Login realizado com sucesso" : "Tentativa de login falhada";
        $level = $success ? self::LEVEL_LOW : self::LEVEL_MEDIUM;
        
        if ($username) {
            $desc .= " para usuário: {$username}";
        }
        
        return $this->log(self::OP_LOGIN, $desc, [
            'level' => $level
        ]);
    }
    
    public function logLogout() {
        return $this->log(self::OP_LOGOUT, "Logout realizado", [
            'level' => self::LEVEL_LOW
        ]);
    }
    
    public function logCriticalAccess($resource, $description = null) {
        $desc = $description ?: "Acesso a recurso crítico: {$resource}";
        
        return $this->log(self::OP_ACCESS, $desc, [
            'level' => self::LEVEL_CRITICAL
        ]);
    }
    
    public function logBackup($type, $success = true, $details = []) {
        $desc = $success ? 
            "Backup {$type} realizado com sucesso" : 
            "Backup {$type} falhou";
        
        return $this->log(self::OP_BACKUP, $desc, [
            'level' => $success ? self::LEVEL_LOW : self::LEVEL_HIGH,
            'new_data' => $details
        ]);
    }
    
    public function logConfigChange($setting, $oldValue, $newValue) {
        $desc = "Configuração alterada: {$setting}";
        
        return $this->log(self::OP_CONFIG, $desc, [
            'level' => self::LEVEL_HIGH,
            'old_data' => ['setting' => $setting, 'value' => $oldValue],
            'new_data' => ['setting' => $setting, 'value' => $newValue]
        ]);
    }
    
    /**
     * Consulta logs de auditoria
     */
    public function getAuditLogs($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM audit_log WHERE 1=1";
        $params = [];
        $types = "";
        
        // Aplicar filtros
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        
        if (!empty($filters['operation'])) {
            $sql .= " AND operation = ?";
            $params[] = strtoupper($filters['operation']);
            $types .= "s";
        }
        
        if (!empty($filters['table_name'])) {
            $sql .= " AND table_name = ?";
            $params[] = $filters['table_name'];
            $types .= "s";
        }
        
        if (!empty($filters['level'])) {
            $sql .= " AND level = ?";
            $params[] = $filters['level'];
            $types .= "s";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Envia alerta para operações críticas
     */
    private function sendCriticalAlert($auditData) {
        try {
            // Log crítico
            $this->logger->critical('Critical operation detected', $auditData);
            
            // Se existe NotificationManager, enviar notificação
            if (class_exists('NotificationManager')) {
                $notificationManager = NotificationManager::getInstance();
                $notificationManager->create(
                    'Operação Crítica Detectada',
                    "Operação crítica: {$auditData['operation']} - {$auditData['description']}",
                    'error',
                    [
                        'role' => 'admin',
                        'channels' => ['browser', 'email'],
                        'data' => $auditData
                    ]
                );
            }
            
        } catch (Exception $e) {
            $this->logger->error('Failed to send critical alert', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtém ID do usuário atual
     */
    private function getCurrentUserId() {
        return $_SESSION['user']['id'] ?? $_SESSION['admin']['id'] ?? null;
    }
    
    /**
     * Obtém nome do usuário atual
     */
    private function getCurrentUserName() {
        return $_SESSION['user']['nome'] ?? 
               $_SESSION['user']['usuario'] ?? 
               $_SESSION['admin']['nome'] ?? 
               $_SESSION['admin']['usuario'] ?? 
               'sistema';
    }
    
    /**
     * Limpa logs antigos
     */
    public function cleanup($daysToKeep = 365) {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
            
            $sql = "DELETE FROM audit_log WHERE created_at < ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $cutoffDate);
            $stmt->execute();
            
            $deletedRows = $stmt->affected_rows;
            
            $this->logger->info('Audit log cleanup completed', [
                'deleted_rows' => $deletedRows,
                'cutoff_date' => $cutoffDate
            ]);
            
            return $deletedRows;
            
        } catch (Exception $e) {
            $this->logger->error('Audit log cleanup failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Obtém estatísticas de auditoria
     */
    public function getStats($days = 30) {
        try {
            $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            $sql = "
                SELECT 
                    operation,
                    level,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM audit_log 
                WHERE created_at >= ?
                GROUP BY operation, level
                ORDER BY count DESC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $since);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to get audit stats', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
