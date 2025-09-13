<?php
require_once __DIR__ . '/Auth.php';

/**
 * Middleware para verificação de permissões de usuários
 */
class AuthMiddleware {
    private $auth;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Verifica se o usuário está autenticado
     * 
     * @return bool True se autenticado, false caso contrário
     */
    public function isAuthenticated() {
        return Auth::check();
    }
    
    /**
     * Verifica se o usuário tem permissão para acessar um recurso
     * 
     * @param string|array $permissions Uma ou mais permissões necessárias
     * @param bool $requireAll True se todas as permissões forem necessárias, false se apenas uma for suficiente
     * @return bool True se usuário tem permissão, false caso contrário
     */
    public function hasPermission($permissions, $requireAll = false) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Se é admin na tabela admin com nível admin, dar acesso total
        if (isset($_SESSION['admin']) && isset($_SESSION['admin']['nivel_acesso'])) {
            if ($_SESSION['admin']['nivel_acesso'] === 'admin') {
                return true; // Admin tem acesso a tudo
            }
        }
        
        // Converter para array se for string
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }
        
        if (empty($permissions)) {
            return true; // Se não há permissões necessárias, permite o acesso
        }
        
        if ($requireAll) {
            // Precisa ter TODAS as permissões
            foreach ($permissions as $permission) {
                if (!$this->auth->can($permission)) {
                    return false;
                }
            }
            return true;
        } else {
            // Precisa ter pelo menos UMA das permissões
            foreach ($permissions as $permission) {
                if ($this->auth->can($permission)) {
                    return true;
                }
            }
            return false;
        }
    }
    
    /**
     * Verifica se o usuário tem um determinado perfil
     * 
     * @param string|array $roles Um ou mais perfis necessários
     * @param bool $requireAll True se todos os perfis forem necessários, false se apenas um for suficiente
     * @return bool True se usuário tem o perfil, false caso contrário
     */
    public function hasRole($roles, $requireAll = false) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Converter para array se for string
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (empty($roles)) {
            return true; // Se não há perfis necessários, permite o acesso
        }
        
        if ($requireAll) {
            // Precisa ter TODOS os perfis
            foreach ($roles as $role) {
                if (!$this->auth->hasRole($role)) {
                    return false;
                }
            }
            return true;
        } else {
            // Precisa ter pelo menos UM dos perfis
            foreach ($roles as $role) {
                if ($this->auth->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }
    }
    
    /**
     * Redireciona para a página de login se o usuário não estiver autenticado
     * 
     * @return void
     */
    public function redirectIfNotAuthenticated() {
        if (!$this->isAuthenticated()) {
            header('Location: /admin/login');
            exit;
        }
    }
    
    /**
     * Redireciona para a página de erro se o usuário não tiver permissão
     * 
     * @param string|array $permissions Uma ou mais permissões necessárias
     * @param bool $requireAll True se todas as permissões forem necessárias, false se apenas uma for suficiente
     * @return void
     */
    public function redirectIfNoPermission($permissions, $requireAll = false) {
        $this->redirectIfNotAuthenticated();
        
        if (!$this->hasPermission($permissions, $requireAll)) {
            $this->renderAccessDenied();
        }
    }
    
    /**
     * Redireciona para a página de erro se o usuário não tiver o perfil
     * 
     * @param string|array $roles Um ou mais perfis necessários
     * @param bool $requireAll True se todos os perfis forem necessários, false se apenas um for suficiente
     * @return void
     */
    public function redirectIfNoRole($roles, $requireAll = false) {
        $this->redirectIfNotAuthenticated();
        
        if (!$this->hasRole($roles, $requireAll)) {
            $this->renderAccessDenied();
        }
    }
    
    /**
     * Renderiza a página de acesso negado
     * 
     * @return void
     */
    public function renderAccessDenied() {
        http_response_code(403);
        
        // Registrar a tentativa não autorizada
        $userId = Auth::id();
        $url = $_SERVER['REQUEST_URI'];
        $ip = $_SERVER['REMOTE_ADDR'];
        error_log("Acesso negado - Usuário ID: $userId, URL: $url, IP: $ip");
        
        // Registrar no log de atividades
        $this->auth->registerActivity(
            "acesso_negado", 
            "security", 
            null, 
            ["url" => $url]
        );
        
        // Determinar se é página administrativa baseado na URL
        $isAdminPage = strpos($url, '/admin') === 0;
        
        // Renderizar página de erro
        $pageTitle = 'Acesso Negado';
        ob_start();
        require __DIR__ . '/../Views/errors/access-denied.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
        exit;
    }
} 