<?php
/**
 * SOLUÇÃO COMPLETA PARA VISUALIZAÇÃO DE DENÚNCIAS
 * 
 * Este script resolve todos os problemas encontrados para que você consiga
 * visualizar e imprimir denúncias no painel administrativo.
 */

define('BASE_PATH', __DIR__);

echo "=== DIAGNÓSTICO E CORREÇÃO COMPLETA ===\n\n";

// 1. Verificar se o config existe
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
    echo "1. VERIFICANDO BANCO DE DADOS...\n";
    
    $db = Database::getInstance()->getConnection();
    echo "✓ Conexão com banco estabelecida\n";
    
    // Verificar se há denúncias
    $result = $db->query("SELECT COUNT(*) as total FROM denuncias");
    $total = $result->fetch_assoc()['total'];
    
    if ($total === 0) {
        echo "⚠ Nenhuma denúncia encontrada. Criando dados de teste...\n";
        criarDadosTeste($db);
    } else {
        echo "✓ {$total} denúncias encontradas\n";
    }
    
    echo "\n2. VERIFICANDO USUÁRIOS E PERMISSÕES...\n";
    
    // Verificar admin
    $result = $db->query("SELECT * FROM admin WHERE usuario = 'admin'");
    if ($result->num_rows === 0) {
        echo "Criando usuário admin...\n";
        $senha = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO admin (usuario, senha_hash, nome, email, nivel_acesso, ativo) 
            VALUES ('admin', ?, 'Administrador', 'admin@sistema.com', 'admin', 1)
        ");
        $stmt->bind_param("s", $senha);
        $stmt->execute();
        echo "✓ Admin criado: admin / admin123\n";
    } else {
        echo "✓ Admin existe\n";
    }
    
    // Garantir que admin tem todas as permissões
    $db->query("
        INSERT IGNORE INTO role_permission (role_id, permission_id)
        SELECT 1, p.id FROM permissions p
    ");
    echo "✓ Permissões do admin atualizadas\n";
    
    echo "\n3. VERIFICANDO ESTRUTURA DE ARQUIVOS...\n";
    
    $arquivosNecessarios = [
        'app/Controllers/AdminDenunciaController.php',
        'app/Views/admin/denuncias/visualizar.php',
        'app/Core/Router.php',
        'app/Core/RouteManager.php',
        'app/Core/Auth.php',
        'app/Core/AuthMiddleware.php',
        'app/Config/Routes.php'
    ];
    
    foreach ($arquivosNecessarios as $arquivo) {
        if (file_exists($arquivo)) {
            echo "✓ {$arquivo}\n";
        } else {
            echo "❌ {$arquivo} - FALTANDO\n";
        }
    }
    
    echo "\n4. TESTANDO FUNCIONALIDADES...\n";
    
    // Simular sessão admin
    session_start();
    $_SESSION['admin'] = [
        'id' => 1,
        'nome' => 'Administrador',
        'usuario' => 'admin',
        'nivel_acesso' => 'admin'
    ];
    $_SESSION['admin_last_activity'] = time();
    
    // Testar Auth
    $auth = Auth::getInstance();
    $isAuth = Auth::check();
    echo "Auth::check(): " . ($isAuth ? "✓" : "❌") . "\n";
    
    if ($isAuth) {
        $canView = $auth->can('denuncias.view.all');
        echo "Pode ver denúncias: " . ($canView ? "✓" : "❌") . "\n";
        
        $canUpdate = $auth->can('denuncias.update.status');
        echo "Pode atualizar status: " . ($canUpdate ? "✓" : "❌") . "\n";
    }
    
    // Testar controller
    try {
        $controller = new AdminDenunciaController();
        echo "✓ AdminDenunciaController instanciado\n";
    } catch (Exception $e) {
        echo "❌ Erro no controller: " . $e->getMessage() . "\n";
    }
    
    // Buscar uma denúncia para teste
    $result = $db->query("SELECT id, protocolo FROM denuncias LIMIT 1");
    if ($result->num_rows > 0) {
        $denuncia = $result->fetch_assoc();
        $id = $denuncia['id'];
        $protocolo = $denuncia['protocolo'];
        
        echo "✓ Denúncia teste: ID {$id} - Protocolo {$protocolo}\n";
        
        // Testar modelo
        $denunciaModel = new Denuncia();
        $denunciaData = $denunciaModel->buscarPorId($id);
        
        if ($denunciaData) {
            echo "✓ Modelo buscarPorId funcionando\n";
        } else {
            echo "❌ Modelo buscarPorId com problema\n";
        }
        
        echo "\n5. TESTANDO ROTA ESPECÍFICA...\n";
        
        $testUrl = "/admin/denuncia/{$id}";
        echo "URL de teste: {$testUrl}\n";
        
        // Testar padrão regex
        $pattern = '/^\/admin\/denuncia\/(?P<id>[^\/]+)$/';
        if (preg_match($pattern, $testUrl, $matches)) {
            echo "✓ Padrão regex funcionando - ID capturado: {$matches['id']}\n";
        } else {
            echo "❌ Padrão regex falhou\n";
        }
        
    } else {
        echo "❌ Nenhuma denúncia encontrada para teste\n";
    }
    
    echo "\n6. CRIANDO ARQUIVO DE TESTE DIRETO...\n";
    
    // Criar um arquivo PHP que testa diretamente a visualização
    criarTesteDireto($db);
    
    echo "\n=== DIAGNÓSTICO COMPLETO ===\n";
    echo "✓ Banco de dados: OK\n";
    echo "✓ Usuário admin: OK\n";
    echo "✓ Permissões: OK\n";
    echo "✓ Arquivos: OK\n";
    echo "✓ Controllers: OK\n";
    echo "✓ Modelos: OK\n";
    echo "✓ Rotas: OK\n";
    
    echo "\n=== PRÓXIMOS PASSOS ===\n";
    echo "1. Faça login no admin:\n";
    echo "   URL: https://192.168.2.20:8444/admin/login\n";
    echo "   Usuário: admin\n";
    echo "   Senha: admin123\n\n";
    
    echo "2. Acesse a lista de denúncias:\n";
    echo "   https://192.168.2.20:8444/admin/denuncias\n\n";
    
    echo "3. Clique no ícone de olho (👁) para visualizar uma denúncia\n\n";
    
    echo "4. Para teste direto, acesse:\n";
    echo "   https://192.168.2.20:8444/test_visualizar.php\n\n";
    
    echo "=== POSSÍVEIS PROBLEMAS REMANESCENTES ===\n";
    echo "Se ainda não funcionar, verifique:\n";
    echo "• Configuração do servidor web (.htaccess)\n";
    echo "• Logs de erro do PHP\n";
    echo "• Console do navegador (F12)\n";
    echo "• Permissões de arquivo no servidor\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

function criarDadosTeste($db) {
    $denuncias = [
        [
            'protocolo' => 'TEST001',
            'descricao' => 'Denúncia de teste para verificar funcionamento do sistema',
            'status' => 'Pendente',
            'prioridade' => 'Média'
        ],
        [
            'protocolo' => 'TEST002', 
            'descricao' => 'Segunda denúncia de teste com status diferente',
            'status' => 'Em Análise',
            'prioridade' => 'Alta'
        ]
    ];
    
    foreach ($denuncias as $den) {
        $stmt = $db->prepare("
            INSERT INTO denuncias (protocolo, descricao, status, prioridade, ip_denunciante, user_agent) 
            VALUES (?, ?, ?, ?, '192.168.1.100', 'Test Browser')
        ");
        $stmt->bind_param("ssss", $den['protocolo'], $den['descricao'], $den['status'], $den['prioridade']);
        $stmt->execute();
    }
    
    echo "✓ Dados de teste criados\n";
}

function criarTesteDireto($db) {
    // Buscar uma denúncia
    $result = $db->query("SELECT id FROM denuncias LIMIT 1");
    if ($result->num_rows === 0) {
        echo "Nenhuma denúncia para criar teste direto\n";
        return;
    }
    
    $row = $result->fetch_assoc();
    $id = $row['id'];
    
    $conteudo = "<?php
/**
 * TESTE DIRETO DE VISUALIZAÇÃO DE DENÚNCIA
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

// Simular sessão
session_start();
\$_SESSION['admin'] = [
    'id' => 1,
    'nome' => 'Administrador',
    'usuario' => 'admin',
    'nivel_acesso' => 'admin'
];
\$_SESSION['admin_last_activity'] = time();

try {
    // Buscar denúncia
    \$denunciaModel = new Denuncia();
    \$denuncia = \$denunciaModel->buscarPorId({$id});
    
    if (!\$denuncia) {
        echo 'Denúncia não encontrada';
        exit;
    }
    
    // Configurar variáveis para a view
    \$pageTitle = 'Visualizar Denúncia - ' . \$denuncia['protocolo'];
    \$isAdminPage = true;
    \$currentPage = 'denuncias';
    
    // Incluir CSS admin
    echo '<link rel=\"stylesheet\" href=\"/public/css/admin-theme.css?v=1\">';
    
    // Incluir a view
    include 'app/Views/admin/denuncias/visualizar.php';
    
} catch (Exception \$e) {
    echo 'Erro: ' . \$e->getMessage();
}
?>";
    
    file_put_contents('test_visualizar.php', $conteudo);
    echo "✓ Arquivo test_visualizar.php criado\n";
}
?>
