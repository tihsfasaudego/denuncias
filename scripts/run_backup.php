<?php
/**
 * Script para executar backups automáticos
 * Pode ser chamado via cron ou manualmente
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/BackupManager.php';
require_once __DIR__ . '/../app/Core/Logger.php';

class BackupRunner {
    private $backupManager;
    private $logger;
    
    public function __construct() {
        $this->backupManager = BackupManager::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Executa backup baseado nos argumentos da linha de comando
     */
    public function run($argv) {
        $type = isset($argv[1]) ? $argv[1] : 'database';
        $compress = isset($argv[2]) ? filter_var($argv[2], FILTER_VALIDATE_BOOLEAN) : true;
        
        echo "=== BACKUP AUTOMÁTICO ===\n";
        echo "Tipo: {$type}\n";
        echo "Compressão: " . ($compress ? 'SIM' : 'NÃO') . "\n";
        echo "Iniciado em: " . date('Y-m-d H:i:s') . "\n\n";
        
        try {
            $options = [
                'compress' => $compress,
                'source' => 'automated_script'
            ];
            
            $backup = $this->backupManager->createBackup($type, $options);
            
            echo "✅ Backup concluído com sucesso!\n";
            echo "ID: {$backup['id']}\n";
            echo "Tamanho: " . $this->formatBytes($backup['size']) . "\n";
            echo "Duração: " . ($backup['completed_at'] - $backup['started_at']) . " segundos\n";
            echo "Arquivos: " . count($backup['files']) . "\n";
            
            // Listar arquivos criados
            foreach ($backup['files'] as $file) {
                echo "  - " . basename($file) . "\n";
            }
            
            // Executar limpeza de backups antigos
            $cleaned = $this->backupManager->cleanup();
            if ($cleaned > 0) {
                echo "\n🧹 Limpeza: {$cleaned} backups antigos removidos\n";
            }
            
            echo "\n✅ Processo concluído!\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            $this->logger->error('Backup failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'source' => 'automated_script'
            ]);
            exit(1);
        }
    }
    
    /**
     * Executa todos os backups agendados
     */
    public function runScheduled() {
        echo "=== EXECUÇÃO DE BACKUPS AGENDADOS ===\n";
        echo "Iniciado em: " . date('Y-m-d H:i:s') . "\n\n";
        
        try {
            $executed = $this->backupManager->runScheduledBackups();
            
            if ($executed > 0) {
                echo "✅ {$executed} backup(s) agendado(s) executado(s)\n";
            } else {
                echo "ℹ️  Nenhum backup agendado para execução\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            $this->logger->error('Scheduled backup failed', [
                'error' => $e->getMessage(),
                'source' => 'automated_script'
            ]);
            exit(1);
        }
    }
    
    /**
     * Lista backups existentes
     */
    public function listBackups() {
        echo "=== LISTA DE BACKUPS ===\n\n";
        
        try {
            $backups = $this->backupManager->listBackups(20);
            
            if (empty($backups)) {
                echo "Nenhum backup encontrado.\n";
                return;
            }
            
            foreach ($backups as $backup) {
                $status = $backup['status'];
                $statusIcon = $status === 'completed' ? '✅' : ($status === 'failed' ? '❌' : '⏳');
                
                echo "{$statusIcon} {$backup['id']}\n";
                echo "   Tipo: {$backup['type']}\n";
                echo "   Status: {$status}\n";
                echo "   Criado: " . date('Y-m-d H:i:s', $backup['started_at']) . "\n";
                
                if ($status === 'completed') {
                    echo "   Tamanho: " . $this->formatBytes($backup['size']) . "\n";
                    echo "   Arquivos: " . count($backup['files']) . "\n";
                }
                
                if (!empty($backup['error'])) {
                    echo "   Erro: {$backup['error']}\n";
                }
                
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Configura backup agendado
     */
    public function schedule($type, $schedule) {
        echo "=== AGENDAMENTO DE BACKUP ===\n";
        echo "Tipo: {$type}\n";
        echo "Cronograma: {$schedule}\n\n";
        
        try {
            $options = [
                'compress' => true,
                'source' => 'scheduled'
            ];
            
            $scheduleId = $this->backupManager->scheduleBackup($type, $schedule, $options);
            
            echo "✅ Backup agendado com sucesso!\n";
            echo "ID do agendamento: {$scheduleId}\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Testa sistema de backup
     */
    public function test() {
        echo "=== TESTE DO SISTEMA DE BACKUP ===\n\n";
        
        try {
            // Teste 1: Criar backup de teste
            echo "1. Testando backup de configuração...\n";
            $backup = $this->backupManager->createBackup('config', ['compress' => false]);
            echo "   ✅ Backup criado: {$backup['id']}\n";
            
            // Teste 2: Verificar se arquivos foram criados
            echo "2. Verificando arquivos...\n";
            foreach ($backup['files'] as $file) {
                if (file_exists($file)) {
                    echo "   ✅ " . basename($file) . " (" . $this->formatBytes(filesize($file)) . ")\n";
                } else {
                    echo "   ❌ " . basename($file) . " não encontrado\n";
                }
            }
            
            // Teste 3: Remover backup de teste
            echo "3. Removendo backup de teste...\n";
            $this->backupManager->deleteBackup($backup['id']);
            echo "   ✅ Backup removido\n";
            
            echo "\n✅ Todos os testes passaram!\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Exibe ajuda
     */
    public function showHelp() {
        echo "=== SISTEMA DE BACKUP AUTOMÁTICO ===\n\n";
        echo "Uso:\n";
        echo "  php run_backup.php [comando] [opções]\n\n";
        echo "Comandos:\n";
        echo "  database [compress]    - Backup do banco de dados (padrão)\n";
        echo "  full [compress]        - Backup completo (banco + arquivos + config)\n";
        echo "  files [compress]       - Backup apenas dos arquivos\n";
        echo "  config [compress]      - Backup apenas das configurações\n";
        echo "  scheduled              - Executa backups agendados\n";
        echo "  list                   - Lista backups existentes\n";
        echo "  schedule <tipo> <when> - Agenda backup (daily, weekly, monthly)\n";
        echo "  test                   - Testa o sistema de backup\n";
        echo "  help                   - Exibe esta ajuda\n\n";
        echo "Opções:\n";
        echo "  compress: true/false   - Ativar/desativar compressão (padrão: true)\n\n";
        echo "Exemplos:\n";
        echo "  php run_backup.php database true\n";
        echo "  php run_backup.php full false\n";
        echo "  php run_backup.php schedule database daily\n";
        echo "  php run_backup.php scheduled\n";
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

// Executar script
try {
    $runner = new BackupRunner();
    
    if (!isset($argv[1])) {
        $runner->showHelp();
        exit(0);
    }
    
    switch ($argv[1]) {
        case 'database':
        case 'full':
        case 'files':
        case 'config':
            $runner->run($argv);
            break;
            
        case 'scheduled':
            $runner->runScheduled();
            break;
            
        case 'list':
            $runner->listBackups();
            break;
            
        case 'schedule':
            if (!isset($argv[2]) || !isset($argv[3])) {
                echo "Uso: php run_backup.php schedule <tipo> <cronograma>\n";
                exit(1);
            }
            $runner->schedule($argv[2], $argv[3]);
            break;
            
        case 'test':
            $runner->test();
            break;
            
        case 'help':
        default:
            $runner->showHelp();
            break;
    }
    
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
