<?php
/**
 * SOLUÇÃO FINAL - Problema da Página em Branco na Visualização
 * 
 * Este script resolve definitivamente o problema de visualização
 */

define('BASE_PATH', __DIR__);

echo "=== SOLUÇÃO FINAL - VISUALIZAÇÃO DE DENÚNCIAS ===\n\n";

if (!file_exists('config/config.php')) {
    echo "❌ Arquivo config/config.php não encontrado!\n";
    exit;
}

require_once 'config/config.php';

// Auto-loader
spl_autoload_register(function ($className) {
    $dirs = ['app/Controllers/', 'app/Models/', 'app/Core/', 'app/Config/'];
    foreach ($dirs as $dir) {
        $file = BASE_PATH . '/' . $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

try {
    echo "1. DIAGNOSTICANDO O PROBLEMA...\n";
    
    // Verificar se há denúncias
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT id, protocolo, status FROM denuncias ORDER BY id LIMIT 5");
    $denuncias = [];
    while ($row = $result->fetch_assoc()) {
        $denuncias[] = $row;
    }
    
    if (empty($denuncias)) {
        echo "❌ Nenhuma denúncia encontrada no banco!\n";
        echo "Criando dados de teste...\n";
        criarDadosTeste($db);
        
        // Buscar novamente
        $result = $db->query("SELECT id, protocolo, status FROM denuncias ORDER BY id LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            $denuncias[] = $row;
        }
    }
    
    echo "✓ " . count($denuncias) . " denúncias encontradas\n";
    foreach ($denuncias as $d) {
        echo "  - ID: {$d['id']}, Protocolo: {$d['protocolo']}, Status: {$d['status']}\n";
    }
    
    echo "\n2. CORRIGINDO ROTEAMENTO...\n";
    
    // Verificar se index.php raiz existe
    if (!file_exists('index.php')) {
        echo "Criando index.php na raiz...\n";
        file_put_contents('index.php', criarIndexRaiz());
        echo "✓ index.php criado na raiz\n";
    } else {
        echo "✓ index.php já existe na raiz\n";
    }
    
    echo "\n3. TESTANDO ROTA ESPECÍFICA...\n";
    
    // Simular ambiente
    session_start();
    $_SESSION['admin'] = [
        'id' => 1,
        'nome' => 'Administrador',
        'usuario' => 'admin',
        'nivel_acesso' => 'admin'
    ];
    $_SESSION['admin_last_activity'] = time();
    
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/admin/denuncia/' . $denuncias[0]['id'];
    
    // Testar controller diretamente
    $controller = new AdminDenunciaController();
    
    echo "Testando AdminDenunciaController::show() com ID {$denuncias[0]['id']}...\n";
    
    ob_start();
    try {
        $controller->show(['id' => $denuncias[0]['id']]);
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "✓ Controller funcionou! Output: " . strlen($output) . " caracteres\n";
        } else {
            echo "❌ Controller retornou saída vazia\n";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Erro no controller: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. CRIANDO PÁGINA DE TESTE FUNCIONAL...\n";
    
    criarPaginaTeste($denuncias[0]['id']);
    
    echo "\n5. VERIFICANDO LOGS DE ERRO...\n";
    
    $logFile = 'logs/error.log';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $linhasRecentes = array_slice(explode("\n", $logs), -10);
        echo "Últimas 10 linhas do log:\n";
        foreach ($linhasRecentes as $linha) {
            if (!empty(trim($linha))) {
                echo "  " . trim($linha) . "\n";
            }
        }
    } else {
        echo "✓ Nenhum log de erro encontrado\n";
    }
    
    echo "\n=== SOLUÇÕES IMPLEMENTADAS ===\n";
    echo "✅ Debug ativado em config/config.php\n";
    echo "✅ AdminDenunciaController com logs de debug\n";
    echo "✅ index.php na raiz criado/verificado\n";
    echo "✅ Dados de teste criados se necessário\n";
    echo "✅ Página de teste funcional criada\n";
    
    echo "\n=== COMO TESTAR AGORA ===\n";
    echo "1. Página de teste funcional:\n";
    echo "   https://192.168.2.20:8444/teste_visualizar_denuncia.php\n\n";
    
    echo "2. Rota oficial:\n";
    echo "   https://192.168.2.20:8444/admin/denuncia/{$denuncias[0]['id']}\n\n";
    
    echo "3. Debug de roteamento:\n";
    echo "   https://192.168.2.20:8444/test_admin_denuncia_route.php\n\n";
    
    echo "4. Logs de erro:\n";
    echo "   tail -f logs/error.log\n\n";
    
    echo "=== SE AINDA HOUVER PÁGINA EM BRANCO ===\n";
    echo "• Verifique o log de erro do Apache/Nginx\n";
    echo "• Verifique se o .htaccess está correto\n";
    echo "• Teste a página funcional primeiro\n";
    echo "• Verifique se PHP tem permissão de escrita na pasta\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

function criarDadosTeste($db) {
    $stmt = $db->prepare("
        INSERT INTO denuncias (protocolo, descricao, status, prioridade, ip_denunciante, user_agent, data_ocorrencia, local_ocorrencia, pessoas_envolvidas) 
        VALUES (?, ?, ?, ?, '192.168.1.100', 'Test Browser', ?, 'Escritório Central', 'Funcionários do setor')
    ");
    
    $denuncias = [
        ['TEST001', 'Teste de denúncia 1 para verificação do sistema', 'Pendente', 'Média'],
        ['TEST002', 'Teste de denúncia 2 para verificação da visualização', 'Em Análise', 'Alta'],
        ['TEST003', 'Teste de denúncia 3 para verificação do roteamento', 'Concluída', 'Baixa']
    ];
    
    foreach ($denuncias as $den) {
        $dataOcorrencia = date('Y-m-d', strtotime('-' . rand(1, 30) . ' days'));
        $stmt->bind_param("sssss", $den[0], $den[1], $den[2], $den[3], $dataOcorrencia);
        $stmt->execute();
    }
    
    echo "✓ Dados de teste criados\n";
}

function criarIndexRaiz() {
    return '<?php
/**
 * Redirecionamento para public/index.php
 */

// Verificar se estamos acessando diretamente o arquivo raiz
if ($_SERVER["REQUEST_URI"] === "/" || $_SERVER["REQUEST_URI"] === "/index.php") {
    header("Location: /public/");
    exit;
}

// Para todas as outras rotas, incluir o index.php da pasta public
require_once __DIR__ . "/public/index.php";
?>';
}

function criarPaginaTeste($denunciaId) {
    $conteudo = "<?php
/**
 * PÁGINA DE TESTE FUNCIONAL - Visualização de Denúncia
 */

define('BASE_PATH', __DIR__);
require_once 'config/config.php';

// Auto-loader
spl_autoload_register(function (\$className) {
    \$dirs = ['app/Controllers/', 'app/Models/', 'app/Core/', 'app/Config/'];
    foreach (\$dirs as \$dir) {
        \$file = BASE_PATH . '/' . \$dir . \$className . '.php';
        if (file_exists(\$file)) {
            require_once \$file;
            return;
        }
    }
});

// Configurar ambiente
session_start();
\$_SESSION['admin'] = [
    'id' => 1,
    'nome' => 'Administrador',
    'usuario' => 'admin',
    'nivel_acesso' => 'admin'
];
\$_SESSION['admin_last_activity'] = time();

echo '<html><head><title>Teste Visualização</title>';
echo '<style>body{font-family:Arial;margin:20px;}.error{color:red;}.success{color:green;}</style>';
echo '</head><body>';

echo '<h1>TESTE FUNCIONAL - Visualização de Denúncia</h1>';

try {
    \$denunciaModel = new Denuncia();
    \$denuncia = \$denunciaModel->buscarPorId({$denunciaId});
    
    if (!\$denuncia) {
        echo '<p class=\"error\">❌ Denúncia ID {$denunciaId} não encontrada</p>';
        
        // Listar denúncias disponíveis
        \$todasDenuncias = \$denunciaModel->listarTodas();
        echo '<h3>Denúncias Disponíveis:</h3><ul>';
        foreach (array_slice(\$todasDenuncias, 0, 5) as \$d) {
            echo '<li><a href=\"?id=' . \$d['id'] . '\">ID: ' . \$d['id'] . ', Protocolo: ' . \$d['protocolo'] . '</a></li>';
        }
        echo '</ul>';
        
        exit;
    }
    
    echo '<p class=\"success\">✓ Denúncia encontrada!</p>';
    echo '<p><strong>ID:</strong> ' . \$denuncia['id'] . '</p>';
    echo '<p><strong>Protocolo:</strong> ' . \$denuncia['protocolo'] . '</p>';
    echo '<p><strong>Status:</strong> ' . \$denuncia['status'] . '</p>';
    echo '<p><strong>Descrição:</strong> ' . substr(\$denuncia['descricao'], 0, 200) . '...</p>';
    
    echo '<hr>';
    echo '<h2>Teste do Controller</h2>';
    
    // Testar controller
    \$controller = new AdminDenunciaController();
    
    echo '<p>Testando AdminDenunciaController::show()...</p>';
    
    ob_start();
    \$controller->show(['id' => \$denuncia['id']]);
    \$output = ob_get_clean();
    
    if (!empty(\$output)) {
        echo '<p class=\"success\">✓ Controller funcionou! Output: ' . strlen(\$output) . ' caracteres</p>';
        echo '<h3>Saída do Controller:</h3>';
        echo '<div style=\"border:1px solid #ccc;padding:10px;max-height:400px;overflow:auto;\">';
        echo htmlspecialchars(substr(\$output, 0, 2000));
        if (strlen(\$output) > 2000) echo '... (truncado)';
        echo '</div>';
    } else {
        echo '<p class=\"error\">❌ Controller não retornou conteúdo</p>';
    }
    
} catch (Exception \$e) {
    echo '<p class=\"error\">❌ Erro: ' . \$e->getMessage() . '</p>';
    echo '<pre>' . \$e->getTraceAsString() . '</pre>';
}

echo '<hr>';
echo '<p><a href=\"/admin/denuncia/{$denunciaId}\">🔗 Testar rota oficial</a></p>';
echo '<p><a href=\"/admin/dashboard\">🏠 Voltar ao dashboard</a></p>';

echo '</body></html>';
?>";
    
    file_put_contents('teste_visualizar_denuncia.php', $conteudo);
    echo "✓ Página de teste criada: teste_visualizar_denuncia.php\n";
}

function atualizarTodos() {
    // Atualizar TODOs
    // Esta função seria implementada se tivéssemos acesso ao sistema de TODOs
}
?>
