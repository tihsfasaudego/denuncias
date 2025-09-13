<?php
/**
 * DIAGNÓSTICO URGENTE - Página em Branco
 * Execute via browser: https://192.168.2.20:8444/diagnostico_urgente.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DIAGNÓSTICO URGENTE - Página em Branco</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>";

echo "<h2>1. VERIFICAÇÕES BÁSICAS</h2>";

// Verificar se estamos na pasta correta
echo "<p><strong>Pasta atual:</strong> " . __DIR__ . "</p>";
echo "<p><strong>BASE_PATH:</strong> ";
define('BASE_PATH', __DIR__);
echo BASE_PATH . "</p>";

// Verificar arquivos essenciais
$arquivos = [
    'config/config.php',
    'app/Controllers/AdminDenunciaController.php',
    'app/Core/Router.php',
    'app/Core/RouteManager.php',
    'app/Views/admin/denuncias/visualizar.php',
    'app/Views/layouts/base.php'
];

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "<p class='ok'>✓ {$arquivo}</p>";
    } else {
        echo "<p class='error'>❌ {$arquivo} - NÃO ENCONTRADO</p>";
    }
}

echo "<h2>2. CARREGANDO CONFIGURAÇÃO</h2>";

try {
    require_once 'config/config.php';
    echo "<p class='ok'>✓ config.php carregado</p>";
    echo "<p>APP_DEBUG: " . (defined('APP_DEBUG') ? APP_DEBUG : 'não definido') . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao carregar config: " . $e->getMessage() . "</p>";
}

echo "<h2>3. TESTANDO AUTO-LOADER</h2>";

// Auto-loader
spl_autoload_register(function ($className) {
    $dirs = ['app/Controllers/', 'app/Models/', 'app/Core/', 'app/Config/'];
    foreach ($dirs as $dir) {
        $file = BASE_PATH . '/' . $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            echo "<p class='ok'>✓ Carregado: {$className}</p>";
            return;
        }
    }
    echo "<p class='error'>❌ Não encontrado: {$className}</p>";
});

echo "<h2>4. TESTANDO CLASSES</h2>";

$classes = ['Database', 'Denuncia', 'Auth', 'AuthMiddleware', 'AdminDenunciaController'];

foreach ($classes as $classe) {
    try {
        if (class_exists($classe)) {
            echo "<p class='ok'>✓ Classe {$classe} existe</p>";
            
            if ($classe === 'AdminDenunciaController') {
                $controller = new $classe();
                echo "<p class='ok'>✓ {$classe} instanciado com sucesso</p>";
            }
        } else {
            echo "<p class='error'>❌ Classe {$classe} não existe</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro com {$classe}: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>5. TESTANDO BANCO DE DADOS</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='ok'>✓ Conexão com banco estabelecida</p>";
    
    $result = $db->query("SELECT id, protocolo, status FROM denuncias ORDER BY id LIMIT 3");
    $denuncias = [];
    while ($row = $result->fetch_assoc()) {
        $denuncias[] = $row;
    }
    
    if (!empty($denuncias)) {
        echo "<p class='ok'>✓ " . count($denuncias) . " denúncias encontradas</p>";
        foreach ($denuncias as $d) {
            echo "<p>  - ID: {$d['id']}, Protocolo: {$d['protocolo']}</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Nenhuma denúncia encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no banco: " . $e->getMessage() . "</p>";
}

echo "<h2>6. SIMULANDO AMBIENTE DE REQUISIÇÃO</h2>";

// Simular sessão
session_start();
$_SESSION['admin'] = [
    'id' => 1,
    'nome' => 'Administrador',
    'usuario' => 'admin',
    'nivel_acesso' => 'admin'
];
$_SESSION['admin_last_activity'] = time();

echo "<p class='ok'>✓ Sessão admin configurada</p>";

// Simular requisição
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/admin/denuncia/6';

echo "<p class='ok'>✓ REQUEST_URI configurado: " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>7. TESTANDO CONTROLLER DIRETAMENTE</h2>";

if (!empty($denuncias)) {
    $denunciaId = $denuncias[0]['id'];
    
    try {
        $controller = new AdminDenunciaController();
        echo "<p>Testando com ID: {$denunciaId}</p>";
        
        // Capturar toda a saída
        ob_start();
        $controller->show(['id' => $denunciaId]);
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "<p class='ok'>✓ Controller retornou " . strlen($output) . " caracteres</p>";
            
            // Verificar se o output contém HTML válido
            if (strpos($output, '<html') !== false || strpos($output, '<!DOCTYPE') !== false) {
                echo "<p class='ok'>✓ Output contém HTML válido</p>";
            } else {
                echo "<p class='warning'>⚠️ Output pode não ser HTML completo</p>";
            }
            
            // Mostrar primeiros caracteres
            echo "<h3>Primeiros 500 caracteres do output:</h3>";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
            
            // Verificar erros comuns
            if (strpos($output, 'Fatal error') !== false) {
                echo "<p class='error'>❌ Fatal error detectado no output</p>";
            }
            if (strpos($output, 'Parse error') !== false) {
                echo "<p class='error'>❌ Parse error detectado no output</p>";
            }
            if (strpos($output, 'Warning') !== false) {
                echo "<p class='warning'>⚠️ Warning detectado no output</p>";
            }
            
        } else {
            echo "<p class='error'>❌ Controller não retornou nenhum conteúdo</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro no controller: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p class='error'>❌ Não foi possível testar: nenhuma denúncia disponível</p>";
}

echo "<h2>8. TESTANDO ROTEAMENTO</h2>";

try {
    $routeManager = new RouteManager();
    echo "<p class='ok'>✓ RouteManager criado</p>";
    
    $routeManager->registerRoutes();
    echo "<p class='ok'>✓ Rotas registradas</p>";
    
    // Testar se a rota específica está registrada
    echo "<p>Tentando executar roteamento...</p>";
    
    ob_start();
    $routeManager->run();
    $routeOutput = ob_get_clean();
    
    if (!empty($routeOutput)) {
        echo "<p class='ok'>✓ RouteManager retornou " . strlen($routeOutput) . " caracteres</p>";
        echo "<h3>Primeiros 300 caracteres do roteamento:</h3>";
        echo "<pre>" . htmlspecialchars(substr($routeOutput, 0, 300)) . "</pre>";
    } else {
        echo "<p class='error'>❌ RouteManager não retornou conteúdo</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no roteamento: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>9. VERIFICANDO LOGS</h2>";

$logFile = 'logs/error.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $linhas = explode("\n", $logs);
    $linhasRecentes = array_slice($linhas, -15);
    
    echo "<p class='ok'>✓ Log de erro encontrado</p>";
    echo "<h3>Últimas 15 linhas do log:</h3>";
    echo "<pre>";
    foreach ($linhasRecentes as $linha) {
        if (!empty(trim($linha))) {
            echo htmlspecialchars($linha) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p class='warning'>⚠️ Arquivo de log não encontrado</p>";
}

echo "<h2>🎯 PRÓXIMOS PASSOS</h2>";
echo "<p>1. Execute este diagnóstico e veja onde está falhando</p>";
echo "<p>2. Se o controller funcionar aqui mas não no browser, o problema é no servidor web</p>";
echo "<p>3. Verifique os logs do Apache/Nginx</p>";
echo "<p>4. Teste a página simples abaixo</p>";

echo "<h2>🔗 LINKS DE TESTE</h2>";
echo "<p><a href='/teste_simples_denuncia.php'>Teste Simples</a></p>";
if (!empty($denuncias)) {
    echo "<p><a href='/admin/denuncia/{$denuncias[0]['id']}'>Rota Original</a></p>";
}
echo "<p><a href='/admin/dashboard'>Dashboard</a></p>";
?>
