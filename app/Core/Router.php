<?php
/**
 * Classe Router - Gerencia o roteamento de URLs
 */
class Router {
    private $routes = [];
    private $notFoundCallback;
    
    /**
     * Adiciona uma rota GET
     * 
     * @param string $path Caminho da URL (ex: /admin/users)
     * @param callable|array $callback Função ou array [Controller, método] a ser executado
     */
    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }
    
    /**
     * Adiciona uma rota POST
     * 
     * @param string $path Caminho da URL (ex: /admin/users)
     * @param callable|array $callback Função ou array [Controller, método] a ser executado
     */
    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }
    
    /**
     * Adiciona uma rota PUT
     * 
     * @param string $path Caminho da URL (ex: /admin/users/1)
     * @param callable|array $callback Função ou array [Controller, método] a ser executado
     */
    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }
    
    /**
     * Adiciona uma rota DELETE
     * 
     * @param string $path Caminho da URL (ex: /admin/users/1)
     * @param callable|array $callback Função ou array [Controller, método] a ser executado
     */
    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
    }
    
    /**
     * Adiciona um handler para rotas não encontradas (404)
     * 
     * @param callable $callback Função a ser executada quando nenhuma rota for encontrada
     */
    public function notFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    /**
     * Adiciona uma rota ao array de rotas
     * 
     * @param string $method Método HTTP (GET, POST, etc)
     * @param string $path Caminho da URL
     * @param callable|array $callback Função ou array [Controller, método] a ser executado
     */
    private function addRoute($method, $path, $callback) {
        // Converter path para formato de expressão regular
        $pattern = str_replace('/', '\/', $path);
        $pattern = '/^' . preg_replace('/{([a-zA-Z0-9_]+)}/', '(?P<$1>[^\/]+)', $pattern) . '$/';
        
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback
        ];
    }
    
    /**
     * Executa o roteamento com base na URL atual
     */
    public function run() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Remover índices numéricos
                foreach ($matches as $key => $value) {
                    if (is_int($key)) {
                        unset($matches[$key]);
                    }
                }
                
                // Executar callback
                if (is_callable($route['callback'])) {
                    call_user_func_array($route['callback'], $matches);
                    return;
                }
                
                if (is_array($route['callback'])) {
                    $controller = $route['callback'][0];
                    $method = $route['callback'][1];
                    
                    if (is_string($controller)) {
                        // Instanciar controlador se for string
                        $controller = new $controller();
                    }
                    
                    if (method_exists($controller, $method)) {
                        call_user_func_array([$controller, $method], $matches);
                        return;
                    }
                }
            }
        }
        
        // Rota não encontrada
        if (is_callable($this->notFoundCallback)) {
            call_user_func($this->notFoundCallback);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo '404 - Página não encontrada';
        }
    }
}
?>
