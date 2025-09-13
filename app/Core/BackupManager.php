<?php
/**
 * Gerenciador de backups automatizados
 * Suporta backup de banco de dados, arquivos e configurações
 */

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/NotificationManager.php';

class BackupManager {
    private static $instance = null;
    private $logger;
    private $cache;
    private $notificationManager;
    private $backupDir;
    
    // Tipos de backup
    const TYPE_FULL = 'full';
    const TYPE_DATABASE = 'database';
    const TYPE_FILES = 'files';
    const TYPE_CONFIG = 'config';
    
    // Status de backup
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->cache = Cache::getInstance();
        $this->notificationManager = NotificationManager::getInstance();
        $this->backupDir = BASE_PATH . '/storage/backups';
        
        $this->ensureBackupDirectory();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Garante que o diretório de backup existe
     */
    private function ensureBackupDirectory() {
        if (!is_dir($this->backupDir)) {
            if (!mkdir($this->backupDir, 0750, true)) {
                throw new Exception("Não foi possível criar diretório de backup: {$this->backupDir}");
            }
        }
        
        // Criar .htaccess para proteger backups
        $htaccessPath = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Require all denied\n");
        }
    }
    
    /**
     * Cria backup completo
     */
    public function createBackup($type = self::TYPE_FULL, $options = []) {
        $backupId = uniqid('backup_', true);
        $timestamp = date('Y-m-d_H-i-s');
        
        $backup = [
            'id' => $backupId,
            'type' => $type,
            'status' => self::STATUS_PENDING,
            'started_at' => time(),
            'completed_at' => null,
            'size' => 0,
            'files' => [],
            'options' => $options,
            'error' => null
        ];
        
        // Salvar status inicial
        $this->saveBackupStatus($backup);
        
        try {
            $this->logger->info('Backup iniciado', [
                'backup_id' => $backupId,
                'type' => $type,
                'options' => $options
            ]);
            
            // Marcar como executando
            $backup['status'] = self::STATUS_RUNNING;
            $this->saveBackupStatus($backup);
            
            // Executar backup baseado no tipo
            switch ($type) {
                case self::TYPE_FULL:
                    $this->createFullBackup($backup);
                    break;
                    
                case self::TYPE_DATABASE:
                    $this->createDatabaseBackup($backup);
                    break;
                    
                case self::TYPE_FILES:
                    $this->createFilesBackup($backup);
                    break;
                    
                case self::TYPE_CONFIG:
                    $this->createConfigBackup($backup);
                    break;
                    
                default:
                    throw new Exception("Tipo de backup inválido: {$type}");
            }
            
            // Marcar como concluído
            $backup['status'] = self::STATUS_COMPLETED;
            $backup['completed_at'] = time();
            
            $this->saveBackupStatus($backup);
            $this->notifyBackupCompleted($backup);
            
            $this->logger->info('Backup concluído', [
                'backup_id' => $backupId,
                'duration' => $backup['completed_at'] - $backup['started_at'],
                'size' => $backup['size'],
                'files' => count($backup['files'])
            ]);
            
            return $backup;
            
        } catch (Exception $e) {
            $backup['status'] = self::STATUS_FAILED;
            $backup['error'] = $e->getMessage();
            $backup['completed_at'] = time();
            
            $this->saveBackupStatus($backup);
            $this->notifyBackupFailed($backup);
            
            $this->logger->error('Backup falhou', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Cria backup completo (banco + arquivos + config)
     */
    private function createFullBackup(&$backup) {
        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = $this->backupDir . "/full_backup_{$timestamp}";
        
        if (!mkdir($backupPath, 0750, true)) {
            throw new Exception("Não foi possível criar diretório: {$backupPath}");
        }
        
        // Backup do banco de dados
        $dbBackupPath = $backupPath . '/database.sql';
        $this->dumpDatabase($dbBackupPath);
        $backup['files'][] = $dbBackupPath;
        
        // Backup dos arquivos de upload
        $uploadsBackupPath = $backupPath . '/uploads';
        $this->backupDirectory(BASE_PATH . '/public/uploads', $uploadsBackupPath);
        $backup['files'][] = $uploadsBackupPath;
        
        // Backup das configurações
        $configBackupPath = $backupPath . '/config';
        $this->backupConfigurations($configBackupPath);
        $backup['files'][] = $configBackupPath;
        
        // Backup dos logs
        $logsBackupPath = $backupPath . '/logs';
        $this->backupDirectory(BASE_PATH . '/storage/logs', $logsBackupPath);
        $backup['files'][] = $logsBackupPath;
        
        // Criar arquivo de manifesto
        $manifest = [
            'backup_id' => $backup['id'],
            'created_at' => date('c'),
            'type' => $backup['type'],
            'version' => Environment::get('APP_VERSION', '1.0.0'),
            'environment' => Environment::get('APP_ENV', 'production'),
            'files' => $backup['files'],
            'checksum' => $this->calculateDirectoryChecksum($backupPath)
        ];
        
        $manifestPath = $backupPath . '/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        $backup['files'][] = $manifestPath;
        
        // Comprimir se solicitado
        if ($backup['options']['compress'] ?? true) {
            $archivePath = $backupPath . '.tar.gz';
            $this->compressDirectory($backupPath, $archivePath);
            
            // Remover diretório original e manter apenas o arquivo
            $this->removeDirectory($backupPath);
            $backup['files'] = [$archivePath];
        }
        
        $backup['size'] = $this->calculateBackupSize($backup['files']);
    }
    
    /**
     * Cria backup apenas do banco de dados
     */
    private function createDatabaseBackup(&$backup) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "database_backup_{$timestamp}.sql";
        $backupPath = $this->backupDir . '/' . $filename;
        
        $this->dumpDatabase($backupPath);
        
        // Comprimir se solicitado
        if ($backup['options']['compress'] ?? true) {
            $compressedPath = $backupPath . '.gz';
            $this->compressFile($backupPath, $compressedPath);
            unlink($backupPath);
            $backup['files'] = [$compressedPath];
        } else {
            $backup['files'] = [$backupPath];
        }
        
        $backup['size'] = $this->calculateBackupSize($backup['files']);
    }
    
    /**
     * Cria backup dos arquivos
     */
    private function createFilesBackup(&$backup) {
        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = $this->backupDir . "/files_backup_{$timestamp}";
        
        if (!mkdir($backupPath, 0750, true)) {
            throw new Exception("Não foi possível criar diretório: {$backupPath}");
        }
        
        // Backup dos uploads
        $uploadsBackupPath = $backupPath . '/uploads';
        $this->backupDirectory(BASE_PATH . '/public/uploads', $uploadsBackupPath);
        
        // Backup dos logs
        $logsBackupPath = $backupPath . '/logs';
        $this->backupDirectory(BASE_PATH . '/storage/logs', $logsBackupPath);
        
        $backup['files'] = [$backupPath];
        
        // Comprimir se solicitado
        if ($backup['options']['compress'] ?? true) {
            $archivePath = $backupPath . '.tar.gz';
            $this->compressDirectory($backupPath, $archivePath);
            $this->removeDirectory($backupPath);
            $backup['files'] = [$archivePath];
        }
        
        $backup['size'] = $this->calculateBackupSize($backup['files']);
    }
    
    /**
     * Cria backup das configurações
     */
    private function createConfigBackup(&$backup) {
        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = $this->backupDir . "/config_backup_{$timestamp}";
        
        if (!mkdir($backupPath, 0750, true)) {
            throw new Exception("Não foi possível criar diretório: {$backupPath}");
        }
        
        $this->backupConfigurations($backupPath);
        
        $backup['files'] = [$backupPath];
        
        // Comprimir sempre para configs
        $archivePath = $backupPath . '.tar.gz';
        $this->compressDirectory($backupPath, $archivePath);
        $this->removeDirectory($backupPath);
        $backup['files'] = [$archivePath];
        
        $backup['size'] = $this->calculateBackupSize($backup['files']);
    }
    
    /**
     * Faz dump do banco de dados
     */
    private function dumpDatabase($outputPath) {
        $mysqldumpPath = Environment::get('MYSQLDUMP_PATH', 'mysqldump');
        
        // Criar comando com melhor tratamento de erros e mais opções
        $command = sprintf(
            '%s --single-transaction --routines --triggers --add-drop-table --lock-tables=false --complete-insert --host=%s --user=%s --password=%s %s 2>&1',
            escapeshellcmd($mysqldumpPath),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME)
        );
        
        $output = [];
        $returnCode = 0;
        
        // Executar comando e capturar saída
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $errorMsg = "Erro no mysqldump (código {$returnCode}): " . implode("\n", $output);
            $this->logger->error($errorMsg);
            throw new Exception($errorMsg);
        }
        
        // Salvar saída no arquivo
        $sqlContent = implode("\n", $output);
        
        if (empty($sqlContent) || strlen($sqlContent) < 100) {
            throw new Exception("Backup do banco de dados está vazio ou muito pequeno");
        }
        
        // Adicionar cabeçalho com informações do backup
        $header = "-- Backup gerado em: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- Banco: " . DB_NAME . "\n";
        $header .= "-- Host: " . DB_HOST . "\n";
        $header .= "-- Versão: " . Environment::get('APP_VERSION', '1.0.0') . "\n";
        $header .= "-- =====================================================\n\n";
        
        $fullContent = $header . $sqlContent;
        
        if (file_put_contents($outputPath, $fullContent) === false) {
            throw new Exception("Erro ao salvar backup no arquivo: {$outputPath}");
        }
        
        // Verificar se arquivo foi criado corretamente
        if (!file_exists($outputPath) || filesize($outputPath) < 200) {
            throw new Exception("Backup do banco de dados falhou ou está vazio");
        }
        
        $this->logger->info('Database backup created successfully', [
            'file' => $outputPath,
            'size' => filesize($outputPath)
        ]);
    }
    
    /**
     * Backup de diretório
     */
    private function backupDirectory($sourceDir, $backupDir) {
        if (!is_dir($sourceDir)) {
            return;
        }
        
        if (!mkdir($backupDir, 0750, true)) {
            throw new Exception("Não foi possível criar diretório: {$backupDir}");
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $backupDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!mkdir($target, 0750, true)) {
                    throw new Exception("Erro ao criar diretório: {$target}");
                }
            } else {
                if (!copy($item->getRealPath(), $target)) {
                    throw new Exception("Erro ao copiar arquivo: {$item->getRealPath()}");
                }
            }
        }
    }
    
    /**
     * Backup das configurações
     */
    private function backupConfigurations($backupPath) {
        $configFiles = [
            BASE_PATH . '/config/config.php',
            BASE_PATH . '/config/email.php',
            BASE_PATH . '/.env',
            BASE_PATH . '/.htaccess',
            BASE_PATH . '/composer.json',
            BASE_PATH . '/composer.lock'
        ];
        
        foreach ($configFiles as $file) {
            if (file_exists($file)) {
                $basename = basename($file);
                $target = $backupPath . '/' . $basename;
                
                if (!copy($file, $target)) {
                    $this->logger->warning("Não foi possível fazer backup do arquivo: {$file}");
                }
            }
        }
        
        // Backup das configurações do banco
        $this->backupDatabaseConfig($backupPath);
    }
    
    /**
     * Backup das configurações do banco
     */
    private function backupDatabaseConfig($backupPath) {
        try {
            $conn = Database::getInstance()->getConnection();
            
            // Exportar estrutura das tabelas
            $tables = [];
            $result = $conn->query("SHOW TABLES");
            
            while ($row = $result->fetch_array()) {
                $tableName = $row[0];
                $tables[] = $tableName;
                
                // Obter CREATE TABLE
                $createResult = $conn->query("SHOW CREATE TABLE `{$tableName}`");
                $createRow = $createResult->fetch_array();
                
                file_put_contents(
                    $backupPath . "/table_{$tableName}.sql",
                    $createRow[1] . ";\n",
                    FILE_APPEND
                );
            }
            
            // Salvar lista de tabelas
            file_put_contents(
                $backupPath . '/tables.json',
                json_encode($tables, JSON_PRETTY_PRINT)
            );
            
        } catch (Exception $e) {
            $this->logger->error("Erro ao fazer backup da configuração do banco: " . $e->getMessage());
        }
    }
    
    /**
     * Comprime diretório
     */
    private function compressDirectory($sourceDir, $outputPath) {
        if (!class_exists('PharData')) {
            throw new Exception("Extensão Phar não disponível para compressão");
        }
        
        try {
            $phar = new PharData($outputPath);
            $phar->buildFromDirectory($sourceDir);
            
            if (!file_exists($outputPath)) {
                throw new Exception("Arquivo comprimido não foi criado");
            }
            
        } catch (Exception $e) {
            // Fallback para tar se disponível
            if ($this->isCommandAvailable('tar')) {
                $command = sprintf(
                    'tar -czf %s -C %s .',
                    escapeshellarg($outputPath),
                    escapeshellarg($sourceDir)
                );
                
                exec($command . ' 2>&1', $output, $returnCode);
                
                if ($returnCode !== 0) {
                    throw new Exception("Erro na compressão: " . implode("\n", $output));
                }
            } else {
                throw new Exception("Nenhum método de compressão disponível");
            }
        }
    }
    
    /**
     * Comprime arquivo único
     */
    private function compressFile($sourceFile, $outputPath) {
        if (function_exists('gzencode')) {
            $content = file_get_contents($sourceFile);
            $compressed = gzencode($content, 9);
            
            if (file_put_contents($outputPath, $compressed) === false) {
                throw new Exception("Erro ao criar arquivo comprimido");
            }
        } else {
            throw new Exception("Função gzencode não disponível");
        }
    }
    
    /**
     * Remove diretório recursivamente
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $action($fileinfo->getRealPath());
        }
        
        rmdir($dir);
    }
    
    /**
     * Calcula tamanho do backup
     */
    private function calculateBackupSize($files) {
        $totalSize = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            } elseif (is_dir($file)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($file, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile()) {
                        $totalSize += $fileInfo->getSize();
                    }
                }
            }
        }
        
        return $totalSize;
    }
    
    /**
     * Calcula checksum do diretório
     */
    private function calculateDirectoryChecksum($dir) {
        $checksums = [];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getRealPath(), strlen($dir) + 1);
                $checksums[$relativePath] = md5_file($file->getRealPath());
            }
        }
        
        ksort($checksums);
        return md5(serialize($checksums));
    }
    
    /**
     * Verifica se comando está disponível
     */
    private function isCommandAvailable($command) {
        $output = [];
        $returnCode = 0;
        
        exec("which {$command} 2>/dev/null", $output, $returnCode);
        
        return $returnCode === 0;
    }
    
    /**
     * Salva status do backup
     */
    private function saveBackupStatus($backup) {
        $this->cache->set("backup_{$backup['id']}", $backup, 86400 * 7); // 7 dias
        
        // Adicionar à lista de backups
        $backupList = $this->cache->get('backup_list', []);
        
        // Atualizar ou adicionar
        $found = false;
        foreach ($backupList as &$item) {
            if ($item['id'] === $backup['id']) {
                $item = $backup;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            array_unshift($backupList, $backup);
        }
        
        // Manter apenas os últimos 50 backups na lista
        $backupList = array_slice($backupList, 0, 50);
        
        $this->cache->set('backup_list', $backupList, 86400 * 7);
    }
    
    /**
     * Lista backups
     */
    public function listBackups($limit = 20) {
        $backupList = $this->cache->get('backup_list', []);
        return array_slice($backupList, 0, $limit);
    }
    
    /**
     * Obtém detalhes de um backup
     */
    public function getBackup($backupId) {
        return $this->cache->get("backup_{$backupId}");
    }
    
    /**
     * Remove backup
     */
    public function deleteBackup($backupId) {
        $backup = $this->getBackup($backupId);
        
        if (!$backup) {
            throw new Exception("Backup não encontrado");
        }
        
        // Remover arquivos
        foreach ($backup['files'] as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                $this->removeDirectory($file);
            }
        }
        
        // Remover do cache
        $this->cache->delete("backup_{$backupId}");
        
        // Remover da lista
        $backupList = $this->cache->get('backup_list', []);
        $backupList = array_filter($backupList, function($item) use ($backupId) {
            return $item['id'] !== $backupId;
        });
        $this->cache->set('backup_list', $backupList, 86400 * 7);
        
        $this->logger->audit('backup_deleted', 'backup', $backupId);
        
        return true;
    }
    
    /**
     * Limpa backups antigos
     */
    public function cleanup($maxAge = 2592000) { // 30 dias padrão
        $cutoff = time() - $maxAge;
        $backupList = $this->cache->get('backup_list', []);
        $cleaned = 0;
        
        foreach ($backupList as $backup) {
            if ($backup['started_at'] < $cutoff) {
                try {
                    $this->deleteBackup($backup['id']);
                    $cleaned++;
                } catch (Exception $e) {
                    $this->logger->error("Erro ao limpar backup {$backup['id']}: " . $e->getMessage());
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Agenda backup automático
     */
    public function scheduleBackup($type, $schedule, $options = []) {
        $scheduledBackup = [
            'id' => uniqid('schedule_', true),
            'type' => $type,
            'schedule' => $schedule, // cron format
            'options' => $options,
            'enabled' => true,
            'last_run' => null,
            'next_run' => $this->calculateNextRun($schedule),
            'created_at' => time()
        ];
        
        $schedules = $this->cache->get('backup_schedules', []);
        $schedules[] = $scheduledBackup;
        $this->cache->set('backup_schedules', $schedules, 86400 * 365); // 1 ano
        
        return $scheduledBackup['id'];
    }
    
    /**
     * Executa backups agendados
     */
    public function runScheduledBackups() {
        $schedules = $this->cache->get('backup_schedules', []);
        $now = time();
        $executed = 0;
        
        foreach ($schedules as &$schedule) {
            if ($schedule['enabled'] && $schedule['next_run'] <= $now) {
                try {
                    $this->createBackup($schedule['type'], $schedule['options']);
                    
                    $schedule['last_run'] = $now;
                    $schedule['next_run'] = $this->calculateNextRun($schedule['schedule'], $now);
                    
                    $executed++;
                    
                } catch (Exception $e) {
                    $this->logger->error("Erro no backup agendado {$schedule['id']}: " . $e->getMessage());
                }
            }
        }
        
        if ($executed > 0) {
            $this->cache->set('backup_schedules', $schedules, 86400 * 365);
        }
        
        return $executed;
    }
    
    /**
     * Calcula próxima execução (simplificado)
     */
    private function calculateNextRun($schedule, $from = null) {
        $from = $from ?: time();
        
        // Implementação simplificada - em produção usar biblioteca cron
        switch ($schedule) {
            case 'daily':
                return $from + 86400;
            case 'weekly':
                return $from + (86400 * 7);
            case 'monthly':
                return $from + (86400 * 30);
            default:
                return $from + 86400; // Padrão diário
        }
    }
    
    /**
     * Notifica backup concluído
     */
    private function notifyBackupCompleted($backup) {
        $size = $this->formatBytes($backup['size']);
        $duration = $backup['completed_at'] - $backup['started_at'];
        
        $this->notificationManager->create(
            'Backup Concluído',
            "Backup {$backup['type']} concluído com sucesso. Tamanho: {$size}, Duração: {$duration}s",
            NotificationManager::TYPE_SUCCESS,
            [
                'role' => 'admin',
                'channels' => [NotificationManager::CHANNEL_BROWSER],
                'data' => ['backup_id' => $backup['id']]
            ]
        );
    }
    
    /**
     * Notifica backup falhou
     */
    private function notifyBackupFailed($backup) {
        $this->notificationManager->create(
            'Backup Falhou',
            "Backup {$backup['type']} falhou: {$backup['error']}",
            NotificationManager::TYPE_ERROR,
            [
                'role' => 'admin',
                'channels' => [NotificationManager::CHANNEL_BROWSER, NotificationManager::CHANNEL_EMAIL],
                'data' => ['backup_id' => $backup['id']]
            ]
        );
    }
    
    /**
     * Formata bytes
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
