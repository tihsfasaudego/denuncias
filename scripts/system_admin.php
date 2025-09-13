<?php
/**
 * Script principal de administração do sistema
 * Centraliza todas as ferramentas de backup, recuperação e diagnóstico
 */

require_once __DIR__ . '/../config/config.php';

class SystemAdmin {
    
    public function showMainMenu() {
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║              HSFA - SISTEMA DE ADMINISTRAÇÃO                    ║\n";
        echo "║                    Sistema de Denúncias                         ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        echo "║                                                                  ║\n";
        echo "║  🔍 DIAGNÓSTICO                                                  ║\n";
        echo "║  1. investigate     - Investigar dados perdidos                 ║\n";
        echo "║  2. integrity       - Verificar integridade do banco            ║\n";
        echo "║                                                                  ║\n";
        echo "║  💾 BACKUP                                                       ║\n";
        echo "║  3. backup          - Sistema de backup                         ║\n";
        echo "║  4. schedule        - Agendador de backups                      ║\n";
        echo "║  5. setup-cron      - Configurar cron jobs                      ║\n";
        echo "║                                                                  ║\n";
        echo "║  🔄 RECUPERAÇÃO                                                  ║\n";
        echo "║  6. recovery        - Ferramentas de recuperação                ║\n";
        echo "║  7. list-backups    - Listar backups disponíveis               ║\n";
        echo "║                                                                  ║\n";
        echo "║  ⚡ AÇÕES RÁPIDAS                                                ║\n";
        echo "║  8. quick-backup    - Backup rápido                             ║\n";
        echo "║  9. status          - Status geral do sistema                   ║\n";
        echo "║  10. test-all       - Testar todos os sistemas                  ║\n";
        echo "║                                                                  ║\n";
        echo "║  0. help            - Ajuda completa                            ║\n";
        echo "║                                                                  ║\n";
        echo "╚══════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }
    
    public function runCommand($command, $args = []) {
        switch ($command) {
            case 'investigate':
            case '1':
                $this->runScript('investigate_missing_data.php');
                break;
                
            case 'integrity':
            case '2':
                $this->runScript('check_database_integrity.php');
                break;
                
            case 'backup':
            case '3':
                $this->runScript('run_backup.php', $args);
                break;
                
            case 'schedule':
            case '4':
                $this->runScript('backup_scheduler.php', $args);
                break;
                
            case 'setup-cron':
            case '5':
                $this->runShellScript('setup_cron.sh');
                break;
                
            case 'recovery':
            case '6':
                $this->runScript('recovery_tools.php', $args);
                break;
                
            case 'list-backups':
            case '7':
                $this->runScript('recovery_tools.php', ['list']);
                break;
                
            case 'quick-backup':
            case '8':
                $this->quickBackup();
                break;
                
            case 'status':
            case '9':
                $this->showSystemStatus();
                break;
                
            case 'test-all':
            case '10':
                $this->testAllSystems();
                break;
                
            case 'help':
            case '0':
                $this->showDetailedHelp();
                break;
                
            default:
                echo "❌ Comando não reconhecido: {$command}\n";
                echo "Use 'help' para ver todos os comandos disponíveis.\n";
                break;
        }
    }
    
    private function runScript($scriptName, $args = []) {
        $scriptPath = __DIR__ . '/' . $scriptName;
        
        if (!file_exists($scriptPath)) {
            echo "❌ Script não encontrado: {$scriptName}\n";
            return;
        }
        
        $command = 'php ' . escapeshellarg($scriptPath);
        
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }
        
        echo "Executando: {$scriptName}\n";
        echo str_repeat('=', 50) . "\n\n";
        
        passthru($command);
    }
    
    private function runShellScript($scriptName) {
        $scriptPath = __DIR__ . '/' . $scriptName;
        
        if (!file_exists($scriptPath)) {
            echo "❌ Script não encontrado: {$scriptName}\n";
            return;
        }
        
        echo "Executando: {$scriptName}\n";
        echo str_repeat('=', 50) . "\n\n";
        
        passthru("bash " . escapeshellarg($scriptPath));
    }
    
    private function quickBackup() {
        echo "=== BACKUP RÁPIDO ===\n\n";
        echo "Escolha o tipo de backup:\n";
        echo "1. Database (mais rápido)\n";
        echo "2. Completo (banco + arquivos)\n";
        echo "3. Cancelar\n\n";
        
        echo "Opção [1-3]: ";
        $choice = trim(fgets(STDIN));
        
        switch ($choice) {
            case '1':
                $this->runScript('run_backup.php', ['database', 'true']);
                break;
            case '2':
                $this->runScript('run_backup.php', ['full', 'true']);
                break;
            case '3':
                echo "Operação cancelada.\n";
                break;
            default:
                echo "❌ Opção inválida.\n";
                break;
        }
    }
    
    private function showSystemStatus() {
        echo "=== STATUS GERAL DO SISTEMA ===\n\n";
        
        try {
            // Status do banco de dados
            echo "🗄️  BANCO DE DADOS:\n";
            $conn = Database::getInstance()->getConnection();
            echo "   ✅ Conectado a: " . DB_HOST . "/" . DB_NAME . "\n";
            
            // Contar denúncias
            $result = $conn->query("SELECT COUNT(*) as total FROM denuncias");
            $total = $result->fetch_assoc()['total'];
            echo "   📊 Total de denúncias: {$total}\n";
            
            // Status dos backups
            echo "\n💾 BACKUPS:\n";
            require_once __DIR__ . '/../app/Core/BackupManager.php';
            $backupManager = BackupManager::getInstance();
            $backups = $backupManager->listBackups(5);
            
            if (empty($backups)) {
                echo "   ⚠️  Nenhum backup encontrado\n";
            } else {
                $lastBackup = $backups[0];
                $date = date('Y-m-d H:i:s', $lastBackup['started_at']);
                $status = $lastBackup['status'] === 'completed' ? '✅' : '❌';
                echo "   {$status} Último backup: {$date} ({$lastBackup['type']})\n";
                echo "   📈 Total de backups: " . count($backups) . "\n";
            }
            
            // Status dos agendamentos
            echo "\n⏰ AGENDAMENTOS:\n";
            require_once __DIR__ . '/../app/Core/Cache.php';
            $cache = Cache::getInstance();
            $schedules = $cache->get('backup_schedules', []);
            
            if (empty($schedules)) {
                echo "   ⚠️  Nenhum agendamento configurado\n";
            } else {
                $active = array_filter($schedules, function($s) { return $s['enabled']; });
                echo "   📅 Agendamentos ativos: " . count($active) . "/" . count($schedules) . "\n";
                
                if (!empty($active)) {
                    $next = min(array_column($active, 'next_run'));
                    echo "   ⏲️  Próximo backup: " . date('Y-m-d H:i:s', $next) . "\n";
                }
            }
            
            // Espaço em disco
            echo "\n💽 ARMAZENAMENTO:\n";
            $totalSpace = disk_total_space(BASE_PATH);
            $freeSpace = disk_free_space(BASE_PATH);
            $usedSpace = $totalSpace - $freeSpace;
            
            $usedPercent = round(($usedSpace / $totalSpace) * 100, 1);
            
            echo "   📊 Espaço usado: " . $this->formatBytes($usedSpace) . " / " . $this->formatBytes($totalSpace) . " ({$usedPercent}%)\n";
            echo "   💾 Espaço livre: " . $this->formatBytes($freeSpace) . "\n";
            
            if ($usedPercent > 90) {
                echo "   ⚠️  ATENÇÃO: Pouco espaço em disco!\n";
            }
            
            // Status da auditoria
            echo "\n🔍 AUDITORIA:\n";
            if (class_exists('AuditLogger')) {
                require_once __DIR__ . '/../app/Core/AuditLogger.php';
                $auditLogger = AuditLogger::getInstance();
                $stats = $auditLogger->getStats(7);
                
                if (!empty($stats)) {
                    $totalEvents = array_sum(array_column($stats, 'count'));
                    echo "   📈 Eventos últimos 7 dias: {$totalEvents}\n";
                    
                    $criticalEvents = array_filter($stats, function($s) { return $s['level'] === 'critical'; });
                    if (!empty($criticalEvents)) {
                        $criticalCount = array_sum(array_column($criticalEvents, 'count'));
                        echo "   🚨 Eventos críticos: {$criticalCount}\n";
                    } else {
                        echo "   ✅ Nenhum evento crítico\n";
                    }
                } else {
                    echo "   📊 Sistema de auditoria ativo\n";
                }
            } else {
                echo "   ⚠️  Sistema de auditoria não configurado\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Erro ao obter status: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Para mais detalhes, use os comandos específicos de diagnóstico.\n";
    }
    
    private function testAllSystems() {
        echo "=== TESTE COMPLETO DOS SISTEMAS ===\n\n";
        
        $tests = [
            'Conexão com banco de dados' => [$this, 'testDatabase'],
            'Sistema de backup' => [$this, 'testBackupSystem'],
            'Sistema de agendamento' => [$this, 'testScheduler'],
            'Sistema de cache' => [$this, 'testCache'],
            'Sistema de auditoria' => [$this, 'testAudit'],
            'Permissões de arquivos' => [$this, 'testFilePermissions']
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $testName => $testFunction) {
            echo "🔍 Testando: {$testName}... ";
            
            try {
                $result = call_user_func($testFunction);
                if ($result) {
                    echo "✅ PASSOU\n";
                    $passed++;
                } else {
                    echo "❌ FALHOU\n";
                    $failed++;
                }
            } catch (Exception $e) {
                echo "❌ ERRO: " . $e->getMessage() . "\n";
                $failed++;
            }
        }
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "RESULTADO FINAL:\n";
        echo "✅ Testes aprovados: {$passed}\n";
        echo "❌ Testes falhados: {$failed}\n";
        
        if ($failed > 0) {
            echo "\n⚠️  Alguns sistemas precisam de atenção!\n";
            echo "Execute os testes individuais para mais detalhes.\n";
        } else {
            echo "\n🎉 Todos os sistemas estão funcionando!\n";
        }
    }
    
    private function testDatabase() {
        $conn = Database::getInstance()->getConnection();
        $result = $conn->query("SELECT 1");
        return $result !== false;
    }
    
    private function testBackupSystem() {
        require_once __DIR__ . '/../app/Core/BackupManager.php';
        $backupManager = BackupManager::getInstance();
        
        // Tentar criar um backup de teste pequeno
        try {
            $backup = $backupManager->createBackup('config', ['compress' => false]);
            $backupManager->deleteBackup($backup['id']);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testScheduler() {
        require_once __DIR__ . '/../app/Core/BackupManager.php';
        $backupManager = BackupManager::getInstance();
        
        try {
            $scheduleId = $backupManager->scheduleBackup('config', 'daily', ['test' => true]);
            
            require_once __DIR__ . '/../app/Core/Cache.php';
            $cache = Cache::getInstance();
            $schedules = $cache->get('backup_schedules', []);
            
            // Remover agendamento de teste
            $filteredSchedules = array_filter($schedules, function($s) use ($scheduleId) {
                return $s['id'] !== $scheduleId;
            });
            $cache->set('backup_schedules', $filteredSchedules, 86400 * 365);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testCache() {
        require_once __DIR__ . '/../app/Core/Cache.php';
        $cache = Cache::getInstance();
        
        $testKey = 'test_' . uniqid();
        $testValue = 'test_value_' . time();
        
        $cache->set($testKey, $testValue, 60);
        $retrieved = $cache->get($testKey);
        $cache->delete($testKey);
        
        return $retrieved === $testValue;
    }
    
    private function testAudit() {
        if (!class_exists('AuditLogger')) {
            return false;
        }
        
        require_once __DIR__ . '/../app/Core/AuditLogger.php';
        $auditLogger = AuditLogger::getInstance();
        
        return $auditLogger->log('TEST', 'Teste do sistema de auditoria');
    }
    
    private function testFilePermissions() {
        $dirs = [
            BASE_PATH . '/storage/logs',
            BASE_PATH . '/storage/cache',
            BASE_PATH . '/storage/backups',
            BASE_PATH . '/public/uploads'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    return false;
                }
            }
            
            if (!is_writable($dir)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function showDetailedHelp() {
        echo "=== AJUDA DETALHADA ===\n\n";
        echo "COMANDOS DE DIAGNÓSTICO:\n";
        echo "  investigate     - Investiga perda de dados e analisa possíveis causas\n";
        echo "  integrity       - Verifica integridade do banco de dados\n\n";
        
        echo "COMANDOS DE BACKUP:\n";
        echo "  backup [tipo]   - Executa backup (database, full, files, config)\n";
        echo "  schedule        - Gerencia agendamentos de backup\n";
        echo "  setup-cron      - Configura cron jobs automáticos\n\n";
        
        echo "COMANDOS DE RECUPERAÇÃO:\n";
        echo "  recovery        - Ferramentas de recuperação de dados\n";
        echo "  list-backups    - Lista backups disponíveis\n\n";
        
        echo "COMANDOS RÁPIDOS:\n";
        echo "  quick-backup    - Interface interativa para backup\n";
        echo "  status          - Status geral do sistema\n";
        echo "  test-all        - Testa todos os sistemas\n\n";
        
        echo "EXEMPLOS DE USO:\n";
        echo "  php system_admin.php backup database\n";
        echo "  php system_admin.php schedule setup\n";
        echo "  php system_admin.php recovery list\n";
        echo "  php system_admin.php investigate\n\n";
        
        echo "ARQUIVOS DE SCRIPT:\n";
        echo "  investigate_missing_data.php  - Investigação de dados perdidos\n";
        echo "  check_database_integrity.php  - Verificação de integridade\n";
        echo "  run_backup.php                - Sistema de backup\n";
        echo "  backup_scheduler.php          - Agendador de backups\n";
        echo "  recovery_tools.php            - Ferramentas de recuperação\n";
        echo "  setup_cron.sh                 - Configuração de cron jobs\n\n";
        
        echo "Para ajuda específica de cada ferramenta, execute:\n";
        echo "  php [script] help\n";
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Executar administrador do sistema
try {
    $admin = new SystemAdmin();
    
    if (!isset($argv[1])) {
        $admin->showMainMenu();
        exit(0);
    }
    
    $command = $argv[1];
    $args = array_slice($argv, 2);
    
    $admin->runCommand($command, $args);
    
} catch (Exception $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
