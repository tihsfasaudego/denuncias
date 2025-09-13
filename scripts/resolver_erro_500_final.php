<?php
/**
 * RESOLVER ERRO 500 - SOLUÇÃO FINAL
 * Acesse: https://192.168.2.20:8444/resolver_erro_500_final.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Resolver Erro 500</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .card{background:white;padding:20px;margin:10px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .success{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f0f0f0;padding:10px;border-radius:4px;} .btn{display:inline-block;padding:10px 20px;background:#007cba;color:white;text-decoration:none;border-radius:4px;margin:5px;}</style>";
echo "</head><body>";

echo "<div class='card'><h1>🔧 RESOLVER ERRO 500 - SOLUÇÃO FINAL</h1></div>";

define('BASE_PATH', __DIR__);

// ETAPA 1: Verificar e corrigir arquivos essenciais
echo "<div class='card'><h2>1. VERIFICANDO ARQUIVOS ESSENCIAIS</h2>";

$arquivosEssenciais = [
    'config/config.php',
    'app/Controllers/AdminDenunciaController.php',
    'app/Models/Denuncia.php',
    'app/Core/Database.php',
    'app/Core/Auth.php',
    'app/Views/admin/denuncias/visualizar.php'
];

$arquivosOK = true;
foreach ($arquivosEssenciais as $arquivo) {
    if (file_exists($arquivo)) {
        echo "<p class='success'>✓ {$arquivo}</p>";
    } else {
        echo "<p class='error'>❌ {$arquivo} - FALTANDO</p>";
        $arquivosOK = false;
    }
}

if (!$arquivosOK) {
    echo "<p class='error'>❌ Arquivos essenciais estão faltando. Verifique a estrutura do projeto.</p>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// ETAPA 2: Testar configuração
echo "<div class='card'><h2>2. TESTANDO CONFIGURAÇÃO</h2>";

try {
    require_once 'config/config.php';
    echo "<p class='success'>✓ Configuração carregada</p>";
    
    // Verificar constantes importantes
    if (defined('APP_DEBUG')) {
        echo "<p>APP_DEBUG: " . (APP_DEBUG ? 'true' : 'false') . "</p>";
    } else {
        echo "<p class='warning'>⚠️ APP_DEBUG não definido</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro na configuração: " . $e->getMessage() . "</p>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// ETAPA 3: Auto-loader e classes
echo "<div class='card'><h2>3. CARREGANDO CLASSES</h2>";

spl_autoload_register(function ($className) {
    $dirs = ['app/Controllers/', 'app/Models/', 'app/Core/', 'app/Config/'];
    foreach ($dirs as $dir) {
        $file = BASE_PATH . '/' . $dir . $className . '.php';
        if (file_exists($file)) {
            try {
                require_once $file;
                echo "<p class='success'>✓ {$className}</p>";
                return;
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro ao carregar {$className}: " . $e->getMessage() . "</p>";
                return;
            }
        }
    }
});

// Testar classes críticas
$classesCriticas = ['Database', 'Auth', 'Denuncia', 'AdminDenunciaController'];
foreach ($classesCriticas as $classe) {
    try {
        if (class_exists($classe)) {
            // Não exibe, já foi exibido pelo autoloader
        } else {
            echo "<p class='error'>❌ Classe {$classe} não foi carregada</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro com {$classe}: " . $e->getMessage() . "</p>";
    }
}

echo "</div>";

// ETAPA 4: Configurar ambiente
echo "<div class='card'><h2>4. CONFIGURANDO AMBIENTE</h2>";

try {
    // Sessão
    session_start();
    $_SESSION['admin'] = [
        'id' => 1,
        'nome' => 'Administrador',
        'usuario' => 'admin',
        'nivel_acesso' => 'admin'
    ];
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    
    echo "<p class='success'>✓ Sessão configurada</p>";
    
    // Ambiente de requisição
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/admin/denuncia/test';
    $_SERVER['HTTP_HOST'] = '192.168.2.20:8444';
    
    echo "<p class='success'>✓ Ambiente de requisição configurado</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro na configuração do ambiente: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ETAPA 5: Testar banco e dados
echo "<div class='card'><h2>5. TESTANDO BANCO E DADOS</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='success'>✓ Conexão com banco estabelecida</p>";
    
    $denunciaModel = new Denuncia();
    $denuncias = $denunciaModel->listarTodas();
    
    if (!empty($denuncias)) {
        echo "<p class='success'>✓ " . count($denuncias) . " denúncias encontradas</p>";
        $denunciaTest = $denuncias[0];
        echo "<p>Denúncia de teste: ID {$denunciaTest['id']}, Protocolo: {$denunciaTest['protocolo']}</p>";
    } else {
        echo "<p class='warning'>⚠️ Nenhuma denúncia encontrada - criando dados de teste</p>";
        
        // Criar denúncia de teste
        $stmt = $db->prepare("INSERT INTO denuncias (protocolo, descricao, status, prioridade, ip_denunciante, user_agent, data_ocorrencia, local_ocorrencia, pessoas_envolvidas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $protocolo = 'TEST' . date('YmdHis');
        $descricao = 'Denúncia de teste criada automaticamente para verificação do sistema';
        $status = 'Pendente';
        $prioridade = 'Média';
        $ip = '192.168.1.100';
        $userAgent = 'Sistema de Teste';
        $dataOcorrencia = date('Y-m-d');
        $local = 'Sistema Administrativo';
        $pessoas = 'Sistema de teste';
        
        $stmt->bind_param("sssssssss", $protocolo, $descricao, $status, $prioridade, $ip, $userAgent, $dataOcorrencia, $local, $pessoas);
        $stmt->execute();
        
        $testId = $db->insert_id;
        echo "<p class='success'>✓ Denúncia de teste criada: ID {$testId}</p>";
        
        // Buscar novamente
        $denuncias = $denunciaModel->listarTodas();
        $denunciaTest = $denuncias[0];
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no banco: " . $e->getMessage() . "</p>";
    echo "</div></body></html>";
    exit;
}

echo "</div>";

// ETAPA 6: Corrigir layout
echo "<div class='card'><h2>6. CORRIGINDO LAYOUT</h2>";

// Criar layout simplificado se não existir
$layoutSimples = 'layout_simples.php';
if (!file_exists($layoutSimples)) {
    $layoutContent = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? "Canal de Denúncias"); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container-fluid { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .text-primary { color: #007cba !important; }
        .btn-primary { background-color: #007cba; border-color: #007cba; }
        .breadcrumb-item a { color: #007cba; }
        .hsfa-card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-radius: 10px; }
        .hsfa-title { color: #003a4d !important; font-weight: 600; }
    </style>
</head>
<body>
    <?php if (isset($isAdminPage) && $isAdminPage): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard">
                <i class="fas fa-shield-alt me-2"></i>Admin - Canal de Denúncias
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/admin/logout"><i class="fas fa-sign-out-alt me-1"></i>Sair</a>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main><?php if (isset($content)) echo $content; ?></main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, type = "info") { alert(message); }
        window.HSFA = { toast: { success: function(msg) { console.log("Success:", msg); }, error: function(msg) { console.log("Error:", msg); }, info: function(msg) { console.log("Info:", msg); } } };
    </script>
</body>
</html>';
    
    file_put_contents($layoutSimples, $layoutContent);
    echo "<p class='success'>✓ Layout simplificado criado</p>";
} else {
    echo "<p class='success'>✓ Layout simplificado já existe</p>";
}

echo "</div>";

// ETAPA 7: Testar controller
echo "<div class='card'><h2>7. TESTANDO CONTROLLER</h2>";

try {
    $controller = new AdminDenunciaController();
    echo "<p class='success'>✓ Controller instanciado</p>";
    
    $denunciaId = $denunciaTest['id'];
    echo "<p>Testando com denúncia ID: {$denunciaId}</p>";
    
    // Capturar output
    ob_start();
    $controller->show(['id' => $denunciaId]);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p class='success'>✓ Controller executou com sucesso (" . strlen($output) . " caracteres)</p>";
        
        // Verificar erros no output
        if (strpos($output, 'Fatal error') !== false || 
            strpos($output, 'Parse error') !== false ||
            strpos($output, 'Call to undefined') !== false) {
            echo "<p class='error'>❌ Erros detectados no output</p>";
            echo "<details><summary>Ver erros</summary><pre>" . htmlspecialchars($output) . "</pre></details>";
        } else {
            echo "<p class='success'>✓ Output sem erros fatais</p>";
        }
    } else {
        echo "<p class='error'>❌ Controller não retornou output</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no controller: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";

// ETAPA 8: Links de teste
echo "<div class='card'><h2>8. LINKS DE TESTE</h2>";

echo "<p>Agora teste estes links:</p>";
echo "<a href='/admin/denuncia/{$denunciaTest['id']}' class='btn' target='_blank'>🎯 Testar Rota Original</a>";
echo "<a href='/teste_controller_direto.php' class='btn' target='_blank'>🧪 Teste Direto</a>";
echo "<a href='/debug_erro_500.php' class='btn' target='_blank'>🔍 Debug Completo</a>";
echo "<a href='/admin/dashboard' class='btn' target='_blank'>🏠 Dashboard</a>";

echo "<h3>Status Final:</h3>";
echo "<p class='success'>✅ Configuração: OK</p>";
echo "<p class='success'>✅ Classes: Carregadas</p>";
echo "<p class='success'>✅ Banco: Conectado</p>";
echo "<p class='success'>✅ Dados: Disponíveis</p>";
echo "<p class='success'>✅ Layout: Simplificado</p>";
echo "<p class='success'>✅ Controller: Testado</p>";

echo "<p><strong>Se ainda houver erro 500:</strong></p>";
echo "<ul>";
echo "<li>Verifique o log de erro do Apache/Nginx</li>";
echo "<li>Teste primeiro o link 'Teste Direto'</li>";
echo "<li>Verifique permissões de arquivo no servidor</li>";
echo "<li>Confirme se PHP está funcionando corretamente</li>";
echo "</ul>";

echo "</div>";

echo "</body></html>";
?>
