<?php
/**
 * INVESTIGAÇÃO DETALHADA DOS DADOS EXISTENTES
 */

// Definir BASE_PATH primeiro
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Configurar display de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 INVESTIGAÇÃO DETALHADA - DADOS EXISTENTES</h1>\n";
echo "<pre>\n";

// Carregar configurações
require_once '../config/config.php';

echo "Investigação em: " . date('Y-m-d H:i:s') . "\n";
echo "Banco: " . DB_HOST . " -> " . DB_NAME . "\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("❌ ERRO: " . $conn->connect_error . "\n");
    }
    
    echo "✅ Conectado ao banco\n\n";
    
    echo "1. ESTRUTURA DA TABELA DENUNCIAS:\n";
    $result = $conn->query("DESCRIBE denuncias");
    echo "Campo                | Tipo                   | Nulo | Chave | Padrão    | Extra\n";
    echo "------------------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        printf("%-18s | %-20s | %-4s | %-5s | %-9s | %s\n",
            $row['Field'],
            $row['Type'],
            $row['Null'],
            $row['Key'],
            $row['Default'] ?: 'NULL',
            $row['Extra']
        );
    }
    
    echo "\n2. REGISTROS EXISTENTES:\n";
    $result = $conn->query("SELECT * FROM denuncias ORDER BY id");
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $count++;
        echo "=== DENÚNCIA #{$count} ===\n";
        echo "ID: {$row['id']}\n";
        echo "Protocolo: {$row['protocolo']}\n";
        echo "Descrição: " . substr($row['descricao'], 0, 100) . (strlen($row['descricao']) > 100 ? "..." : "") . "\n";
        echo "Status: {$row['status']}\n";
        echo "Data Criação: {$row['data_criacao']}\n";
        echo "Data Ocorrência: " . ($row['data_ocorrencia'] ?? 'N/A') . "\n";
        echo "Local: " . ($row['local_ocorrencia'] ?? 'N/A') . "\n";
        echo "IP: " . ($row['ip_denunciante'] ?? 'N/A') . "\n";
        echo "Anexo: " . ($row['anexo'] ?? 'N/A') . "\n";
        echo "\n";
    }
    
    echo "3. VERIFICAÇÃO DE TABELAS RELACIONADAS:\n";
    
    // Verificar categorias
    $result = $conn->query("SHOW TABLES LIKE 'denuncia_categoria'");
    if ($result->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM denuncia_categoria");
        $catCount = $result->fetch_assoc()['total'];
        echo "   denuncia_categoria: {$catCount} registros\n";
        
        if ($catCount > 0) {
            $result = $conn->query("SELECT dc.*, d.protocolo FROM denuncia_categoria dc LEFT JOIN denuncias d ON dc.denuncia_id = d.id LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                echo "     Denúncia ID {$row['denuncia_id']} (Protocolo: {$row['protocolo']}) -> Categoria {$row['categoria_id']}\n";
            }
        }
    } else {
        echo "   denuncia_categoria: tabela não existe\n";
    }
    
    // Verificar histórico
    $result = $conn->query("SHOW TABLES LIKE 'historico_status'");
    if ($result->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM historico_status");
        $histCount = $result->fetch_assoc()['total'];
        echo "   historico_status: {$histCount} registros\n";
    } else {
        echo "   historico_status: tabela não existe\n";
    }
    
    // Verificar categorias
    $result = $conn->query("SHOW TABLES LIKE 'categorias'");
    if ($result->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM categorias");
        $catMasterCount = $result->fetch_assoc()['total'];
        echo "   categorias: {$catMasterCount} registros\n";
        
        if ($catMasterCount > 0) {
            echo "     Categorias disponíveis:\n";
            $result = $conn->query("SELECT id, nome FROM categorias LIMIT 10");
            while ($row = $result->fetch_assoc()) {
                echo "       ID {$row['id']}: {$row['nome']}\n";
            }
        }
    } else {
        echo "   categorias: tabela não existe\n";
    }
    
    echo "\n4. VERIFICAÇÃO AUTO_INCREMENT E PRÓXIMO ID:\n";
    $result = $conn->query("SHOW TABLE STATUS LIKE 'denuncias'");
    $status = $result->fetch_assoc();
    echo "   AUTO_INCREMENT atual: {$status['Auto_increment']}\n";
    echo "   Engine: {$status['Engine']}\n";
    echo "   Charset: {$status['Collation']}\n";
    echo "   Tamanho: " . number_format($status['Data_length']) . " bytes\n";
    
    echo "\n5. TESTANDO GERAÇÃO DE PROTOCOLO:\n";
    // Verificar tamanho máximo do campo protocolo
    $result = $conn->query("SHOW COLUMNS FROM denuncias LIKE 'protocolo'");
    $protocoloField = $result->fetch_assoc();
    echo "   Campo protocolo: {$protocoloField['Type']}\n";
    
    // Tentar gerar protocolo menor
    $protocoloTeste = strtoupper(substr(uniqid(), -8)); // 8 caracteres
    echo "   Protocolo teste: {$protocoloTeste} (tamanho: " . strlen($protocoloTeste) . ")\n";
    
    // Tentar inserir com protocolo menor
    $stmt = $conn->prepare("INSERT INTO denuncias (protocolo, descricao, status) VALUES (?, ?, 'Pendente')");
    $descricaoTeste = "Teste de inserção - " . date('Y-m-d H:i:s');
    $stmt->bind_param("ss", $protocoloTeste, $descricaoTeste);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        echo "   ✅ SUCESSO! Inserido com ID: {$newId}\n";
        
        // Verificar total após inserção
        $result = $conn->query("SELECT COUNT(*) as total FROM denuncias");
        $newTotal = $result->fetch_assoc()['total'];
        echo "   📊 Total após inserção: {$newTotal} registros\n";
        
    } else {
        echo "   ❌ ERRO na inserção: " . $stmt->error . "\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n</pre>";
echo "<p><strong>Investigação concluída em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
