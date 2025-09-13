<?php
/**
 * Controlador principal da API REST
 * Gerencia endpoints e respostas JSON
 */

require_once __DIR__ . '/../Core/ApiAuth.php';
require_once __DIR__ . '/../Core/Logger.php';
require_once __DIR__ . '/../Models/Denuncia.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Admin.php';

class ApiController {
    private $apiAuth;
    private $logger;
    private $denunciaModel;
    private $userModel;
    private $adminModel;
    
    public function __construct() {
        $this->apiAuth = ApiAuth::getInstance();
        $this->logger = Logger::getInstance();
        $this->denunciaModel = new Denuncia();
        $this->userModel = new User();
        $this->adminModel = new Admin();
        
        // Headers para API
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Responder a preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Roteador principal da API
     */
    public function route() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/api', '', $path);
        $segments = array_filter(explode('/', $path));
        
        try {
            // Log da requisição
            $this->logger->info('API Request', [
                'method' => $method,
                'path' => $path,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            // Rotas públicas (sem autenticação)
            if ($segments[0] === 'auth') {
                return $this->handleAuth($method, array_slice($segments, 1));
            }
            
            if ($segments[0] === 'health') {
                return $this->healthCheck();
            }
            
            if ($segments[0] === 'docs') {
                return $this->apiDocumentation();
            }
            
            // Verificar autenticação para outras rotas
            $this->apiAuth->middleware();
            
            // Rotas autenticadas
            switch ($segments[0]) {
                case 'denuncias':
                    return $this->handleDenuncias($method, array_slice($segments, 1));
                    
                case 'users':
                    return $this->handleUsers($method, array_slice($segments, 1));
                    
                case 'stats':
                    return $this->handleStats($method, array_slice($segments, 1));
                    
                case 'reports':
                    return $this->handleReports($method, array_slice($segments, 1));
                    
                default:
                    throw new Exception('Endpoint não encontrado', 404);
            }
            
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
    
    /**
     * Gerencia autenticação
     */
    private function handleAuth($method, $segments) {
        switch ($method) {
            case 'POST':
                if ($segments[0] === 'login') {
                    return $this->login();
                }
                if ($segments[0] === 'refresh') {
                    return $this->refreshToken();
                }
                if ($segments[0] === 'logout') {
                    return $this->logout();
                }
                break;
        }
        
        throw new Exception('Endpoint de autenticação não encontrado', 404);
    }
    
    /**
     * Login via API
     */
    private function login() {
        $input = $this->getJsonInput();
        
        if (!isset($input['username']) || !isset($input['password'])) {
            throw new Exception('Username e password são obrigatórios', 400);
        }
        
        $result = $this->apiAuth->authenticate($input['username'], $input['password']);
        
        return $this->successResponse($result);
    }
    
    /**
     * Renovar token
     */
    private function refreshToken() {
        $input = $this->getJsonInput();
        
        if (!isset($input['refresh_token'])) {
            throw new Exception('Refresh token é obrigatório', 400);
        }
        
        $result = $this->apiAuth->refreshToken($input['refresh_token']);
        
        return $this->successResponse($result);
    }
    
    /**
     * Logout
     */
    private function logout() {
        $input = $this->getJsonInput();
        $refreshToken = $input['refresh_token'] ?? null;
        
        $result = $this->apiAuth->logout($refreshToken);
        
        return $this->successResponse($result);
    }
    
    /**
     * Gerencia endpoints de denúncias
     */
    private function handleDenuncias($method, $segments) {
        switch ($method) {
            case 'GET':
                if (empty($segments)) {
                    return $this->listDenuncias();
                }
                if (count($segments) === 1) {
                    return $this->getDenuncia($segments[0]);
                }
                break;
                
            case 'POST':
                return $this->createDenuncia();
                
            case 'PUT':
                if (count($segments) === 1) {
                    return $this->updateDenuncia($segments[0]);
                }
                break;
                
            case 'DELETE':
                if (count($segments) === 1) {
                    return $this->deleteDenuncia($segments[0]);
                }
                break;
        }
        
        throw new Exception('Método não permitido para denúncias', 405);
    }
    
    /**
     * Lista denúncias com paginação e filtros
     */
    private function listDenuncias() {
        if (!$this->apiAuth->hasPermission('denuncias.view.all') && 
            !$this->apiAuth->hasPermission('denuncias.view.assigned')) {
            throw new Exception('Sem permissão para visualizar denúncias', 403);
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = min((int)($_GET['limit'] ?? 20), 100); // Máximo 100
        $filters = [
            'status' => $_GET['status'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $result = $this->denunciaModel->listarComPaginacao($page, $limit, $filters);
        
        // Se usuário só pode ver atribuídas, filtrar
        if (!$this->apiAuth->hasPermission('denuncias.view.all')) {
            $user = $this->apiAuth->getCurrentUser();
            $result['data'] = array_filter($result['data'], function($denuncia) use ($user) {
                return $denuncia['admin_responsavel_id'] == $user['sub'];
            });
        }
        
        return $this->successResponse([
            'denuncias' => $result['data'],
            'pagination' => [
                'current_page' => $result['current_page'],
                'total_pages' => $result['pages'],
                'total_items' => $result['total'],
                'items_per_page' => $limit
            ]
        ]);
    }
    
    /**
     * Obtém denúncia específica
     */
    private function getDenuncia($protocolo) {
        if (!$this->apiAuth->hasPermission('denuncias.view.all') && 
            !$this->apiAuth->hasPermission('denuncias.view.assigned')) {
            throw new Exception('Sem permissão para visualizar denúncias', 403);
        }
        
        try {
            $denuncia = $this->denunciaModel->consultar($protocolo);
            
            // Verificar se usuário pode ver esta denúncia específica
            if (!$this->apiAuth->hasPermission('denuncias.view.all')) {
                $user = $this->apiAuth->getCurrentUser();
                if ($denuncia['admin_responsavel_id'] != $user['sub']) {
                    throw new Exception('Sem permissão para visualizar esta denúncia', 403);
                }
            }
            
            return $this->successResponse($denuncia);
            
        } catch (Exception $e) {
            throw new Exception('Denúncia não encontrada', 404);
        }
    }
    
    /**
     * Cria nova denúncia
     */
    private function createDenuncia() {
        if (!$this->apiAuth->hasPermission('denuncias.create')) {
            throw new Exception('Sem permissão para criar denúncias', 403);
        }
        
        $input = $this->getJsonInput();
        
        if (!isset($input['descricao']) || empty(trim($input['descricao']))) {
            throw new Exception('Descrição é obrigatória', 400);
        }
        
        try {
            $protocolo = $this->denunciaModel->salvar(
                $input['descricao'],
                null, // anexo via API não implementado ainda
                $input['categorias'] ?? []
            );
            
            $user = $this->apiAuth->getCurrentUser();
            $this->logger->audit('denuncia_created_api', 'denuncia', $protocolo, [
                'api_user' => $user['sub']
            ]);
            
            return $this->successResponse([
                'protocolo' => $protocolo,
                'message' => 'Denúncia criada com sucesso'
            ], 201);
            
        } catch (Exception $e) {
            throw new Exception('Erro ao criar denúncia: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Atualiza denúncia
     */
    private function updateDenuncia($protocolo) {
        if (!$this->apiAuth->hasPermission('denuncias.update')) {
            throw new Exception('Sem permissão para atualizar denúncias', 403);
        }
        
        $input = $this->getJsonInput();
        
        if (!isset($input['status'])) {
            throw new Exception('Status é obrigatório para atualização', 400);
        }
        
        try {
            $user = $this->apiAuth->getCurrentUser();
            $resposta = $input['resposta'] ?? '';
            
            $success = $this->denunciaModel->atualizarStatus(
                $protocolo, 
                $input['status'], 
                $resposta
            );
            
            if (!$success) {
                throw new Exception('Erro ao atualizar denúncia');
            }
            
            $this->logger->audit('denuncia_updated_api', 'denuncia', $protocolo, [
                'api_user' => $user['sub'],
                'new_status' => $input['status']
            ]);
            
            return $this->successResponse([
                'message' => 'Denúncia atualizada com sucesso'
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Erro ao atualizar denúncia: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Remove denúncia
     */
    private function deleteDenuncia($protocolo) {
        if (!$this->apiAuth->hasPermission('denuncias.delete')) {
            throw new Exception('Sem permissão para excluir denúncias', 403);
        }
        
        try {
            $success = $this->denunciaModel->excluir($protocolo);
            
            if (!$success) {
                throw new Exception('Erro ao excluir denúncia');
            }
            
            $user = $this->apiAuth->getCurrentUser();
            $this->logger->audit('denuncia_deleted_api', 'denuncia', $protocolo, [
                'api_user' => $user['sub']
            ]);
            
            return $this->successResponse([
                'message' => 'Denúncia excluída com sucesso'
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Erro ao excluir denúncia: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Gerencia estatísticas
     */
    private function handleStats($method, $segments) {
        if ($method !== 'GET') {
            throw new Exception('Método não permitido para estatísticas', 405);
        }
        
        if (!$this->apiAuth->hasPermission('reports.view')) {
            throw new Exception('Sem permissão para visualizar estatísticas', 403);
        }
        
        $stats = $this->denunciaModel->getOptimizedStats();
        
        return $this->successResponse($stats);
    }
    
    /**
     * Health check da API
     */
    private function healthCheck() {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => Environment::get('APP_VERSION', '1.0.0'),
            'environment' => Environment::get('APP_ENV', 'production'),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage()
            ]
        ];
        
        $overallHealth = array_reduce($health['checks'], function($carry, $check) {
            return $carry && $check['status'] === 'ok';
        }, true);
        
        if (!$overallHealth) {
            $health['status'] = 'unhealthy';
            http_response_code(503);
        }
        
        return $this->successResponse($health);
    }
    
    /**
     * Documentação da API
     */
    private function apiDocumentation() {
        $docs = [
            'name' => 'HSFA Denúncias API',
            'version' => '1.0.0',
            'description' => 'API REST para o sistema de denúncias do Hospital São Francisco de Assis',
            'base_url' => Environment::get('APP_URL') . '/api',
            'authentication' => [
                'type' => 'Bearer Token (JWT)',
                'login_endpoint' => '/auth/login',
                'refresh_endpoint' => '/auth/refresh'
            ],
            'endpoints' => [
                'auth' => [
                    'POST /auth/login' => 'Autenticação',
                    'POST /auth/refresh' => 'Renovar token',
                    'POST /auth/logout' => 'Logout'
                ],
                'denuncias' => [
                    'GET /denuncias' => 'Listar denúncias',
                    'GET /denuncias/{protocolo}' => 'Visualizar denúncia',
                    'POST /denuncias' => 'Criar denúncia',
                    'PUT /denuncias/{protocolo}' => 'Atualizar denúncia',
                    'DELETE /denuncias/{protocolo}' => 'Excluir denúncia'
                ],
                'stats' => [
                    'GET /stats' => 'Estatísticas gerais'
                ],
                'health' => [
                    'GET /health' => 'Status da API'
                ]
            ]
        ];
        
        return $this->successResponse($docs);
    }
    
    /**
     * Verifica status do banco de dados
     */
    private function checkDatabase() {
        try {
            $conn = Database::getInstance()->getConnection();
            $result = $conn->query("SELECT 1");
            
            return [
                'status' => 'ok',
                'response_time' => 0, // Implementar medição se necessário
                'message' => 'Database connection successful'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica status do cache
     */
    private function checkCache() {
        try {
            $cache = Cache::getInstance();
            $testKey = 'health_check_' . time();
            $cache->set($testKey, 'test', 10);
            $result = $cache->get($testKey);
            $cache->delete($testKey);
            
            return [
                'status' => $result === 'test' ? 'ok' : 'error',
                'message' => $result === 'test' ? 'Cache is working' : 'Cache test failed'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica storage
     */
    private function checkStorage() {
        try {
            $testFile = BASE_PATH . '/storage/test_' . time() . '.tmp';
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);
            
            return [
                'status' => $content === 'test' ? 'ok' : 'error',
                'message' => $content === 'test' ? 'Storage is writable' : 'Storage test failed'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtém input JSON
     */
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido', 400);
        }
        
        return $data ?? [];
    }
    
    /**
     * Resposta de sucesso
     */
    private function successResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Resposta de erro
     */
    private function errorResponse($message, $status = 500) {
        http_response_code($status);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ],
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
