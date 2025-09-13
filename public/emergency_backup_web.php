<?php
/**
 * BACKUP EMERGENCIAL VIA WEB
 */

// Definir BASE_PATH primeiro
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Configurar display de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🆘 BACKUP EMERGENCIAL</h1>\n";
echo "<pre>\n";

// Carregar configurações
require_once '../config/config.php';

echo "Backup iniciado em: " . date('Y-m-d H:i:s') . "\n";
echo "Servidor: " . DB_HOST . "\n";
echo "Banco: " . DB_NAME . "\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("❌ ERRO DE CONEXÃO: " . $conn->connect_error . "\n");
    }
    
    echo "✅ Conectado ao banco\n\n";
    
    // Criar diretório de backup se não existir
    $backupDir = __DIR__ . '/emergency_backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "/emergency_backup_{$timestamp}.sql";
    
    echo "1. FAZENDO BACKUP DA ESTRUTURA E DADOS:\n";
    
    // Abrir arquivo de backup
    $file = fopen($backupFile, 'w');
    
    if (!$file) {
        die("❌ Erro ao criar arquivo de backup\n");
    }
    
    // Cabeçalho do backup
    fwrite($file, "-- BACKUP EMERGENCIAL\n");
    fwrite($file, "-- Data: " . date('Y-m-d H:i:s') . "\n");
    fwrite($file, "-- Servidor: " . DB_HOST . "\n");
    fwrite($file, "-- Banco: " . DB_NAME . "\n");
    fwrite($file, "-- ================================\n\n");
    
    fwrite($file, "SET FOREIGN_KEY_CHECKS = 0;\n");
    fwrite($file, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($file, "SET AUTOCOMMIT = 0;\n");
    fwrite($file, "START TRANSACTION;\n\n");
    
    // Listar todas as tabelas
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    
    echo "Tabelas encontradas:\n";
    while ($row = $result->fetch_array()) {
        $tableName = $row[0];
        $tables[] = $tableName;
        echo "   - {$tableName}\n";
    }
    echo "\n";
    
    // Fazer backup de cada tabela
    foreach ($tables as $table) {
        echo "Processando tabela: {$table}\n";
        
        // Estrutura da tabela
        $result = $conn->query("SHOW CREATE TABLE `{$table}`");
        $row = $result->fetch_array();
        
        fwrite($file, "-- Estrutura da tabela `{$table}`\n");
        fwrite($file, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($file, $row[1] . ";\n\n");
        
        // Dados da tabela
        $result = $conn->query("SELECT * FROM `{$table}`");
        $numRows = $result->num_rows;
        
        if ($numRows > 0) {
            echo "   {$numRows} registros encontrados\n";
            
            fwrite($file, "-- Dados da tabela `{$table}`\n");
            
            // Obter nomes das colunas
            $fieldInfo = $conn->query("SHOW COLUMNS FROM `{$table}`");
            $fields = [];
            while ($field = $fieldInfo->fetch_assoc()) {
                $fields[] = "`{$field['Field']}`";
            }
            
            $fieldList = implode(', ', $fields);
            
            while ($row = $result->fetch_assoc()) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                
                $valueList = implode(', ', $values);
                fwrite($file, "INSERT INTO `{$table}` ({$fieldList}) VALUES ({$valueList});\n");
            }
            fwrite($file, "\n");
        } else {
            echo "   Tabela vazia\n";
        }
    }
    
    fwrite($file, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fwrite($file, "COMMIT;\n");
    fclose($file);
    
    echo "\n✅ BACKUP CONCLUÍDO!\n";
    echo "Arquivo: {$backupFile}\n";
    echo "Tamanho: " . number_format(filesize($backupFile)) . " bytes\n\n";
    
    // Verificar denúncias especificamente
    echo "2. VERIFICAÇÃO ESPECÍFICA - DENÚNCIAS:\n";
    $result = $conn->query("SELECT COUNT(*) as total FROM denuncias");
    if ($result) {
        $count = $result->fetch_assoc()['total'];
        echo "Total de denúncias: {$count}\n";
        
        if ($count > 0) {
            echo "Últimas denúncias:\n";
            $result = $conn->query("SELECT id, protocolo, data_criacao, status FROM denuncias ORDER BY id DESC LIMIT 10");
            while ($row = $result->fetch_assoc()) {
                echo "   ID: {$row['id']}, Protocolo: {$row['protocolo']}, Data: {$row['data_criacao']}, Status: {$row['status']}\n";
            }
        } else {
            echo "⚠️  TABELA DENÚNCIAS ESTÁ VAZIA!\n";
        }
    }
    
    // Verificar AUTO_INCREMENT
    echo "\n3. VERIFICAÇÃO AUTO_INCREMENT:\n";
    $result = $conn->query("SHOW TABLE STATUS LIKE 'denuncias'");
    if ($result && $row = $result->fetch_assoc()) {
        $autoIncrement = $row['Auto_increment'];
        echo "AUTO_INCREMENT atual: {$autoIncrement}\n";
        
        if ($autoIncrement > 1) {
            echo "🚨 ALERTA: AUTO_INCREMENT = {$autoIncrement} mas tabela pode estar vazia!\n";
            echo "   Isso confirma que registros foram DELETADOS!\n";
        }
    }
    
    $conn->close();
    
    echo "\n4. LINK PARA DOWNLOAD:\n";
    $relativePath = 'emergency_backups/' . basename($backupFile);
    echo "📥 <a href='{$relativePath}' download>Baixar Backup</a>\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n</pre>";
echo "<p><strong>Backup concluído em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>