<?php
/**
 * Agendador de backups automáticos
 * Configura e gerencia backups programados
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/BackupManager.php';
require_once __DIR__ . '/../app/Core/Logger.php';

class BackupScheduler {
    private $backupManager;
    private $logger;
    
    public function __construct() {
        $this->backupManager = BackupManager::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Configura agendamentos padrão
     */
    public function setupDefaultSchedules() {
        echo "=== CONFIGURAÇÃO DE AGENDAMENTOS PADRÃO ===\n\n";
        
        try {
            // Backup diário do banco de dados
            $dailyId = $this->backupManager->scheduleBackup(
                BackupManager::TYPE_DATABASE,
                'daily',
                [
                    'compress' => true,
                    'description' => 'Backup diário automático do banco de dados',
                    'notify_on_failure' => true
                ]
            );
            echo "✅ Agendamento diário criado: {$dailyId}\n";
            
            // Backup semanal completo
            $weeklyId = $this->backupManager->scheduleBackup(
                BackupManager::TYPE_FULL,
                'weekly',
                [
                    'compress' => true,
                    'description' => 'Backup semanal completo (banco + arquivos + config)',
                    'notify_on_failure' => true,
                    'cleanup_after' => true
                ]
            );
            echo "✅ Agendamento semanal criado: {$weeklyId}\n";
            
            // Backup mensal de arquivos
            $monthlyId = $this->backupManager->scheduleBackup(
                BackupManager::TYPE_FILES,
                'monthly',
                [
                    'compress' => true,
                    'description' => 'Backup mensal dos arquivos e uploads',
                    'notify_on_failure' => true
                ]
            );
            echo "✅ Agendamento mensal criado: {$monthlyId}\n";
            
            echo "\n✅ Agendamentos padrão configurados com sucesso!\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            $this->logger->error('Failed to setup default schedules', [
                'error' => $e->getMessage()
            ]);
            exit(1);
        }
    }
    
    /**
     * Lista agendamentos ativos
     */
    public function listSchedules() {
        echo "=== AGENDAMENTOS ATIVOS ===\n\n";
        
        try {
            $cache = Cache::getInstance();
            $schedules = $cache->get('backup_schedules', []);
            
            if (empty($schedules)) {
                echo "Nenhum agendamento encontrado.\n";
                echo "Use 'setup' para criar agendamentos padrão.\n";
                return;
            }
            
            foreach ($schedules as $schedule) {
                $status = $schedule['enabled'] ? '✅ Ativo' : '❌ Inativo';
                $lastRun = $schedule['last_run'] ? date('Y-m-d H:i:s', $schedule['last_run']) : 'Nunca';
                $nextRun = date('Y-m-d H:i:s', $schedule['next_run']);
                
                echo "ID: {$schedule['id']}\n";
                echo "  Tipo: {$schedule['type']}\n";
                echo "  Cronograma: {$schedule['schedule']}\n";
                echo "  Status: {$status}\n";
                echo "  Última execução: {$lastRun}\n";
                echo "  Próxima execução: {$nextRun}\n";
                echo "  Criado em: " . date('Y-m-d H:i:s', $schedule['created_at']) . "\n";
                
                if (!empty($schedule['options']['description'])) {
                    echo "  Descrição: {$schedule['options']['description']}\n";
                }
                
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Executa backups agendados
     */
    public function runScheduled() {
        echo "=== EXECUÇÃO DE BACKUPS AGENDADOS ===\n";
        echo "Verificando agendamentos em: " . date('Y-m-d H:i:s') . "\n\n";
        
        try {
            $executed = $this->backupManager->runScheduledBackups();
            
            if ($executed > 0) {
                echo "✅ {$executed} backup(s) agendado(s) executado(s) com sucesso!\n";
                
                // Executar limpeza após backups agendados
                $cleaned = $this->backupManager->cleanup();
                if ($cleaned > 0) {
                    echo "🧹 {$cleaned} backup(s) antigo(s) removido(s)\n";
                }
                
            } else {
                echo "ℹ️  Nenhum backup agendado para execução neste momento\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            $this->logger->error('Scheduled backup execution failed', [
                'error' => $e->getMessage()
            ]);
            exit(1);
        }
    }
    
    /**
     * Adiciona agendamento personalizado
     */
    public function addSchedule($type, $schedule, $options = []) {
        echo "=== ADICIONANDO AGENDAMENTO ===\n";
        echo "Tipo: {$type}\n";
        echo "Cronograma: {$schedule}\n\n";
        
        try {
            $defaultOptions = [
                'compress' => true,
                'description' => "Backup {$schedule} do tipo {$type}",
                'notify_on_failure' => true
            ];
            
            $mergedOptions = array_merge($defaultOptions, $options);
            
            $scheduleId = $this->backupManager->scheduleBackup($type, $schedule, $mergedOptions);
            
            echo "✅ Agendamento criado com sucesso!\n";
            echo "ID: {$scheduleId}\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Remove agendamento
     */
    public function removeSchedule($scheduleId) {
        echo "=== REMOVENDO AGENDAMENTO ===\n";
        echo "ID: {$scheduleId}\n\n";
        
        try {
            $cache = Cache::getInstance();
            $schedules = $cache->get('backup_schedules', []);
            
            $found = false;
            $filteredSchedules = [];
            
            foreach ($schedules as $schedule) {
                if ($schedule['id'] === $scheduleId) {
                    $found = true;
                    echo "Agendamento encontrado: {$schedule['type']} ({$schedule['schedule']})\n";
                } else {
                    $filteredSchedules[] = $schedule;
                }
            }
            
            if (!$found) {
                echo "❌ Agendamento não encontrado!\n";
                exit(1);
            }
            
            $cache->set('backup_schedules', $filteredSchedules, 86400 * 365);
            
            echo "✅ Agendamento removido com sucesso!\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Habilita/desabilita agendamento
     */
    public function toggleSchedule($scheduleId, $enabled = null) {
        echo "=== ALTERANDO STATUS DO AGENDAMENTO ===\n";
        echo "ID: {$scheduleId}\n\n";
        
        try {
            $cache = Cache::getInstance();
            $schedules = $cache->get('backup_schedules', []);
            
            $found = false;
            
            foreach ($schedules as &$schedule) {
                if ($schedule['id'] === $scheduleId) {
                    $found = true;
                    $oldStatus = $schedule['enabled'];
                    $newStatus = $enabled !== null ? $enabled : !$oldStatus;
                    
                    $schedule['enabled'] = $newStatus;
                    
                    $statusText = $newStatus ? 'habilitado' : 'desabilitado';
                    echo "Agendamento {$statusText}: {$schedule['type']} ({$schedule['schedule']})\n";
                    break;
                }
            }
            
            if (!$found) {
                echo "❌ Agendamento não encontrado!\n";
                exit(1);
            }
            
            $cache->set('backup_schedules', $schedules, 86400 * 365);
            
            echo "✅ Status alterado com sucesso!\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Testa agendador
     */
    public function test() {
        echo "=== TESTE DO AGENDADOR ===\n\n";
        
        try {
            // Teste 1: Criar agendamento temporário
            echo "1. Criando agendamento de teste...\n";
            $testId = $this->backupManager->scheduleBackup(
                BackupManager::TYPE_CONFIG,
                'daily',
                [
                    'compress' => false,
                    'description' => 'Teste do agendador - será removido',
                    'test' => true
                ]
            );
            echo "   ✅ Agendamento de teste criado: {$testId}\n";
            
            // Teste 2: Verificar se foi salvo
            echo "2. Verificando persistência...\n";
            $cache = Cache::getInstance();
            $schedules = $cache->get('backup_schedules', []);
            $testFound = false;
            
            foreach ($schedules as $schedule) {
                if ($schedule['id'] === $testId) {
                    $testFound = true;
                    break;
                }
            }
            
            if ($testFound) {
                echo "   ✅ Agendamento persistido corretamente\n";
            } else {
                echo "   ❌ Agendamento não foi persistido\n";
                return false;
            }
            
            // Teste 3: Remover agendamento de teste
            echo "3. Removendo agendamento de teste...\n";
            $this->removeSchedule($testId);
            echo "   ✅ Agendamento de teste removido\n";
            
            echo "\n✅ Todos os testes do agendador passaram!\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Exibe status geral
     */
    public function status() {
        echo "=== STATUS DO SISTEMA DE BACKUP ===\n\n";
        
        try {
            $cache = Cache::getInstance();
            $schedules = $cache->get('backup_schedules', []);
            
            // Estatísticas dos agendamentos
            $activeCount = 0;
            $inactiveCount = 0;
            $types = [];
            
            foreach ($schedules as $schedule) {
                if ($schedule['enabled']) {
                    $activeCount++;
                } else {
                    $inactiveCount++;
                }
                
                $types[$schedule['type']] = ($types[$schedule['type']] ?? 0) + 1;
            }
            
            echo "AGENDAMENTOS:\n";
            echo "  - Total: " . count($schedules) . "\n";
            echo "  - Ativos: {$activeCount}\n";
            echo "  - Inativos: {$inactiveCount}\n";
            
            if (!empty($types)) {
                echo "\nPOR TIPO:\n";
                foreach ($types as $type => $count) {
                    echo "  - {$type}: {$count}\n";
                }
            }
            
            // Últimos backups
            echo "\nÚLTIMOS BACKUPS:\n";
            $recentBackups = $this->backupManager->listBackups(5);
            
            if (empty($recentBackups)) {
                echo "  - Nenhum backup encontrado\n";
            } else {
                foreach ($recentBackups as $backup) {
                    $date = date('Y-m-d H:i:s', $backup['started_at']);
                    $status = $backup['status'] === 'completed' ? '✅' : '❌';
                    $size = isset($backup['size']) ? $this->formatBytes($backup['size']) : 'N/A';
                    
                    echo "  {$status} {$date} - {$backup['type']} ({$size})\n";
                }
            }
            
            // Próximas execuções
            echo "\nPRÓXIMAS EXECUÇÕES:\n";
            $upcoming = [];
            
            foreach ($schedules as $schedule) {
                if ($schedule['enabled']) {
                    $upcoming[] = [
                        'type' => $schedule['type'],
                        'next_run' => $schedule['next_run'],
                        'schedule' => $schedule['schedule']
                    ];
                }
            }
            
            usort($upcoming, function($a, $b) {
                return $a['next_run'] - $b['next_run'];
            });
            
            foreach (array_slice($upcoming, 0, 5) as $next) {
                $date = date('Y-m-d H:i:s', $next['next_run']);
                echo "  - {$date}: {$next['type']} ({$next['schedule']})\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Exibe ajuda
     */
    public function showHelp() {
        echo "=== AGENDADOR DE BACKUPS ===\n\n";
        echo "Uso:\n";
        echo "  php backup_scheduler.php [comando] [opções]\n\n";
        echo "Comandos:\n";
        echo "  setup                    - Configura agendamentos padrão\n";
        echo "  list                     - Lista agendamentos ativos\n";
        echo "  run                      - Executa backups agendados\n";
        echo "  add <tipo> <cronograma>  - Adiciona agendamento\n";
        echo "  remove <id>              - Remove agendamento\n";
        echo "  enable <id>              - Habilita agendamento\n";
        echo "  disable <id>             - Desabilita agendamento\n";
        echo "  status                   - Exibe status geral\n";
        echo "  test                     - Testa o agendador\n";
        echo "  help                     - Exibe esta ajuda\n\n";
        echo "Tipos de backup:\n";
        echo "  database                 - Backup do banco de dados\n";
        echo "  full                     - Backup completo\n";
        echo "  files                    - Backup dos arquivos\n";
        echo "  config                   - Backup das configurações\n\n";
        echo "Cronogramas:\n";
        echo "  daily                    - Diário\n";
        echo "  weekly                   - Semanal\n";
        echo "  monthly                  - Mensal\n\n";
        echo "Exemplos:\n";
        echo "  php backup_scheduler.php setup\n";
        echo "  php backup_scheduler.php add database daily\n";
        echo "  php backup_scheduler.php run\n";
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

// Executar agendador
try {
    $scheduler = new BackupScheduler();
    
    if (!isset($argv[1])) {
        $scheduler->showHelp();
        exit(0);
    }
    
    switch ($argv[1]) {
        case 'setup':
            $scheduler->setupDefaultSchedules();
            break;
            
        case 'list':
            $scheduler->listSchedules();
            break;
            
        case 'run':
            $scheduler->runScheduled();
            break;
            
        case 'add':
            if (!isset($argv[2]) || !isset($argv[3])) {
                echo "Uso: php backup_scheduler.php add <tipo> <cronograma>\n";
                exit(1);
            }
            $scheduler->addSchedule($argv[2], $argv[3]);
            break;
            
        case 'remove':
            if (!isset($argv[2])) {
                echo "Uso: php backup_scheduler.php remove <id>\n";
                exit(1);
            }
            $scheduler->removeSchedule($argv[2]);
            break;
            
        case 'enable':
            if (!isset($argv[2])) {
                echo "Uso: php backup_scheduler.php enable <id>\n";
                exit(1);
            }
            $scheduler->toggleSchedule($argv[2], true);
            break;
            
        case 'disable':
            if (!isset($argv[2])) {
                echo "Uso: php backup_scheduler.php disable <id>\n";
                exit(1);
            }
            $scheduler->toggleSchedule($argv[2], false);
            break;
            
        case 'status':
            $scheduler->status();
            break;
            
        case 'test':
            $scheduler->test();
            break;
            
        case 'help':
        default:
            $scheduler->showHelp();
            break;
    }
    
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
