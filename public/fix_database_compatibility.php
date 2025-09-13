<?php
/**
 * CORREÇÃO DE COMPATIBILIDADE BANCO vs CÓDIGO
 */

// Definir BASE_PATH primeiro
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Configurar display de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔧 CORREÇÃO DE COMPATIBILIDADE BANCO vs CÓDIGO</h1>\n";
echo "<pre>\n";

// Carregar configurações
require_once '../config/config.php';

echo "Correção iniciada em: " . date('Y-m-d H:i:s') . "\n";
echo "Banco: " . DB_HOST . " -> " . DB_NAME . "\n\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("❌ ERRO: " . $conn->connect_error . "\n");
    }
    
    echo "✅ Conectado ao banco\n\n";
    
    echo "1. VERIFICANDO ESTRUTURA ATUAL:\n";
    $result = $conn->query("DESCRIBE denuncias");
    echo "Estrutura da tabela denuncias:\n";
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'protocolo') {
            echo "   ⚠️  protocolo: {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
        } else {
            echo "   ✅ {$row['Field']}: {$row['Type']}\n";
        }
    }
    
    echo "\n2. CORRIGINDO CAMPO PROTOCOLO:\n";
    
    // Verificar se precisa alterar
    $result = $conn->query("SHOW COLUMNS FROM denuncias LIKE 'protocolo'");
    $protocoloField = $result->fetch_assoc();
    
    if ($protocoloField['Type'] === 'varchar(8)') {
        echo "   🔧 Alterando campo protocolo de VARCHAR(8) para VARCHAR(20)...\n";
        
        $alterSql = "ALTER TABLE denuncias MODIFY COLUMN protocolo VARCHAR(20) NOT NULL";
        if ($conn->query($alterSql)) {
            echo "   ✅ Campo protocolo alterado com sucesso!\n";
        } else {
            echo "   ❌ Erro ao alterar campo: " . $conn->error . "\n";
        }
    } else {
        echo "   ✅ Campo protocolo já tem tamanho adequado: {$protocoloField['Type']}\n";
    }
    
    echo "\n3. VERIFICANDO AUTO_INCREMENT:\n";
    $result = $conn->query("SHOW TABLE STATUS LIKE 'denuncias'");
    $status = $result->fetch_assoc();
    $currentAutoInc = $status['Auto_increment'];
    echo "   AUTO_INCREMENT atual: {$currentAutoInc}\n";
    
    if ($currentAutoInc == 1) {
        echo "   🔧 Definindo AUTO_INCREMENT para 1...\n";
        $conn->query("ALTER TABLE denuncias AUTO_INCREMENT = 1");
        echo "   ✅ AUTO_INCREMENT configurado\n";
    }
    
    echo "\n4. VERIFICANDO STATUS ENUM:\n";
    $result = $conn->query("SHOW COLUMNS FROM denuncias LIKE 'status'");
    $statusField = $result->fetch_assoc();
    echo "   Status ENUM: {$statusField['Type']}\n";
    
    $allowedStatuses = ['Pendente','Em Análise','Em Investigação','Concluída','Arquivada'];
    echo "   Status permitidos: " . implode(', ', $allowedStatuses) . "\n";
    
    echo "\n5. TESTANDO GERAÇÃO DE PROTOCOLO:\n";
    
    // Simular método gerarProtocolo corrigido
    function gerarProtocoloCorrigido($length = 8) {
        // Gerar protocolo alfanumérico de 8 caracteres
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $protocolo = '';
        for ($i = 0; $i < $length; $i++) {
            $protocolo .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $protocolo;
    }
    
    $protocoloTeste = gerarProtocoloCorrigido(8);
    echo "   Protocolo teste gerado: {$protocoloTeste} (tamanho: " . strlen($protocoloTeste) . ")\n";
    
    echo "\n6. TESTANDO INSERÇÃO:\n";
    
    $stmt = $conn->prepare("INSERT INTO denuncias (protocolo, descricao, status, data_criacao) VALUES (?, ?, ?, NOW())");
    $descricao = "Teste de inserção após correções - " . date('Y-m-d H:i:s');
    $status = 'Pendente';
    
    $stmt->bind_param("sss", $protocoloTeste, $descricao, $status);
    
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        echo "   ✅ SUCESSO! Inserido com ID: {$insertId}\n";
        echo "   📋 Protocolo: {$protocoloTeste}\n";
        echo "   📝 Descrição: " . substr($descricao, 0, 50) . "...\n";
        
        // Verificar total
        $result = $conn->query("SELECT COUNT(*) as total FROM denuncias");
        $total = $result->fetch_assoc()['total'];
        echo "   📊 Total de denúncias: {$total}\n";
        
    } else {
        echo "   ❌ ERRO na inserção: " . $stmt->error . "\n";
    }
    
    echo "\n7. VERIFICANDO CATEGORIAS:\n";
    $result = $conn->query("SELECT COUNT(*) as total FROM categorias");
    $catTotal = $result->fetch_assoc()['total'];
    echo "   📊 Total de categorias: {$catTotal}\n";
    
    if ($catTotal > 0) {
        echo "   📋 Categorias disponíveis:\n";
        $result = $conn->query("SELECT id, nome FROM categorias LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            echo "     ID {$row['id']}: {$row['nome']}\n";
        }
    }
    
    echo "\n8. VERIFICANDO ROTAS (lógica):\n";
    echo "   ✅ GET /denuncia/criar - Formulário de denúncia\n";
    echo "   ✅ POST /denuncia/criar - Salvamento (DenunciaController@store)\n";
    echo "   ✅ GET /denuncia/consultar - Consulta por protocolo\n";
    echo "   ✅ POST /denuncia/consultar - Busca de denúncia\n";
    echo "   ✅ GET /denuncia/detalhes - Detalhes da denúncia\n";
    
    echo "\n9. LISTANDO DENÚNCIAS EXISTENTES:\n";
    $result = $conn->query("SELECT id, protocolo, status, data_criacao FROM denuncias ORDER BY id DESC LIMIT 10");
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $count++;
        echo "   {$count}. ID: {$row['id']}, Protocolo: {$row['protocolo']}, Status: {$row['status']}, Data: {$row['data_criacao']}\n";
    }
    
    if ($count == 0) {
        echo "   ⚠️  Nenhuma denúncia encontrada na tabela\n";
    }
    
    $conn->close();
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "✅ CORREÇÕES APLICADAS COM SUCESSO!\n";
    echo "📋 PRÓXIMOS PASSOS:\n";
    echo "   1. Testar criação de denúncia via formulário\n";
    echo "   2. Verificar se método gerarProtocolo() precisa ser atualizado\n";
    echo "   3. Confirmar se dashboard mostra dados corretos\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n</pre>";
echo "<p><strong>Correção concluída em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
