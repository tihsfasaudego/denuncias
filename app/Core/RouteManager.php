<?php
/**
 * Gerenciador de Rotas Centralizado
 * 
 * Carrega e processa as rotas definidas em app/Config/Routes.php
 */

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Auth.php';

class RouteManager {
    private $router;
    private $routes;
    
    public function __construct() {
        $this->router = new Router();
        $this->loadRoutes();
    }
    
    /**
     * Carrega as rotas do arquivo de configuração
     */
    private function loadRoutes() {
        $routesFile = BASE_PATH . '/app/Config/Routes.php';
        
        if (!file_exists($routesFile)) {
            throw new Exception('Arquivo de rotas não encontrado: ' . $routesFile);
        }
        
        $this->routes = require $routesFile;
    }
    
    /**
     * Registra todas as rotas no router
     */
    public function registerRoutes() {
        // Registrar rotas públicas
        if (isset($this->routes['public'])) {
            foreach ($this->routes['public'] as $route) {
                $this->registerRoute($route);
            }
        }
        
        // Registrar rotas administrativas públicas (login, etc)
        if (isset($this->routes['admin']['public'])) {
            foreach ($this->routes['admin']['public'] as $route) {
                $this->registerRoute($route);
            }
        }
        
        // Registrar rotas administrativas protegidas
        if (isset($this->routes['admin']['protected'])) {
            foreach ($this->routes['admin']['protected'] as $route) {
                $this->registerProtectedRoute($route);
            }
        }
        
        // Registrar rotas da API
        if (isset($this->routes['api'])) {
            foreach ($this->routes['api'] as $route) {
                $this->registerApiRoute($route);
            }
        }
        
        // Configurar handler 404
        $this->router->notFound([$this, 'handleNotFound']);
    }
    
    /**
     * Registra uma rota comum
     */
    private function registerRoute($route) {
        list($method, $path, $action) = $route;
        
        $callback = $this->createCallback($action);
        
        if (strtoupper($method) === 'GET') {
            $this->router->get($path, $callback);
        } elseif (strtoupper($method) === 'POST') {
            $this->router->post($path, $callback);
        } elseif (strtoupper($method) === 'PUT') {
            $this->router->put($path, $callback);
        } elseif (strtoupper($method) === 'DELETE') {
            $this->router->delete($path, $callback);
        }
    }
    
    /**
     * Registra uma rota protegida (requer autenticação)
     */
    private function registerProtectedRoute($route) {
        list($method, $path, $action) = $route;
        
        $callback = function(...$params) use ($action) {
            // Verificar autenticação
            if (!Auth::check()) {
                // Se for uma requisição AJAX, retornar JSON
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
                    exit;
                }
                
                // Redirecionar para login
                header('Location: /admin/login');
                exit;
            }
            
            // Executar ação
            $callback = $this->createCallback($action);
            return call_user_func_array($callback, $params);
        };
        
        if (strtoupper($method) === 'GET') {
            $this->router->get($path, $callback);
        } elseif (strtoupper($method) === 'POST') {
            $this->router->post($path, $callback);
        }
    }
    
    /**
     * Registra uma rota da API
     */
    private function registerApiRoute($route) {
        list($method, $path, $action) = $route;
        
        $callback = function(...$params) use ($action, $path) {
            // Configurar headers para API
            header('Content-Type: application/json');
            
            // Verificar se é rota protegida (não é login/logout/health/docs)
            if (!in_array($path, ['/api/health', '/api/docs', '/api/auth/login', '/api/auth/refresh'])) {
                // Aqui seria verificação de API Auth (JWT), mas por simplicidade usaremos Auth básico
                if (!Auth::check()) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Token de autenticação necessário']);
                    exit;
                }
            }
            
            // Executar ação
            $actionCallback = $this->createCallback($action);
            return call_user_func_array($actionCallback, $params);
        };
        
        if (strtoupper($method) === 'GET') {
            $this->router->get($path, $callback);
        } elseif (strtoupper($method) === 'POST') {
            $this->router->post($path, $callback);
        } elseif (strtoupper($method) === 'PUT') {
            $this->router->put($path, $callback);
        } elseif (strtoupper($method) === 'DELETE') {
            $this->router->delete($path, $callback);
        }
    }
    
    /**
     * Cria um callback para uma ação
     */
    private function createCallback($action) {
        list($controller, $method) = explode('@', $action);
        
        return function(...$params) use ($controller, $method) {
            // Verificar se é rota admin para definir variáveis globais
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/admin') === 0) {
                global $isAdminPage, $bodyClass, $currentPage;
                $isAdminPage = true;
                $bodyClass = 'admin-body';
                
                // Definir página atual baseado na URI
                if (strpos($uri, '/dashboard') !== false) {
                    $currentPage = 'dashboard';
                } elseif (strpos($uri, '/usuarios') !== false) {
                    $currentPage = 'usuarios';
                } elseif (strpos($uri, '/configuracoes') !== false) {
                    $currentPage = 'configuracoes';
                } elseif (strpos($uri, '/relatorios') !== false) {
                    $currentPage = 'relatorios';
                } elseif (strpos($uri, '/denuncia') !== false) {
                    $currentPage = 'denuncias';
                }
            }
            
            // Instanciar controlador
            if (!class_exists($controller)) {
                throw new Exception("Controller não encontrado: {$controller}");
            }
            
            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new Exception("Método não encontrado: {$controller}@{$method}");
            }
            
            // Executar método
            return call_user_func_array([$controllerInstance, $method], $params);
        };
    }
    
    /**
     * Verifica se é uma requisição AJAX
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Handler para rotas não encontradas (404)
     */
    public function handleNotFound() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Se for requisição AJAX, retornar JSON
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint não encontrado']);
            exit;
        }
        
        // Se for rota admin, usar layout admin
        if (strpos($uri, '/admin') === 0) {
            global $isAdminPage, $bodyClass;
            $isAdminPage = true;
            $bodyClass = 'admin-body';
        }
        
        http_response_code(404);
        $pageTitle = 'Página não encontrada';
        $error = 'A página solicitada não foi encontrada.';
        
        ob_start();
        require BASE_PATH . '/app/Views/errors/404.php';
        $content = ob_get_clean();
        require BASE_PATH . '/app/Views/layouts/base.php';
    }
    
    /**
     * Executa o roteamento
     */
    public function run() {
        $this->router->run();
    }
    
    /**
     * Delega chamadas para o router
     */
    public function __call($method, $args) {
        if (method_exists($this->router, $method)) {
            return call_user_func_array([$this->router, $method], $args);
        }
        
        throw new Exception("Método não encontrado: {$method}");
    }
}
