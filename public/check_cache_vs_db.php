<?php
/**
 * VERIFICAR SE DADOS ESTÃO EM CACHE VS BANCO
 */

// Definir BASE_PATH primeiro
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Configurar display de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 CACHE vs BANCO DE DADOS</h1>\n";
echo "<pre>\n";

// Carregar configurações
require_once '../config/config.php';
require_once '../app/Core/Database.php';
require_once '../app/Core/Cache.php';
require_once '../app/Models/Denuncia.php';

echo "Verificação em: " . date('Y-m-d H:i:s') . "\n\n";

echo "1. VERIFICANDO BANCO DE DADOS DIRETAMENTE:\n";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("❌ ERRO: " . $conn->connect_error . "\n");
    }
    
    // Contar registros direto no banco
    $result = $conn->query("SELECT COUNT(*) as total FROM denuncias");
    $dbCount = $result->fetch_assoc()['total'];
    echo "📊 Total no BANCO: {$dbCount} registros\n";
    
    if ($dbCount > 0) {
        echo "📋 Últimas denúncias no BANCO:\n";
        $result = $conn->query("SELECT id, protocolo, data_criacao, status FROM denuncias ORDER BY id DESC LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            echo "   ID: {$row['id']}, Protocolo: {$row['protocolo']}, Data: {$row['data_criacao']}\n";
        }
    }
    
} catch (Exception $e) {
    die("❌ ERRO no banco: " . $e->getMessage() . "\n");
}

echo "\n2. VERIFICANDO VIA MODELO (COM CACHE):\n";
try {
    $denunciaModel = new Denuncia();
    
    // Listar com cache
    $denunciasComCache = $denunciaModel->listarTodas(true);
    echo "📊 Total via MODELO COM CACHE: " . count($denunciasComCache) . " registros\n";
    
    if (count($denunciasComCache) > 0) {
        echo "📋 Primeiras denúncias (com cache):\n";
        foreach (array_slice($denunciasComCache, 0, 5) as $denuncia) {
            echo "   ID: {$denuncia['id']}, Protocolo: {$denuncia['protocolo']}, Data: {$denuncia['data_criacao']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERRO no modelo com cache: " . $e->getMessage() . "\n";
}

echo "\n3. VERIFICANDO VIA MODELO (SEM CACHE):\n";
try {
    // Listar sem cache
    $denunciasSemCache = $denunciaModel->listarTodas(false);
    echo "📊 Total via MODELO SEM CACHE: " . count($denunciasSemCache) . " registros\n";
    
    if (count($denunciasSemCache) > 0) {
        echo "📋 Primeiras denúncias (sem cache):\n";
        foreach (array_slice($denunciasSemCache, 0, 5) as $denuncia) {
            echo "   ID: {$denuncia['id']}, Protocolo: {$denuncia['protocolo']}, Data: {$denuncia['data_criacao']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERRO no modelo sem cache: " . $e->getMessage() . "\n";
}

echo "\n4. VERIFICANDO SISTEMA DE CACHE:\n";
try {
    $cache = Cache::getInstance();
    echo "✅ Cache carregado\n";
    
    // Verificar se existe cache das denúncias
    $cacheKey = 'denuncias_lista';
    $cachedData = $cache->get($cacheKey);
    
    if ($cachedData !== null) {
        echo "📦 CACHE ENCONTRADO para '{$cacheKey}'\n";
        echo "   Tipo: " . gettype($cachedData) . "\n";
        if (is_array($cachedData)) {
            echo "   Itens no cache: " . count($cachedData) . "\n";
        }
    } else {
        echo "🚫 NENHUM CACHE encontrado para '{$cacheKey}'\n";
    }
    
    // Verificar estatísticas do cache
    $stats = $cache->getStats();
    echo "📈 Estatísticas do cache:\n";
    foreach ($stats as $key => $value) {
        echo "   {$key}: {$value}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO no cache: " . $e->getMessage() . "\n";
}

echo "\n5. VERIFICANDO DASHBOARD DATA:\n";
try {
    // Verificar método específico do dashboard
    $dashboardStats = $denunciaModel->getStatsForDashboard();
    echo "📊 Stats do Dashboard:\n";
    foreach ($dashboardStats as $key => $value) {
        echo "   {$key}: {$value}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO no dashboard: " . $e->getMessage() . "\n";
}

echo "\n6. INVESTIGANDO POSSÍVEL PROBLEMA:\n";

// Comparar resultados
if ($dbCount == 0 && (count($denunciasComCache ?? []) > 0 || count($denunciasSemCache ?? []) > 0)) {
    echo "🚨 PROBLEMA IDENTIFICADO: Banco vazio mas modelo retorna dados!\n";
    echo "   Isso indica que os dados estão vindo de CACHE ou VIEW.\n";
} elseif ($dbCount > 0 && (count($denunciasComCache ?? []) == 0 && count($denunciasSemCache ?? []) == 0)) {
    echo "🚨 PROBLEMA IDENTIFICADO: Banco tem dados mas modelo não retorna!\n";
    echo "   Isso indica problema na QUERY do modelo.\n";
} elseif ($dbCount == 0 && count($denunciasComCache ?? []) == 0 && count($denunciasSemCache ?? []) == 0) {
    echo "✅ CONSISTENTE: Banco e modelo ambos vazios.\n";
    echo "   O problema é que as denúncias NÃO estão sendo salvas.\n";
} else {
    echo "✅ CONSISTENTE: Banco e modelo com mesma quantidade.\n";
}

$conn->close();

echo "\n</pre>";
echo "<p><strong>Verificação concluída em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>