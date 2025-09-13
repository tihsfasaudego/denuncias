<?php
/**
 * Ferramentas de recuperação de dados
 * Permite restaurar dados a partir de backups
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/BackupManager.php';
require_once __DIR__ . '/../app/Core/Logger.php';
require_once __DIR__ . '/../app/Core/Database.php';

class RecoveryTools {
    private $backupManager;
    private $logger;
    private $conn;
    
    public function __construct() {
        $this->backupManager = BackupManager::getInstance();
        $this->logger = Logger::getInstance();
        $this->conn = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista backups disponíveis para recuperação
     */
    public function listAvailableBackups() {
        echo "=== BACKUPS DISPONÍVEIS PARA RECUPERAÇÃO ===\n\n";
        
        try {
            $backups = $this->backupManager->listBackups(50);
            
            if (empty($backups)) {
                echo "❌ Nenhum backup encontrado!\n";
                echo "Execute backups antes de tentar recuperar dados.\n";
                return;
            }
            
            echo "ID                        | Tipo     | Status      | Data/Hora           | Tamanho\n";
            echo "--------------------------|----------|-------------|---------------------|----------\n";
            
            foreach ($backups as $backup) {
                $date = date('Y-m-d H:i:s', $backup['started_at']);
                $status = $backup['status'] === 'completed' ? '✅ Completo' : '❌ Falhou';
                $size = isset($backup['size']) ? $this->formatBytes($backup['size']) : 'N/A';
                $type = str_pad($backup['type'], 8);
                
                printf("%-25s | %-8s | %-11s | %-19s | %s\n",
                    substr($backup['id'], 0, 25),
                    $type,
                    $status,
                    $date,
                    $size
                );
            }
            
            echo "\nPara recuperar, use: php recovery_tools.php restore <backup_id>\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Restaura backup específico
     */
    public function restoreBackup($backupId, $options = []) {
        echo "=== RESTAURAÇÃO DE BACKUP ===\n";
        echo "Backup ID: {$backupId}\n\n";
        
        try {
            $backup = $this->backupManager->getBackup($backupId);
            
            if (!$backup) {
                echo "❌ Backup não encontrado!\n";
                exit(1);
            }
            
            if ($backup['status'] !== 'completed') {
                echo "❌ Backup não está completo! Status: {$backup['status']}\n";
                exit(1);
            }
            
            echo "Tipo: {$backup['type']}\n";
            echo "Criado em: " . date('Y-m-d H:i:s', $backup['started_at']) . "\n";
            echo "Tamanho: " . $this->formatBytes($backup['size']) . "\n";
            echo "Arquivos: " . count($backup['files']) . "\n\n";
            
            // Confirmar restauração
            if (!($options['force'] ?? false)) {
                echo "⚠️  ATENÇÃO: Esta operação irá SUBSTITUIR os dados atuais!\n";
                echo "Tem certeza que deseja continuar? (digite 'SIM' para confirmar): ";
                
                $confirmation = trim(fgets(STDIN));
                
                if ($confirmation !== 'SIM') {
                    echo "Operação cancelada.\n";
                    exit(0);
                }
            }
            
            echo "\n🔄 Iniciando restauração...\n\n";
            
            // Criar backup de segurança antes da restauração
            if (!($options['no_safety_backup'] ?? false)) {
                echo "1. Criando backup de segurança...\n";
                $safetyBackup = $this->createSafetyBackup();
                echo "   ✅ Backup de segurança criado: {$safetyBackup['id']}\n\n";
            }
            
            // Executar restauração baseada no tipo
            switch ($backup['type']) {
                case 'database':
                    $this->restoreDatabase($backup);
                    break;
                    
                case 'full':
                    $this->restoreFullBackup($backup);
                    break;
                    
                case 'files':
                    $this->restoreFiles($backup);
                    break;
                    
                case 'config':
                    $this->restoreConfig($backup);
                    break;
                    
                default:
                    throw new Exception("Tipo de backup não suportado: {$backup['type']}");
            }
            
            echo "\n✅ Restauração concluída com sucesso!\n";
            
            // Log de auditoria
            require_once __DIR__ . '/../Core/AuditLogger.php';
            $auditLogger = AuditLogger::getInstance();
            $auditLogger->log(
                'RESTORE',
                "Backup restaurado: {$backup['type']} - {$backupId}",
                [
                    'level' => AuditLogger::LEVEL_CRITICAL,
                    'old_data' => ['backup_id' => $backupId],
                    'new_data' => $backup
                ]
            );
            
        } catch (Exception $e) {
            echo "❌ ERRO na restauração: " . $e->getMessage() . "\n";
            $this->logger->error('Backup restoration failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage()
            ]);
            exit(1);
        }
    }
    
    /**
     * Cria backup de segurança antes da restauração
     */
    private function createSafetyBackup() {
        $safetyId = 'safety_' . uniqid();
        
        return $this->backupManager->createBackup('database', [
            'compress' => true,
            'description' => 'Backup de segurança antes da restauração',
            'safety_backup' => true
        ]);
    }
    
    /**
     * Restaura backup de banco de dados
     */
    private function restoreDatabase($backup) {
        echo "2. Restaurando banco de dados...\n";
        
        foreach ($backup['files'] as $file) {
            if (str_ends_with($file, '.sql') || str_ends_with($file, '.sql.gz')) {
                echo "   Processando: " . basename($file) . "\n";
                
                $sqlContent = $this->extractSqlContent($file);
                
                if (empty($sqlContent)) {
                    throw new Exception("Arquivo SQL vazio ou inválido: {$file}");
                }
                
                $this->executeSqlRestore($sqlContent);
                echo "   ✅ Banco de dados restaurado\n";
                
                return;
            }
        }
        
        throw new Exception("Nenhum arquivo SQL encontrado no backup");
    }
    
    /**
     * Restaura backup completo
     */
    private function restoreFullBackup($backup) {
        echo "2. Restaurando backup completo...\n";
        
        $extractDir = $this->extractBackupFiles($backup);
        
        try {
            // Restaurar banco de dados
            echo "   2.1. Restaurando banco de dados...\n";
            $dbFile = $extractDir . '/database.sql';
            if (file_exists($dbFile)) {
                $sqlContent = file_get_contents($dbFile);
                $this->executeSqlRestore($sqlContent);
                echo "   ✅ Banco de dados restaurado\n";
            }
            
            // Restaurar arquivos
            echo "   2.2. Restaurando arquivos...\n";
            $uploadsDir = $extractDir . '/uploads';
            if (is_dir($uploadsDir)) {
                $this->restoreDirectory($uploadsDir, BASE_PATH . '/public/uploads');
                echo "   ✅ Arquivos de upload restaurados\n";
            }
            
            // Restaurar configurações
            echo "   2.3. Restaurando configurações...\n";
            $configDir = $extractDir . '/config';
            if (is_dir($configDir)) {
                $this->restoreConfigurations($configDir);
                echo "   ✅ Configurações restauradas\n";
            }
            
        } finally {
            // Limpar diretório temporário
            $this->removeDirectory($extractDir);
        }
    }
    
    /**
     * Restaura apenas arquivos
     */
    private function restoreFiles($backup) {
        echo "2. Restaurando arquivos...\n";
        
        $extractDir = $this->extractBackupFiles($backup);
        
        try {
            // Restaurar uploads
            $uploadsDir = $extractDir . '/uploads';
            if (is_dir($uploadsDir)) {
                $this->restoreDirectory($uploadsDir, BASE_PATH . '/public/uploads');
                echo "   ✅ Uploads restaurados\n";
            }
            
            // Restaurar logs se existirem
            $logsDir = $extractDir . '/logs';
            if (is_dir($logsDir)) {
                $this->restoreDirectory($logsDir, BASE_PATH . '/storage/logs');
                echo "   ✅ Logs restaurados\n";
            }
            
        } finally {
            $this->removeDirectory($extractDir);
        }
    }
    
    /**
     * Restaura configurações
     */
    private function restoreConfig($backup) {
        echo "2. Restaurando configurações...\n";
        
        $extractDir = $this->extractBackupFiles($backup);
        
        try {
            $this->restoreConfigurations($extractDir);
            echo "   ✅ Configurações restauradas\n";
            
        } finally {
            $this->removeDirectory($extractDir);
        }
    }
    
    /**
     * Extrai conteúdo SQL de arquivo (comprimido ou não)
     */
    private function extractSqlContent($file) {
        if (str_ends_with($file, '.gz')) {
            $content = gzdecode(file_get_contents($file));
        } else {
            $content = file_get_contents($file);
        }
        
        return $content;
    }
    
    /**
     * Executa restauração SQL
     */
    private function executeSqlRestore($sqlContent) {
        // Dividir em comandos individuais
        $commands = array_filter(
            array_map('trim', explode(';', $sqlContent)),
            function($cmd) { return !empty($cmd) && !str_starts_with($cmd, '--'); }
        );
        
        $this->conn->begin_transaction();
        
        try {
            foreach ($commands as $command) {
                if (!empty($command)) {
                    $this->conn->query($command);
                }
            }
            
            $this->conn->commit();
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw new Exception("Erro na restauração SQL: " . $e->getMessage());
        }
    }
    
    /**
     * Extrai arquivos de backup
     */
    private function extractBackupFiles($backup) {
        $extractDir = sys_get_temp_dir() . '/restore_' . uniqid();
        
        if (!mkdir($extractDir, 0755, true)) {
            throw new Exception("Não foi possível criar diretório temporário: {$extractDir}");
        }
        
        foreach ($backup['files'] as $file) {
            if (str_ends_with($file, '.tar.gz')) {
                // Extrair arquivo comprimido
                $command = sprintf(
                    'tar -xzf %s -C %s',
                    escapeshellarg($file),
                    escapeshellarg($extractDir)
                );
                
                exec($command . ' 2>&1', $output, $returnCode);
                
                if ($returnCode !== 0) {
                    throw new Exception("Erro ao extrair backup: " . implode("\n", $output));
                }
                
            } else if (is_dir($file)) {
                // Copiar diretório
                $this->copyDirectory($file, $extractDir . '/' . basename($file));
            }
        }
        
        return $extractDir;
    }
    
    /**
     * Restaura diretório
     */
    private function restoreDirectory($sourceDir, $targetDir) {
        // Criar backup do diretório atual
        if (is_dir($targetDir)) {
            $backupDir = $targetDir . '_backup_' . time();
            rename($targetDir, $backupDir);
            echo "   Backup do diretório atual criado em: {$backupDir}\n";
        }
        
        // Restaurar diretório
        $this->copyDirectory($sourceDir, $targetDir);
    }
    
    /**
     * Copia diretório recursivamente
     */
    private function copyDirectory($source, $dest) {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!mkdir($target, 0755, true) && !is_dir($target)) {
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
     * Restaura configurações específicas
     */
    private function restoreConfigurations($configDir) {
        $configFiles = [
            'config.php' => BASE_PATH . '/config/',
            'email.php' => BASE_PATH . '/config/',
            '.env' => BASE_PATH . '/',
            '.htaccess' => BASE_PATH . '/',
            'composer.json' => BASE_PATH . '/',
            'composer.lock' => BASE_PATH . '/'
        ];
        
        foreach ($configFiles as $filename => $targetDir) {
            $sourceFile = $configDir . '/' . $filename;
            $targetFile = $targetDir . $filename;
            
            if (file_exists($sourceFile)) {
                // Fazer backup do arquivo atual
                if (file_exists($targetFile)) {
                    copy($targetFile, $targetFile . '.backup.' . time());
                }
                
                // Restaurar arquivo
                copy($sourceFile, $targetFile);
                echo "   Configuração restaurada: {$filename}\n";
            }
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
     * Verifica integridade de backup
     */
    public function verifyBackup($backupId) {
        echo "=== VERIFICAÇÃO DE INTEGRIDADE ===\n";
        echo "Backup ID: {$backupId}\n\n";
        
        try {
            $backup = $this->backupManager->getBackup($backupId);
            
            if (!$backup) {
                echo "❌ Backup não encontrado!\n";
                return false;
            }
            
            echo "Verificando arquivos...\n";
            
            $allFilesExist = true;
            $totalSize = 0;
            
            foreach ($backup['files'] as $file) {
                if (file_exists($file)) {
                    $size = filesize($file);
                    $totalSize += $size;
                    echo "   ✅ " . basename($file) . " (" . $this->formatBytes($size) . ")\n";
                } else {
                    echo "   ❌ " . basename($file) . " (arquivo não encontrado)\n";
                    $allFilesExist = false;
                }
            }
            
            echo "\nResumo:\n";
            echo "  - Status: {$backup['status']}\n";
            echo "  - Tamanho registrado: " . $this->formatBytes($backup['size']) . "\n";
            echo "  - Tamanho atual: " . $this->formatBytes($totalSize) . "\n";
            echo "  - Arquivos: " . count($backup['files']) . "\n";
            
            if ($allFilesExist && $backup['status'] === 'completed') {
                echo "\n✅ Backup íntegro e pronto para restauração!\n";
                return true;
            } else {
                echo "\n❌ Backup possui problemas!\n";
                return false;
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Exibe ajuda
     */
    public function showHelp() {
        echo "=== FERRAMENTAS DE RECUPERAÇÃO ===\n\n";
        echo "Uso:\n";
        echo "  php recovery_tools.php [comando] [opções]\n\n";
        echo "Comandos:\n";
        echo "  list                     - Lista backups disponíveis\n";
        echo "  restore <backup_id>      - Restaura backup específico\n";
        echo "  verify <backup_id>       - Verifica integridade do backup\n";
        echo "  help                     - Exibe esta ajuda\n\n";
        echo "Opções para restore:\n";
        echo "  --force                  - Não pedir confirmação\n";
        echo "  --no-safety-backup       - Não criar backup de segurança\n\n";
        echo "Exemplos:\n";
        echo "  php recovery_tools.php list\n";
        echo "  php recovery_tools.php restore backup_12345\n";
        echo "  php recovery_tools.php verify backup_12345\n\n";
        echo "⚠️  ATENÇÃO: A restauração substitui dados atuais!\n";
        echo "Sempre faça backup antes de restaurar.\n";
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

// Executar ferramentas de recuperação
try {
    $recovery = new RecoveryTools();
    
    if (!isset($argv[1])) {
        $recovery->showHelp();
        exit(0);
    }
    
    switch ($argv[1]) {
        case 'list':
            $recovery->listAvailableBackups();
            break;
            
        case 'restore':
            if (!isset($argv[2])) {
                echo "Uso: php recovery_tools.php restore <backup_id>\n";
                exit(1);
            }
            
            $options = [];
            if (in_array('--force', $argv)) {
                $options['force'] = true;
            }
            if (in_array('--no-safety-backup', $argv)) {
                $options['no_safety_backup'] = true;
            }
            
            $recovery->restoreBackup($argv[2], $options);
            break;
            
        case 'verify':
            if (!isset($argv[2])) {
                echo "Uso: php recovery_tools.php verify <backup_id>\n";
                exit(1);
            }
            $recovery->verifyBackup($argv[2]);
            break;
            
        case 'help':
        default:
            $recovery->showHelp();
            break;
    }
    
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
