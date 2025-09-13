<?php
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/Database.php';

class Auth {
    private static $instance = null;
    private $userModel;

    private function __construct() {
        $this->userModel = new User();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Verifica se o usuário está autenticado
     *
     * @return bool True se autenticado, false caso contrário
     */
    public static function check() {
        // Verificar se existe a sessão 'user' ou 'admin' (para compatibilidade)
        if (!isset($_SESSION['user']) && !isset($_SESSION['admin'])) {
            return false;
        }
        
        // Determinar qual variável de sessão está sendo usada
        $sessionVar = isset($_SESSION['user']) ? 'user' : 'admin';
        
        // Verificar se a chave last_activity existe
        if (!isset($_SESSION[$sessionVar . '_last_activity'])) {
            // Se não existir, definir para o timestamp atual
            $_SESSION[$sessionVar . '_last_activity'] = time();
            return true;
        }
        
        // Verificar se a sessão expirou (30 minutos)
        if (time() - $_SESSION[$sessionVar . '_last_activity'] > 1800) {
            self::logout();
            return false;
        }
        
        // Atualizar timestamp de atividade
        $_SESSION[$sessionVar . '_last_activity'] = time();
        return true;
    }
    
    /**
     * Retorna os dados do usuário autenticado
     *
     * @return array|null Dados do usuário ou null se não autenticado
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        // Retornar dados do usuário com base em qual sessão está ativa
        return isset($_SESSION['user']) ? $_SESSION['user'] : $_SESSION['admin'];
    }
    
    /**
     * Retorna o ID do usuário autenticado
     *
     * @return int|null ID do usuário ou null se não autenticado
     */
    public static function id() {
        if (!self::check()) {
            return null;
        }
        
        // Retornar ID do usuário com base em qual sessão está ativa
        return isset($_SESSION['user']) ? $_SESSION['user']['id'] : $_SESSION['admin']['id'];
    }
    
    /**
     * Realiza o login do usuário
     *
     * @param string $usuario Nome de usuário
     * @param string $senha Senha do usuário
     * @return bool True se login bem-sucedido, false caso contrário
     */
    public function login($usuario, $senha) {
        error_log("Tentativa de login para usuário: " . $usuario);
        $user = $this->userModel->authenticate($usuario, $senha);
        
        if ($user) {
            // Armazenar dados do usuário na sessão
            $_SESSION['user'] = $user;
            $_SESSION['user_last_activity'] = time();
            
            // Para compatibilidade, também armazenar como admin
            $_SESSION['admin'] = $user;
            $_SESSION['admin_last_activity'] = time();
            
            error_log("Login bem-sucedido para usuário: " . $usuario);
            return true;
        }
        
        error_log("Falha no login para usuário: " . $usuario);
        return false;
    }
    
    /**
     * Encerra a sessão do usuário
     */
    public static function logout() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Verifica se o usuário possui determinada permissão
     *
     * @param string $permission Slug da permissão
     * @return bool True se possui a permissão, false caso contrário
     */
    public function can($permission) {
        if (!self::check()) {
            return false;
        }
        
        // Se é admin na tabela admin, dar acesso total
        if (isset($_SESSION['admin']) && isset($_SESSION['admin']['nivel_acesso'])) {
            if ($_SESSION['admin']['nivel_acesso'] === 'admin') {
                return true; // Admin tem acesso a tudo
            }
        }
        
        $userId = self::id();
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Consulta direta às tabelas de permissão
            $stmt = $db->prepare("
                SELECT 1
                FROM permissions p
                JOIN role_permission rp ON p.id = rp.permission_id
                JOIN user_role ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ? AND p.slug = ?
                LIMIT 1
            ");
            
            $stmt->bind_param("is", $userId, $permission);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result && $result->num_rows > 0;
            
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário tem determinado perfil
     *
     * @param string $role Nome do perfil a ser verificado
     * @return bool True se o usuário tem o perfil, false caso contrário
     */
    public function hasRole($role) {
        if (!self::check()) {
            return false;
        }
        
        // Se é admin na tabela admin, sempre tem papel de admin
        if (isset($_SESSION['admin']) && isset($_SESSION['admin']['nivel_acesso'])) {
            if ($_SESSION['admin']['nivel_acesso'] === 'admin' && strtolower($role) === 'admin') {
                return true;
            }
        }
        
        $userId = self::id();
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Consulta direta às tabelas de papéis
            $stmt = $db->prepare("
                SELECT 1
                FROM roles r
                JOIN user_role ur ON r.id = ur.role_id
                WHERE ur.user_id = ? AND LOWER(r.nome) = LOWER(?)
                LIMIT 1
            ");
            
            $stmt->bind_param("is", $userId, $role);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result && $result->num_rows > 0;
            
        } catch (Exception $e) {
            error_log("Erro ao verificar papel: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra uma atividade do usuário
     *
     * @param string $action Descrição da ação
     * @param string $entityType Tipo de entidade
     * @param int $entityId ID da entidade
     * @param array $details Detalhes adicionais
     * @return bool True se registro bem-sucedido, false caso contrário
     */
    public function registerActivity($action, $entityType = null, $entityId = null, $details = null) {
        if (!self::check()) {
            return false;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO user_activity_log 
                (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $userId = self::id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $detailsJson = $details ? json_encode($details) : null;
            
            $stmt->bind_param("ississs", $userId, $action, $entityType, $entityId, $detailsJson, $ip, $userAgent);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao registrar atividade: " . $e->getMessage());
            return false;
        }
    }
}
?> 