<?php
/**
 * Script para investigar o desaparecimento das denúncias
 * Analisa logs, estrutura do banco e possíveis causas
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Logger.php';

class DataInvestigator {
    private $conn;
    private $logger;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Executa investigação completa
     */
    public function investigate() {
        echo "=== INVESTIGAÇÃO DE DADOS PERDIDOS ===\n\n";
        
        $this->checkDatabaseStructure();
        $this->checkTableContents();
        $this->analyzeTableHistory();
        $this->checkReplicationLogs();
        $this->analyzeDeletionOperations();
        $this->checkBackupHistory();
        $this->generateReport();
    }
    
    /**
     * Verifica estrutura do banco de dados
     */
    private function checkDatabaseStructure() {
        echo "1. Verificando estrutura do banco de dados...\n";
        
        try {
            // Listar todas as tabelas
            $result = $this->conn->query("SHOW TABLES");
            $tables = [];
            
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            echo "   Tabelas encontradas: " . count($tables) . "\n";
            foreach ($tables as $table) {
                echo "   - {$table}\n";
            }
            
            // Verificar estrutura da tabela denuncias
            if (in_array('denuncias', $tables)) {
                echo "\n   Estrutura da tabela 'denuncias':\n";
                $result = $this->conn->query("DESCRIBE denuncias");
                while ($row = $result->fetch_assoc()) {
                    echo "   - {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Key']}\n";
                }
                
                // Verificar se há índices
                echo "\n   Índices da tabela 'denuncias':\n";
                $result = $this->conn->query("SHOW INDEX FROM denuncias");
                while ($row = $result->fetch_assoc()) {
                    echo "   - {$row['Key_name']} em {$row['Column_name']}\n";
                }
            }
            
        } catch (Exception $e) {
            echo "   ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Verifica conteúdo das tabelas
     */
    private function checkTableContents() {
        echo "2. Verificando conteúdo das tabelas...\n";
        
        try {
            // Contar registros na tabela denuncias
            $result = $this->conn->query("SELECT COUNT(*) as total FROM denuncias");
            $count = $result->fetch_assoc()['total'];
            echo "   Total de denúncias: {$count}\n";
            
            if ($count > 0) {
                // Verificar datas das denúncias
                $result = $this->conn->query("
                    SELECT 
                        MIN(data_criacao) as primeira,
                        MAX(data_criacao) as ultima,
                        COUNT(*) as total
                    FROM denuncias
                ");
                $dates = $result->fetch_assoc();
                echo "   Primeira denúncia: {$dates['primeira']}\n";
                echo "   Última denúncia: {$dates['ultima']}\n";
                
                // Verificar distribuição por status
                $result = $this->conn->query("
                    SELECT status, COUNT(*) as total 
                    FROM denuncias 
                    GROUP BY status 
                    ORDER BY total DESC
                ");
                echo "   Distribuição por status:\n";
                while ($row = $result->fetch_assoc()) {
                    echo "   - {$row['status']}: {$row['total']}\n";
                }
            }
            
            // Verificar outras tabelas relacionadas
            $tables = ['historico_status', 'anexos', 'categorias_denuncia'];
            foreach ($tables as $table) {
                if ($this->tableExists($table)) {
                    $result = $this->conn->query("SELECT COUNT(*) as total FROM {$table}");
                    $count = $result->fetch_assoc()['total'];
                    echo "   Total em {$table}: {$count}\n";
                }
            }
            
        } catch (Exception $e) {
            echo "   ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Analisa histórico das tabelas
     */
    private function analyzeTableHistory() {
        echo "3. Analisando histórico das tabelas...\n";
        
        try {
            // Verificar informações da tabela
            $result = $this->conn->query("
                SELECT 
                    table_name,
                    table_rows,
                    data_length,
                    index_length,
                    create_time,
                    update_time
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                AND table_name = 'denuncias'
            ");
            
            if ($row = $result->fetch_assoc()) {
                echo "   Criação da tabela: " . ($row['create_time'] ?: 'N/A') . "\n";
                echo "   Última atualização: " . ($row['update_time'] ?: 'N/A') . "\n";
                echo "   Linhas estimadas: {$row['table_rows']}\n";
                echo "   Tamanho dos dados: " . $this->formatBytes($row['data_length']) . "\n";
                echo "   Tamanho dos índices: " . $this->formatBytes($row['index_length']) . "\n";
            }
            
            // Verificar se há AUTO_INCREMENT gaps
            $result = $this->conn->query("SHOW TABLE STATUS LIKE 'denuncias'");
            if ($row = $result->fetch_assoc()) {
                $autoIncrement = $row['Auto_increment'];
                echo "   Próximo AUTO_INCREMENT: {$autoIncrement}\n";
                
                // Verificar se há gaps nos IDs
                $result = $this->conn->query("SELECT MAX(id) as max_id FROM denuncias");
                $maxId = $result->fetch_assoc()['max_id'];
                if ($maxId && $autoIncrement > ($maxId + 1)) {
                    echo "   ⚠️  ALERTA: Gap detectado nos IDs (máximo: {$maxId}, próximo: {$autoIncrement})\n";
                    echo "   Isso pode indicar que registros foram deletados.\n";
                }
            }
            
        } catch (Exception $e) {
            echo "   ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Verifica logs de replicação/binlog se disponível
     */
    private function checkReplicationLogs() {
        echo "4. Verificando logs de replicação...\n";
        
        try {
            // Verificar se o binlog está habilitado
            $result = $this->conn->query("SHOW VARIABLES LIKE 'log_bin'");
            $binlog = $result->fetch_assoc();
            
            if ($binlog && $binlog['Value'] === 'ON') {
                echo "   Binary log: HABILITADO\n";
                
                // Listar arquivos de binlog
                $result = $this->conn->query("SHOW BINARY LOGS");
                echo "   Arquivos de binlog disponíveis:\n";
                while ($row = $result->fetch_assoc()) {
                    echo "   - {$row['Log_name']} ({$this->formatBytes($row['File_size'])})\n";
                }
            } else {
                echo "   Binary log: DESABILITADO\n";
                echo "   ⚠️  Sem logs de replicação disponíveis para análise.\n";
            }
            
        } catch (Exception $e) {
            echo "   ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Analisa operações de DELETE
     */
    private function analyzeDeletionOperations() {
        echo "5. Analisando operações de DELETE...\n";
        
        try {
            // Verificar logs da aplicação
            $logFiles = glob(BASE_PATH . '/storage/logs/*.log');
            $deleteOperations = [];
            
            foreach ($logFiles as $logFile) {
                if (is_readable($logFile)) {
                    $content = file_get_contents($logFile);
                    $lines = explode("\n", $content);
                    
                    foreach ($lines as $line) {
                        if (stripos($line, 'DELETE') !== false || 
                            stripos($line, 'excluir') !== false ||
                            stripos($line, 'deleted') !== false) {
                            $deleteOperations[] = [
                                'file' => basename($logFile),
                                'line' => $line
                            ];
                        }
                    }
                }
            }
            
            if (!empty($deleteOperations)) {
                echo "   Operações de DELETE encontradas nos logs:\n";
                foreach (array_slice($deleteOperations, -10) as $op) {
                    echo "   [{$op['file']}] " . trim($op['line']) . "\n";
                }
            } else {
                echo "   Nenhuma operação de DELETE encontrada nos logs.\n";
            }
            
            // Verificar se há tabela de auditoria
            if ($this->tableExists('audit_log') || $this->tableExists('user_activity_log')) {
                $table = $this->tableExists('audit_log') ? 'audit_log' : 'user_activity_log';
                
                $result = $this->conn->query("
                    SELECT * FROM {$table} 
                    WHERE action LIKE '%delet%' OR action LIKE '%exclu%'
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                
                if ($result->num_rows > 0) {
                    echo "\n   Operações de exclusão na auditoria:\n";
                    while ($row = $result->fetch_assoc()) {
                        echo "   - {$row['created_at']}: {$row['action']} (usuário: {$row['user_id']})\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "   ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Verifica histórico de backups
     */
    private function checkBackupHistory() {
        echo "6. Verificando histórico de backups...\n";
        
        try {
            $backupDir = BASE_PATH . '/storage/backups';
            
            if (is_dir($backupDir)) {
                $backupFiles = glob($backupDir . '/*');
                echo "   Diretório de backup: EXISTE\n";
                echo "   Arquivos de backup encontrados: " . count($backupFiles) . "\n";
                
                foreach ($backupFiles as $file) {
                    $size = filesize($file);
                    $date = date('Y-m-d H:i:s', filemtime($file));
                    echo "   - " . basename($file) . " ({$this->formatBytes($size)}, {$date})\n";
                }
            } else {
                echo "   Diretório de backup: NÃO EXISTE\n";
                echo "   ⚠️  Nenhum backup encontrado.\n";
            }
            
            // Verificar se há dumps SQL externos
            $sqlFiles = glob(BASE_PATH . '/*.sql');
            if (!empty($sqlFiles)) {
                echo "\n   Arquivos SQL encontrados na raiz:\n";
                foreach ($sqlFiles as $file) {
                    $size = filesize($file);
                    $date = date('Y-m-d H:i:s', filemtime($file));
                    echo "   - " . basename($file) . " ({$this->formatBytes($size)}, {$date})\n";
                }
            }
            
        } catch (Exception $e) {
            echo "   ERRO: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Gera relatório final
     */
    private function generateReport() {
        echo "7. RELATÓRIO FINAL\n";
        echo "==================\n\n";
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database_host' => DB_HOST,
            'database_name' => DB_NAME,
            'investigation_summary' => []
        ];
        
        try {
            // Resumo das descobertas
            $result = $this->conn->query("SELECT COUNT(*) as total FROM denuncias");
            $denunciasCount = $result->fetch_assoc()['total'];
            
            if ($denunciasCount == 0) {
                echo "🚨 PROBLEMA CRÍTICO IDENTIFICADO:\n";
                echo "   - Tabela 'denuncias' está vazia\n";
                echo "   - Todas as denúncias foram perdidas\n\n";
                
                echo "POSSÍVEIS CAUSAS:\n";
                echo "1. Operação DELETE acidental ou maliciosa\n";
                echo "2. Falha no sistema durante migração\n";
                echo "3. Corrupção de dados\n";
                echo "4. Restauração de backup antigo\n";
                echo "5. Problema de sincronização de dados\n\n";
                
                echo "AÇÕES RECOMENDADAS:\n";
                echo "1. Verificar se existe backup mais recente\n";
                echo "2. Implementar sistema de backup automático IMEDIATAMENTE\n";
                echo "3. Configurar logs de auditoria para evitar repetição\n";
                echo "4. Investigar logs do servidor MySQL/MariaDB\n";
                echo "5. Verificar se dados estão em outra instância\n\n";
                
                $report['investigation_summary'][] = 'CRITICAL: All denuncias data lost';
            } else {
                echo "✅ DADOS ENCONTRADOS:\n";
                echo "   - {$denunciasCount} denúncias na tabela\n";
                echo "   - Sistema aparenta estar funcionando\n\n";
                
                $report['investigation_summary'][] = "Found {$denunciasCount} denuncias records";
            }
            
            // Salvar relatório
            $reportFile = BASE_PATH . '/storage/logs/data_investigation_' . date('Y-m-d_H-i-s') . '.json';
            file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
            echo "Relatório salvo em: {$reportFile}\n";
            
        } catch (Exception $e) {
            echo "ERRO ao gerar relatório: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Verifica se tabela existe
     */
    private function tableExists($tableName) {
        try {
            $result = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
            return $result->num_rows > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Formata bytes para formato legível
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Executar investigação
try {
    $investigator = new DataInvestigator();
    $investigator->investigate();
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
