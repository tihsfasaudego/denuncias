<?php
/**
 * Script para verificação de integridade do banco de dados
 * Analisa possíveis problemas e inconsistências
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Logger.php';

class DatabaseIntegrityChecker {
    private $conn;
    private $logger;
    private $issues = [];
    private $warnings = [];
    private $info = [];
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Executa verificação completa de integridade
     */
    public function checkIntegrity() {
        echo "=== VERIFICAÇÃO DE INTEGRIDADE DO BANCO DE DADOS ===\n\n";
        echo "Banco: " . DB_NAME . "\n";
        echo "Host: " . DB_HOST . "\n";
        echo "Iniciado em: " . date('Y-m-d H:i:s') . "\n\n";
        
        $this->checkTableStructure();
        $this->checkIndexes();
        $this->checkForeignKeys();
        $this->checkDataConsistency();
        $this->checkAutoincrementGaps();
        $this->checkDuplicates();
        $this->checkOrphanedRecords();
        $this->checkTableSizes();
        $this->generateReport();
    }
    
    /**
     * Verifica estrutura das tabelas
     */
    private function checkTableStructure() {
        echo "1. Verificando estrutura das tabelas...\n";
        
        $expectedTables = [
            'denuncias', 'categorias', 'historico_status', 
            'admin', 'users', 'roles', 'permissions',
            'audit_log'
        ];
        
        foreach ($expectedTables as $table) {
            if (!$this->tableExists($table)) {
                $this->addIssue("Tabela '{$table}' não encontrada");
            } else {
                $this->addInfo("Tabela '{$table}' encontrada");
                
                // Verificar se tabela está vazia quando não deveria
                if (in_array($table, ['categorias', 'roles', 'permissions'])) {
                    $count = $this->getTableCount($table);
                    if ($count == 0) {
                        $this->addWarning("Tabela '{$table}' está vazia (pode precisar de dados iniciais)");
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Verifica índices das tabelas
     */
    private function checkIndexes() {
        echo "2. Verificando índices...\n";
        
        $criticalIndexes = [
            'denuncias' => ['PRIMARY', 'protocolo'],
            'historico_status' => ['denuncia_id'],
            'admin' => ['PRIMARY', 'usuario'],
            'users' => ['PRIMARY']
        ];
        
        foreach ($criticalIndexes as $table => $indexes) {
            if ($this->tableExists($table)) {
                $existingIndexes = $this->getTableIndexes($table);
                
                foreach ($indexes as $index) {
                    if (!in_array($index, $existingIndexes)) {
                        $this->addWarning("Índice '{$index}' não encontrado na tabela '{$table}'");
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Verifica chaves estrangeiras
     */
    private function checkForeignKeys() {
        echo "3. Verificando chaves estrangeiras...\n";
        
        try {
            // Verificar FKs órfãs em historico_status
            if ($this->tableExists('historico_status') && $this->tableExists('denuncias')) {
                $sql = "
                    SELECT COUNT(*) as orphans
                    FROM historico_status h
                    LEFT JOIN denuncias d ON h.denuncia_id = d.id
                    WHERE d.id IS NULL
                ";
                
                $result = $this->conn->query($sql);
                $orphans = $result->fetch_assoc()['orphans'];
                
                if ($orphans > 0) {
                    $this->addIssue("Encontrados {$orphans} registros órfãos em historico_status");
                } else {
                    $this->addInfo("Nenhum registro órfão em historico_status");
                }
            }
            
            // Verificar FKs órfãs em denuncia_categoria (se existir)
            if ($this->tableExists('denuncia_categoria') && $this->tableExists('denuncias')) {
                $sql = "
                    SELECT COUNT(*) as orphans
                    FROM denuncia_categoria dc
                    LEFT JOIN denuncias d ON dc.denuncia_id = d.id
                    WHERE d.id IS NULL
                ";
                
                $result = $this->conn->query($sql);
                $orphans = $result->fetch_assoc()['orphans'];
                
                if ($orphans > 0) {
                    $this->addIssue("Encontrados {$orphans} registros órfãos em denuncia_categoria");
                }
            }
            
        } catch (Exception $e) {
            $this->addWarning("Erro ao verificar chaves estrangeiras: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Verifica consistência dos dados
     */
    private function checkDataConsistency() {
        echo "4. Verificando consistência dos dados...\n";
        
        try {
            if ($this->tableExists('denuncias')) {
                // Verificar protocolos duplicados
                $sql = "
                    SELECT protocolo, COUNT(*) as count
                    FROM denuncias
                    GROUP BY protocolo
                    HAVING COUNT(*) > 1
                ";
                
                $result = $this->conn->query($sql);
                $duplicates = $result->num_rows;
                
                if ($duplicates > 0) {
                    $this->addIssue("Encontrados {$duplicates} protocolos duplicados");
                } else {
                    $this->addInfo("Nenhum protocolo duplicado encontrado");
                }
                
                // Verificar status inválidos
                $validStatuses = ['Pendente', 'Em Análise', 'Em Investigação', 'Concluída', 'Arquivada'];
                $sql = "
                    SELECT DISTINCT status
                    FROM denuncias
                    WHERE status NOT IN ('" . implode("','", $validStatuses) . "')
                ";
                
                $result = $this->conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $this->addWarning("Status inválido encontrado: '{$row['status']}'");
                    }
                } else {
                    $this->addInfo("Todos os status são válidos");
                }
                
                // Verificar datas futuras
                $sql = "
                    SELECT COUNT(*) as future_dates
                    FROM denuncias
                    WHERE data_criacao > NOW()
                ";
                
                $result = $this->conn->query($sql);
                $futureDates = $result->fetch_assoc()['future_dates'];
                
                if ($futureDates > 0) {
                    $this->addWarning("Encontradas {$futureDates} denúncias com data futura");
                }
            }
            
        } catch (Exception $e) {
            $this->addWarning("Erro ao verificar consistência: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Verifica gaps no AUTO_INCREMENT
     */
    private function checkAutoincrementGaps() {
        echo "5. Verificando gaps em AUTO_INCREMENT...\n";
        
        $tables = ['denuncias', 'admin', 'users', 'historico_status'];
        
        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                try {
                    // Obter próximo AUTO_INCREMENT
                    $sql = "SHOW TABLE STATUS LIKE '{$table}'";
                    $result = $this->conn->query($sql);
                    $status = $result->fetch_assoc();
                    $nextAutoIncrement = $status['Auto_increment'];
                    
                    // Obter maior ID atual
                    $sql = "SELECT MAX(id) as max_id FROM {$table}";
                    $result = $this->conn->query($sql);
                    $maxId = $result->fetch_assoc()['max_id'];
                    
                    if ($maxId && $nextAutoIncrement > ($maxId + 1)) {
                        $gap = $nextAutoIncrement - $maxId - 1;
                        $this->addWarning("Tabela '{$table}': Gap de {$gap} no AUTO_INCREMENT (máximo: {$maxId}, próximo: {$nextAutoIncrement})");
                    } else {
                        $this->addInfo("Tabela '{$table}': AUTO_INCREMENT consistente");
                    }
                    
                } catch (Exception $e) {
                    $this->addWarning("Erro ao verificar AUTO_INCREMENT da tabela '{$table}': " . $e->getMessage());
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Verifica registros duplicados
     */
    private function checkDuplicates() {
        echo "6. Verificando registros duplicados...\n";
        
        try {
            if ($this->tableExists('admin')) {
                $sql = "
                    SELECT usuario, COUNT(*) as count
                    FROM admin
                    GROUP BY usuario
                    HAVING COUNT(*) > 1
                ";
                
                $result = $this->conn->query($sql);
                if ($result->num_rows > 0) {
                    $this->addWarning("Usuários admin duplicados encontrados");
                    while ($row = $result->fetch_assoc()) {
                        $this->addWarning("  - Usuário '{$row['usuario']}': {$row['count']} registros");
                    }
                } else {
                    $this->addInfo("Nenhum usuário admin duplicado");
                }
            }
            
        } catch (Exception $e) {
            $this->addWarning("Erro ao verificar duplicados: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Verifica registros órfãos
     */
    private function checkOrphanedRecords() {
        echo "7. Verificando registros órfãos...\n";
        
        try {
            // Arquivos anexos órfãos
            if ($this->tableExists('denuncias')) {
                $sql = "
                    SELECT anexo
                    FROM denuncias
                    WHERE anexo IS NOT NULL AND anexo != ''
                ";
                
                $result = $this->conn->query($sql);
                $missingFiles = 0;
                $totalFiles = 0;
                
                while ($row = $result->fetch_assoc()) {
                    $totalFiles++;
                    $filePath = BASE_PATH . '/public/uploads/' . $row['anexo'];
                    
                    if (!file_exists($filePath)) {
                        $missingFiles++;
                    }
                }
                
                if ($missingFiles > 0) {
                    $this->addWarning("Encontrados {$missingFiles} anexos referenciados mas ausentes no sistema de arquivos (total: {$totalFiles})");
                } else if ($totalFiles > 0) {
                    $this->addInfo("Todos os {$totalFiles} anexos referenciados existem no sistema de arquivos");
                }
            }
            
        } catch (Exception $e) {
            $this->addWarning("Erro ao verificar registros órfãos: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Verifica tamanhos das tabelas
     */
    private function checkTableSizes() {
        echo "8. Analisando tamanhos das tabelas...\n";
        
        try {
            $sql = "
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb',
                    ROUND((data_length / 1024 / 1024), 2) AS 'data_mb',
                    ROUND((index_length / 1024 / 1024), 2) AS 'index_mb'
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ";
            
            $result = $this->conn->query($sql);
            
            echo "   Tabela                | Linhas    | Total MB | Dados MB | Índices MB\n";
            echo "   ---------------------|-----------|----------|----------|----------\n";
            
            while ($row = $result->fetch_assoc()) {
                printf("   %-20s | %9s | %8s | %8s | %8s\n",
                    $row['table_name'],
                    number_format($row['table_rows']),
                    $row['size_mb'],
                    $row['data_mb'],
                    $row['index_mb']
                );
                
                // Alertas para tabelas muito grandes
                if ($row['size_mb'] > 100) {
                    $this->addWarning("Tabela '{$row['table_name']}' é muito grande ({$row['size_mb']} MB)");
                }
                
                // Alertas para tabelas com muitos índices vs dados
                if ($row['data_mb'] > 0 && ($row['index_mb'] / $row['data_mb']) > 2) {
                    $this->addWarning("Tabela '{$row['table_name']}' tem índices desproporcionalmente grandes");
                }
            }
            
        } catch (Exception $e) {
            $this->addWarning("Erro ao analisar tamanhos: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Gera relatório final
     */
    private function generateReport() {
        echo "=== RELATÓRIO DE INTEGRIDADE ===\n\n";
        
        $totalIssues = count($this->issues);
        $totalWarnings = count($this->warnings);
        $totalInfo = count($this->info);
        
        if ($totalIssues > 0) {
            echo "🚨 PROBLEMAS CRÍTICOS ({$totalIssues}):\n";
            foreach ($this->issues as $issue) {
                echo "   ❌ {$issue}\n";
            }
            echo "\n";
        }
        
        if ($totalWarnings > 0) {
            echo "⚠️  AVISOS ({$totalWarnings}):\n";
            foreach ($this->warnings as $warning) {
                echo "   ⚠️  {$warning}\n";
            }
            echo "\n";
        }
        
        if ($totalInfo > 0) {
            echo "ℹ️  INFORMAÇÕES ({$totalInfo}):\n";
            foreach (array_slice($this->info, 0, 10) as $info) {
                echo "   ✅ {$info}\n";
            }
            if (count($this->info) > 10) {
                echo "   ... e mais " . (count($this->info) - 10) . " itens\n";
            }
            echo "\n";
        }
        
        // Resumo final
        echo "RESUMO:\n";
        echo "  - Problemas críticos: {$totalIssues}\n";
        echo "  - Avisos: {$totalWarnings}\n";
        echo "  - Verificações OK: {$totalInfo}\n";
        echo "\n";
        
        if ($totalIssues > 0) {
            echo "❌ BANCO DE DADOS POSSUI PROBLEMAS CRÍTICOS!\n";
            echo "   Recomenda-se ação imediata para corrigir os problemas identificados.\n";
        } else if ($totalWarnings > 0) {
            echo "⚠️  Banco de dados funcional, mas com avisos.\n";
            echo "   Considere revisar os avisos para otimização.\n";
        } else {
            echo "✅ BANCO DE DADOS ÍNTEGRO!\n";
            echo "   Nenhum problema crítico identificado.\n";
        }
        
        // Salvar relatório
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => DB_NAME,
            'host' => DB_HOST,
            'critical_issues' => $this->issues,
            'warnings' => $this->warnings,
            'info' => $this->info,
            'summary' => [
                'critical_count' => $totalIssues,
                'warning_count' => $totalWarnings,
                'info_count' => $totalInfo,
                'status' => $totalIssues > 0 ? 'CRITICAL' : ($totalWarnings > 0 ? 'WARNING' : 'OK')
            ]
        ];
        
        $reportFile = BASE_PATH . '/storage/logs/integrity_check_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        echo "\nRelatório salvo em: {$reportFile}\n";
    }
    
    /**
     * Métodos auxiliares
     */
    
    private function tableExists($tableName) {
        try {
            $result = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
            return $result->num_rows > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getTableCount($tableName) {
        try {
            $result = $this->conn->query("SELECT COUNT(*) as count FROM {$tableName}");
            return $result->fetch_assoc()['count'];
        } catch (Exception $e) {
            return -1;
        }
    }
    
    private function getTableIndexes($tableName) {
        try {
            $result = $this->conn->query("SHOW INDEX FROM {$tableName}");
            $indexes = [];
            
            while ($row = $result->fetch_assoc()) {
                $indexes[] = $row['Key_name'];
            }
            
            return array_unique($indexes);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function addIssue($message) {
        $this->issues[] = $message;
        echo "   ❌ {$message}\n";
    }
    
    private function addWarning($message) {
        $this->warnings[] = $message;
        echo "   ⚠️  {$message}\n";
    }
    
    private function addInfo($message) {
        $this->info[] = $message;
        echo "   ✅ {$message}\n";
    }
}

// Executar verificação
try {
    $checker = new DatabaseIntegrityChecker();
    $checker->checkIntegrity();
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
