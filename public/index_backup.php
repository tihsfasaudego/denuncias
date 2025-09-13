<?php
/**
 * Arquivo de entrada principal da aplicação
 * 
 * Inicializa a aplicação, processa as rotas e exibe a página correspondente
 */

// Definir constantes
define('BASE_PATH', dirname(__DIR__));

// Configuração de erros baseada no ambiente
if (defined('APP_ENV') && APP_ENV === 'production') {
    // Produção: Log erros mas não exibir
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Desenvolvimento: Exibir erros se debug habilitado
    if (defined('APP_DEBUG') && APP_DEBUG) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    }
    ini_set('log_errors', 1);
}
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Verifica e cria diretório de logs se não existir
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0777, true);
}

// Carregar configuração uma única vez
require_once BASE_PATH . '/config/config.php';

// Verificar se a URL solicitada é a raiz ('/')
if ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '') {
    // Exibir a página inicial diretamente
    require_once BASE_PATH . '/app/Controllers/HomeController.php';
    
    try {
        $controller = new HomeController();
        $controller->index();
        exit;
    } catch (Exception $e) {
        echo "<h1>Erro ao carregar a página inicial</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        exit;
    }
}

// Verificar se a URL solicitada é para o admin (começa com /admin)
if (strpos($_SERVER['REQUEST_URI'], '/admin') === 0) {
    // Define a variável para o layout saber que é uma página do admin
    $isAdminPage = true;
    $bodyClass = 'admin-body';
    
    // Carregar os arquivos necessários para o admin
    require_once BASE_PATH . '/app/Core/Database.php';
    require_once BASE_PATH . '/app/Core/Auth.php';
    require_once BASE_PATH . '/app/Controllers/AdminController.php';
    
    // Inicializa o AdminController
    $adminController = new AdminController();
    
    // Verificar qual rota específica de admin foi solicitada
    $adminPath = explode('?', str_replace('/admin', '', $_SERVER['REQUEST_URI']))[0];
    
    // Rotas que não precisam de autenticação
    if ($adminPath === '/login') {
        $adminController->login();
        exit;
    }
    
    if ($adminPath === '/authenticate') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->authenticate();
        } else {
            // Redirecionar para o login se tentar acessar diretamente
            header('Location: /admin/login');
        }
        exit;
    }
    
    // Todas as outras rotas de admin precisam de autenticação
    if (!Auth::check()) {
        header('Location: /admin/login');
        exit;
    }
    
    // Rotas que precisam de autenticação
    if ($adminPath === '' || $adminPath === '/') {
        // Redirecionar admin/ para admin/dashboard
        header('Location: /admin/dashboard');
        exit;
    }
    
    if ($adminPath === '/logout') {
        $adminController->logout();
        exit;
    }
    
    if ($adminPath === '/dashboard') {
        $currentPage = 'dashboard';
        $adminController->dashboard();
        exit;
    }
    
    if ($adminPath === '/denuncia') {
        $currentPage = 'denuncias';
        $adminController->viewDenuncia();
        exit;
    }
    
    if ($adminPath === '/denuncia/status') {
        $adminController->updateStatus();
        exit;
    }
    
    if ($adminPath === '/configuracoes') {
        $currentPage = 'configuracoes';
        $adminController->configuracoes();
        exit;
    }
    
    if ($adminPath === '/relatorios') {
        $currentPage = 'relatorios';
        $adminController->relatorios();
        exit;
    }
    
    if ($adminPath === '/relatorios/gerar') {
        $currentPage = 'relatorios';
        $adminController->gerarRelatorio();
        exit;
    }
    
    if ($adminPath === '/relatorios/estatistico') {
        $currentPage = 'relatorios';
        $adminController->relatorioEstatistico();
        exit;
    }
    
    if ($adminPath === '/relatorios/exportar-pdf') {
        $currentPage = 'relatorios';
        $adminController->exportarRelatorioPDF();
        exit;
    }
    
    // Rota de diagnóstico temporária
    if ($adminPath === '/debug/historico-status') {
        $adminController->debugHistoricoStatus();
        exit;
    }
    
    // Rotas para gerenciamento de usuários 
    if ($adminPath === '/usuarios') {
        // Verificar se está autenticado
        if (!Auth::check()) {
            header('Location: /admin/login');
            exit;
        }
        
        require_once BASE_PATH . '/app/Controllers/UserController.php';
        $currentPage = 'usuarios';
        $userController = new UserController();
        $userController->index();
        exit;
    }
    
    if ($adminPath === '/usuarios/novo') {
        // Verificar se está autenticado
        if (!Auth::check()) {
            header('Location: /admin/login');
            exit;
        }
        
        require_once BASE_PATH . '/app/Controllers/UserController.php';
        $currentPage = 'usuarios';
        $userController = new UserController();
        $userController->create();
        exit;
    }
    
    if ($adminPath === '/usuarios/editar') {
        // Verificar se está autenticado
        if (!Auth::check()) {
            header('Location: /admin/login');
            exit;
        }
        
        require_once BASE_PATH . '/app/Controllers/UserController.php';
        $currentPage = 'usuarios';
        $userController = new UserController();
        $id = $_GET['id'] ?? 0;
        $userController->edit($id);
        exit;
    }
    
    // Se chegou aqui, a rota não foi encontrada
    http_response_code(404);
    $pageTitle = 'Página não encontrada';
    $error = 'A página solicitada não foi encontrada no painel administrativo.';
    
    ob_start();
    require __DIR__ . '/../app/Views/errors/error.php';
    $content = ob_get_clean();
    require __DIR__ . '/../app/Views/layouts/base.php';
    exit;
}

// Carregar arquivos principais para outras rotas
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Core/Router.php';
require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/AuthMiddleware.php';

// Auto-loader para controllers e models
spl_autoload_register(function ($className) {
    // Verificar se é um controller
    if (strpos($className, 'Controller') !== false) {
        $file = BASE_PATH . '/app/Controllers/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Verificar se é um model
    $file = BASE_PATH . '/app/Models/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Verificar se é uma classe Core
    $file = BASE_PATH . '/app/Core/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Classe Config
    $file = BASE_PATH . '/app/Config/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

// Criar router e configurar as rotas
$router = new Router();

// Rotas principais
$router->get('/', function() {
    $controller = new HomeController();
    return $controller->index();
});

$router->get('/denuncia/criar', function() {
    $controller = new DenunciaController();
    return $controller->index();
});

$router->post('/denuncia/criar', function() {
    $controller = new DenunciaController();
    return $controller->store();
});

$router->get('/denuncia/consultar', function() {
    $controller = new DenunciaController();
    return $controller->status();
});

$router->post('/denuncia/consultar', function() {
    $controller = new DenunciaController();
    return $controller->checkStatus();
});

$router->get('/denuncia/detalhes', function() {
    $controller = new DenunciaController();
    return $controller->details();
});

// Rotas administrativas
$router->get('/admin/login', function() {
    $controller = new AdminController();
    return $controller->login();
});

$router->post('/admin/login', function() {
    $controller = new AdminController();
    return $controller->authenticate();
});

$router->get('/admin/dashboard', function() {
    $controller = new AdminController();
    return $controller->dashboard();
});

$router->get('/admin/logout', function() {
    $controller = new AdminController();
    return $controller->logout();
});

$router->get('/admin/configuracoes', function() {
    $controller = new AdminController();
    return $controller->configuracoes();
});

$router->post('/admin/configuracoes/logo', function() {
    $controller = new AdminController();
    return $controller->uploadLogo();
});

// Rotas para denúncias administrativas
$router->get('/admin/denuncia', function() {
    $controller = new AdminController();
    return $controller->viewDenuncia();
});

$router->post('/admin/denuncia/status', function() {
    $controller = new AdminController();
    return $controller->updateStatus();
});

$router->post('/admin/denuncia/excluir', function() {
    $controller = new AdminController();
    return $controller->excluirDenuncia();
});

// Rotas para gerenciamento de usuários
$router->get('/admin/usuarios', function() {
    $controller = new UserController();
    return $controller->index();
});

$router->get('/admin/usuarios/novo', function() {
    $controller = new UserController();
    return $controller->create();
});

$router->post('/admin/usuarios/salvar', function() {
    $controller = new UserController();
    return $controller->store();
});

$router->get('/admin/usuarios/editar/{id}', function($id) {
    $controller = new UserController();
    return $controller->edit($id);
});

$router->post('/admin/usuarios/atualizar/{id}', function($id) {
    $controller = new UserController();
    return $controller->update($id);
});

$router->post('/admin/usuarios/excluir/{id}', function($id) {
    $controller = new UserController();
    return $controller->delete($id);
});

$router->post('/admin/usuarios/status/{id}', function($id) {
    $controller = new UserController();
    return $controller->updateStatus($id);
});

// Rotas para perfil
$router->get('/admin/perfil', function() {
    $controller = new UserController();
    return $controller->profile();
});

$router->post('/admin/perfil/atualizar', function() {
    $controller = new UserController();
    return $controller->updateProfile();
});

// Rotas para relatórios
$router->get('/admin/relatorios', function() {
    $controller = new AdminController();
    return $controller->relatorios();
});

$router->get('/admin/relatorios/gerar', function() {
    $controller = new AdminController();
    return $controller->gerarRelatorio();
});

$router->get('/admin/relatorios/estatistico', function() {
    $controller = new AdminController();
    return $controller->relatorioEstatistico();
});

$router->get('/admin/relatorios/exportar-pdf', function() {
    $controller = new AdminController();
    return $controller->exportarRelatorioPDF();
});

// Tratar erro 404
$router->notFound(function() {
    http_response_code(404);
    $pageTitle = 'Página não encontrada';
    ob_start();
    require BASE_PATH . '/app/Views/errors/404.php';
    $content = ob_get_clean();
    require BASE_PATH . '/app/Views/layouts/base.php';
});

// Executar o roteador
$router->run();
?>
