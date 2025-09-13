<?php
/**
 * INVESTIGAÇÃO DE EMERGÊNCIA - DENÚNCIAS PERDIDAS
 * Análise forense imediata da situação
 */

echo "🚨 INVESTIGAÇÃO DE EMERGÊNCIA - DENÚNCIAS PERDIDAS 🚨\n";
echo "====================================================\n\n";

echo "Servidor atual: " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\n";
echo "Diretório: " . __DIR__ . "\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// Verificar se as constantes do banco estão definidas
echo "1. VERIFICANDO CONFIGURAÇÃO DO BANCO:\n";
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
    echo "✅ Arquivo config.php encontrado\n";
    echo "   DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NÃO DEFINIDO') . "\n";
    echo "   DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NÃO DEFINIDO') . "\n";
    echo "   DB_USER: " . (defined('DB_USER') ? DB_USER : 'NÃO DEFINIDO') . "\n";
} else {
    echo "❌ Arquivo config.php NÃO ENCONTRADO!\n";
    exit(1);
}

echo "\n2. TENTANDO CONECTAR NO BANCO 192.168.2.40:\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "❌ ERRO DE CONEXÃO: " . $conn->connect_error . "\n";
        exit(1);
    }
    
    echo "✅ Conectado com sucesso ao banco " . DB_NAME . " em " . DB_HOST . "\n";
    
    // Verificar se a tabela denuncias existe
    echo "\n3. VERIFICANDO TABELA DENUNCIAS:\n";
    $result = $conn->query("SHOW TABLES LIKE 'denuncias'");
    
    if ($result->num_rows > 0) {
        echo "✅ Tabela 'denuncias' existe\n";
        
        // Contar registros
        $result = $conn->query("SELECT COUNT(*) as total FROM denuncias");
        $count = $result->fetch_assoc()['total'];
        echo "📊 TOTAL DE DENÚNCIAS: " . $count . "\n";
        
        if ($count == 0) {
            echo "🚨 PROBLEMA CRÍTICO: TABELA DENUNCIAS ESTÁ VAZIA!\n\n";
            
            // Verificar estrutura da tabela
            echo "4. ANALISANDO ESTRUTURA DA TABELA:\n";
            $result = $conn->query("DESCRIBE denuncias");
            while ($row = $result->fetch_assoc()) {
                echo "   - {$row['Field']} ({$row['Type']})\n";
            }
            
            // Verificar AUTO_INCREMENT
            echo "\n5. VERIFICANDO AUTO_INCREMENT:\n";
            $result = $conn->query("SHOW TABLE STATUS LIKE 'denuncias'");
            $status = $result->fetch_assoc();
            $autoIncrement = $status['Auto_increment'];
            echo "   AUTO_INCREMENT atual: " . $autoIncrement . "\n";
            
            if ($autoIncrement > 1) {
                echo "🚨 ALERTA: AUTO_INCREMENT = {$autoIncrement} mas tabela vazia!\n";
                echo "   Isso indica que registros foram DELETADOS!\n";
            }
            
            // Verificar outras tabelas relacionadas
            echo "\n6. VERIFICANDO TABELAS RELACIONADAS:\n";
            $relatedTables = ['historico_status', 'denuncia_categoria', 'anexos'];
            foreach ($relatedTables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '{$table}'");
                if ($result->num_rows > 0) {
                    $result = $conn->query("SELECT COUNT(*) as total FROM {$table}");
                    $count = $result->fetch_assoc()['total'];
                    echo "   {$table}: {$count} registros\n";
                    
                    if ($count > 0 && $table === 'historico_status') {
                        echo "🚨 INCONSISTÊNCIA: Histórico existe mas denúncias não!\n";
                    }
                } else {
                    echo "   {$table}: tabela não existe\n";
                }
            }
            
            // Verificar logs recentes
            echo "\n7. VERIFICANDO LOGS MYSQL (se disponível):\n";
            $result = $conn->query("SHOW VARIABLES LIKE 'log_bin'");
            $binlog = $result->fetch_assoc();
            
            if ($binlog && $binlog['Value'] === 'ON') {
                echo "✅ Binary log habilitado - possível recuperar dados\n";
                $result = $conn->query("SHOW BINARY LOGS");
                echo "   Arquivos de binlog:\n";
                while ($row = $result->fetch_assoc()) {
                    echo "   - {$row['Log_name']} (" . round($row['File_size']/1024/1024, 2) . " MB)\n";
                }
            } else {
                echo "❌ Binary log desabilitado - recuperação limitada\n";
            }
            
        } else {
            echo "✅ Encontradas {$count} denúncias na tabela\n";
            
            // Mostrar algumas denúncias
            echo "\n4. ÚLTIMAS DENÚNCIAS:\n";
            $result = $conn->query("SELECT id, protocolo, data_criacao, status FROM denuncias ORDER BY id DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                echo "   ID: {$row['id']}, Protocolo: {$row['protocolo']}, Data: {$row['data_criacao']}, Status: {$row['status']}\n";
            }
        }
        
    } else {
        echo "❌ TABELA 'denuncias' NÃO EXISTE!\n";
        echo "🚨 PROBLEMA CRÍTICO: Estrutura do banco foi perdida!\n";
        
        // Listar todas as tabelas
        echo "\nTabelas existentes:\n";
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            echo "   - " . $row[0] . "\n";
        }
    }
    
    // Verificar informações do servidor
    echo "\n8. INFORMAÇÕES DO SERVIDOR MYSQL:\n";
    $result = $conn->query("SELECT VERSION() as version");
    $version = $result->fetch_assoc()['version'];
    echo "   Versão MySQL: " . $version . "\n";
    
    $result = $conn->query("SELECT NOW() as server_time");
    $serverTime = $result->fetch_assoc()['server_time'];
    echo "   Hora do servidor: " . $serverTime . "\n";
    
    $result = $conn->query("SELECT DATABASE() as current_db");
    $currentDb = $result->fetch_assoc()['current_db'];
    echo "   Banco atual: " . $currentDb . "\n";
    
} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "INVESTIGAÇÃO CONCLUÍDA\n";
echo "Hora: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n";
?>
