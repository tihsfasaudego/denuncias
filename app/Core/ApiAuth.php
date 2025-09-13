<?php
/**
 * Sistema de autenticação para API
 * Gerencia tokens JWT e permissões de API
 */

require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/Logger.php';

class ApiAuth {
    private static $instance = null;
    private $logger;
    private $cache;
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->cache = Cache::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Autentica usuário e gera tokens
     */
    public function authenticate($username, $password) {
        try {
            // Tentar autenticar na tabela admin primeiro
            $adminModel = new Admin();
            $user = $adminModel->authenticate($username, $password);
            
            if ($user) {
                $permissions = $this->getAdminPermissions($user['id']);
                return $this->generateTokenPair($user, $permissions, 'admin');
            }
            
            // Tentar na tabela users
            $userModel = new User();
            $user = $userModel->authenticate($username, $password);
            
            if ($user) {
                $permissions = $this->getUserPermissions($user['id']);
                return $this->generateTokenPair($user, $permissions, 'user');
            }
            
            // Log de tentativa de login falhada
            $this->logger->security('api_login_failed', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            throw new Exception('Credenciais inválidas');
            
        } catch (Exception $e) {
            throw new Exception('Erro na autenticação: ' . $e->getMessage());
        }
    }
    
    /**
     * Gera par de tokens (access + refresh)
     */
    private function generateTokenPair($user, $permissions, $type) {
        $accessToken = JWT::generateAccessToken($user, $permissions);
        $refreshToken = JWT::generateRefreshToken($user);
        
        // Salvar refresh token no cache para invalidação
        $cacheKey = "refresh_token_{$user['id']}_{$type}";
        $this->cache->set($cacheKey, $refreshToken, 7 * 24 * 3600); // 7 dias
        
        // Log de login bem-sucedido
        $this->logger->audit('api_login_success', $type, $user['id'], [
            'username' => $user['usuario'] ?? $user['email'],
            'permissions_count' => count($permissions)
        ]);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => $user['id'],
                'name' => $user['nome'] ?? $user['usuario'],
                'email' => $user['email'] ?? $user['usuario'],
                'type' => $type
            ],
            'permissions' => $permissions
        ];
    }
    
    /**
     * Renova token usando refresh token
     */
    public function refreshToken($refreshToken) {
        try {
            $payload = JWT::decode($refreshToken);
            
            if ($payload['type'] !== 'refresh') {
                throw new Exception('Token de refresh inválido');
            }
            
            $userId = $payload['sub'];
            
            // Verificar se refresh token ainda está válido no cache
            $cacheKey = "refresh_token_{$userId}_admin";
            $cachedToken = $this->cache->get($cacheKey);
            
            if (!$cachedToken) {
                $cacheKey = "refresh_token_{$userId}_user";
                $cachedToken = $this->cache->get($cacheKey);
            }
            
            if ($cachedToken !== $refreshToken) {
                throw new Exception('Refresh token inválido ou expirado');
            }
            
            // Buscar usuário e gerar novo access token
            $adminModel = new Admin();
            $user = $adminModel->findById($userId);
            $type = 'admin';
            
            if (!$user) {
                $userModel = new User();
                $user = $userModel->findById($userId);
                $type = 'user';
            }
            
            if (!$user) {
                throw new Exception('Usuário não encontrado');
            }
            
            $permissions = $type === 'admin' ? 
                $this->getAdminPermissions($userId) : 
                $this->getUserPermissions($userId);
            
            $newAccessToken = JWT::generateAccessToken($user, $permissions);
            
            return [
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ];
            
        } catch (Exception $e) {
            throw new Exception('Erro ao renovar token: ' . $e->getMessage());
        }
    }
    
    /**
     * Valida token de acesso
     */
    public function validateToken($token) {
        try {
            return JWT::validateApiToken($token);
        } catch (Exception $e) {
            throw new Exception('Token inválido: ' . $e->getMessage());
        }
    }
    
    /**
     * Middleware de autenticação para API
     */
    public function middleware() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!$authHeader) {
            return $this->unauthorizedResponse('Token de autorização necessário');
        }
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Formato de token inválido');
        }
        
        $token = $matches[1];
        
        try {
            $payload = $this->validateToken($token);
            
            // Adicionar informações do usuário ao contexto global
            $GLOBALS['api_user'] = $payload;
            
            return true;
            
        } catch (Exception $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }
    }
    
    /**
     * Verifica se usuário tem permissão específica
     */
    public function hasPermission($permission) {
        $user = $GLOBALS['api_user'] ?? null;
        
        if (!$user) {
            return false;
        }
        
        return in_array($permission, $user['permissions'] ?? []);
    }
    
    /**
     * Obtém usuário atual da API
     */
    public function getCurrentUser() {
        return $GLOBALS['api_user'] ?? null;
    }
    
    /**
     * Logout - invalida refresh token
     */
    public function logout($refreshToken = null) {
        if ($refreshToken) {
            try {
                $payload = JWT::decode($refreshToken);
                $userId = $payload['sub'];
                
                // Remover refresh token do cache
                $this->cache->delete("refresh_token_{$userId}_admin");
                $this->cache->delete("refresh_token_{$userId}_user");
                
                $this->logger->audit('api_logout', null, $userId);
                
            } catch (Exception $e) {
                // Token inválido, mas logout ainda é bem-sucedido
            }
        }
        
        return ['message' => 'Logout realizado com sucesso'];
    }
    
    /**
     * Obtém permissões de admin
     */
    private function getAdminPermissions($adminId) {
        return $this->cache->remember("admin_permissions_{$adminId}", function() use ($adminId) {
            // Admins têm todas as permissões por padrão
            return [
                'denuncias.view.all',
                'denuncias.create',
                'denuncias.update',
                'denuncias.delete',
                'denuncias.assign',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'reports.view',
                'reports.export',
                'config.update',
                'api.access'
            ];
        }, 3600);
    }
    
    /**
     * Obtém permissões de usuário
     */
    private function getUserPermissions($userId) {
        return $this->cache->remember("user_permissions_{$userId}", function() use ($userId) {
            $auth = Auth::getInstance();
            $permissions = [];
            
            // Verificar permissões específicas
            if ($auth->can('denuncias.view.all', $userId)) {
                $permissions[] = 'denuncias.view.all';
            }
            
            if ($auth->can('denuncias.view.assigned', $userId)) {
                $permissions[] = 'denuncias.view.assigned';
            }
            
            if ($auth->can('denuncias.update', $userId)) {
                $permissions[] = 'denuncias.update';
            }
            
            // Usuários sempre podem acessar API
            $permissions[] = 'api.access';
            
            return $permissions;
        }, 3600);
    }
    
    /**
     * Resposta de erro de autorização
     */
    private function unauthorizedResponse($message) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
            'code' => 401
        ]);
        exit;
    }
    
    /**
     * Gera chave de API para integração externa
     */
    public function generateApiKey($userId, $name, $permissions = []) {
        $apiKey = 'hsfa_' . bin2hex(random_bytes(20));
        
        $keyData = [
            'user_id' => $userId,
            'name' => $name,
            'permissions' => $permissions,
            'created_at' => time(),
            'last_used' => null,
            'usage_count' => 0
        ];
        
        // Salvar no cache (em produção, usar banco de dados)
        $this->cache->set("api_key_{$apiKey}", $keyData, 365 * 24 * 3600); // 1 ano
        
        $this->logger->audit('api_key_created', 'api_key', $apiKey, [
            'user_id' => $userId,
            'name' => $name,
            'permissions' => $permissions
        ]);
        
        return $apiKey;
    }
    
    /**
     * Valida chave de API
     */
    public function validateApiKey($apiKey) {
        $keyData = $this->cache->get("api_key_{$apiKey}");
        
        if (!$keyData) {
            throw new Exception('Chave de API inválida');
        }
        
        // Atualizar estatísticas de uso
        $keyData['last_used'] = time();
        $keyData['usage_count']++;
        $this->cache->set("api_key_{$apiKey}", $keyData, 365 * 24 * 3600);
        
        return $keyData;
    }
}
