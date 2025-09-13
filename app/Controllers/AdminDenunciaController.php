<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Denuncia.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';
require_once __DIR__ . '/../Core/Logger.php';

/**
 * Controller para operações administrativas de denúncias
 * Lida com visualização, atualização de status e respostas
 */
class AdminDenunciaController {
    private $conn;
    private $denunciaModel;
    private $auth;
    private $middleware;
    private $logger;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->conn = Database::getInstance()->getConnection();
        $this->denunciaModel = new Denuncia();
        $this->auth = Auth::getInstance();
        $this->middleware = new AuthMiddleware();
        $this->logger = Logger::getInstance();
    }

    /**
     * Verifica autenticação e permissões básicas
     */
    private function checkAuth() {
        if (!Auth::check()) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Não autenticado']);
                exit;
            }
            header('Location: /admin/login');
            exit;
        }

        // Verificar inatividade (30 minutos)
        if (isset($_SESSION['admin_last_activity']) && time() - $_SESSION['admin_last_activity'] > 1800) {
            Auth::logout();
            $_SESSION['error'] = 'Sua sessão expirou. Por favor, faça login novamente.';
            header('Location: /admin/login?expired=1');
            exit;
        }

        $_SESSION['admin_last_activity'] = time();
    }

    /**
     * Verifica se é uma requisição AJAX
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Exibe detalhes da denúncia
     * GET /admin/denuncia/{id}
     */
    public function show($params) {
        // Debug: Log entrada
        error_log("AdminDenunciaController::show() chamado com parâmetros: " . print_r($params, true));
        
        try {
            $this->checkAuth();

            // Verificar permissão para visualizar denúncias
            if (!$this->middleware->hasPermission('denuncias.view.all') &&
                !$this->middleware->hasPermission('denuncias.view.assigned')) {
                error_log("AdminDenunciaController::show() - Sem permissão");
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                    exit;
                }
                $this->middleware->renderAccessDenied();
            }

            $id = $params['id'] ?? null;
            error_log("AdminDenunciaController::show() - ID recebido: " . $id);

            if (!$id) {
                error_log("AdminDenunciaController::show() - ID não informado");
                $_SESSION['error'] = 'ID da denúncia não informado';
                header('Location: /admin/denuncias');
                exit;
            }

            // Buscar denúncia
            $denuncia = $this->denunciaModel->buscarPorId($id);
            error_log("AdminDenunciaController::show() - Denúncia encontrada: " . ($denuncia ? 'SIM' : 'NÃO'));

            if (!$denuncia) {
                error_log("AdminDenunciaController::show() - Denúncia não encontrada para ID: " . $id);
                $_SESSION['error'] = 'Denúncia não encontrada';
                header('Location: /admin/denuncias');
                exit;
            }

            // Verificar se o usuário pode ver esta denúncia específica
            if (!$this->middleware->hasPermission('denuncias.view.all') &&
                $denuncia['admin_responsavel_id'] != Auth::id()) {
                error_log("AdminDenunciaController::show() - Sem permissão específica para esta denúncia");
                $_SESSION['error'] = 'Você não tem permissão para visualizar esta denúncia';
                header('Location: /admin/denuncias');
                exit;
            }

            // Registrar atividade
            $this->auth->registerActivity("visualizou_denuncia", "denuncia", $denuncia['id']);

            // Gerar CSRF token se não existir
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            // Carregar a view
            $pageTitle = 'Visualizar Denúncia - ' . $denuncia['protocolo'];
            $isAdminPage = true;
            $currentPage = 'denuncias';
            
            error_log("AdminDenunciaController::show() - Carregando view");
            
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/visualizar.php';
            $content = ob_get_clean();
            
            error_log("AdminDenunciaController::show() - View carregada, content length: " . strlen($content));
            
            require __DIR__ . '/../Views/layouts/base.php';
            
            error_log("AdminDenunciaController::show() - Layout carregado");
            
        } catch (Exception $e) {
            error_log("AdminDenunciaController::show() - Erro: " . $e->getMessage());
            error_log("AdminDenunciaController::show() - Stack trace: " . $e->getTraceAsString());
            
            // Mostrar erro se em modo debug
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo "<h1>Erro na visualização da denúncia</h1>";
                echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            } else {
                $_SESSION['error'] = 'Erro interno ao carregar denúncia';
                header('Location: /admin/denuncias');
                exit;
            }
        }
    }

    /**
     * Retorna dados da denúncia para AJAX
     * GET /admin/denuncia/{id}/dados
     */
    public function getDados($params) {
        header('Content-Type: application/json');
        
        try {
            // Verificar autenticação básica
            if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Não autenticado']);
                exit;
            }

            $id = $params['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID não informado']);
                exit;
            }

            // Conectar diretamente ao banco
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("Erro de conexão: " . $conn->connect_error);
            }
            
            // Buscar denúncia com query simples
            $stmt = $conn->prepare("
                SELECT d.*, 
                       GROUP_CONCAT(c.nome) as categorias,
                       a.nome as responsavel
                FROM denuncias d
                LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
                LEFT JOIN categorias c ON dc.categoria_id = c.id
                LEFT JOIN admin a ON d.admin_responsavel_id = a.id
                WHERE d.id = ?
                GROUP BY d.id
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $denuncia = $result->fetch_assoc();

            if (!$denuncia) {
                // Verificar denúncias disponíveis
                $result2 = $conn->query("SELECT id, protocolo FROM denuncias LIMIT 10");
                $todasDenuncias = $result2->fetch_all(MYSQLI_ASSOC);
                
                $conn->close();
                http_response_code(404);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Denúncia não encontrada',
                    'debug' => [
                        'id_solicitado' => $id,
                        'denuncias_disponiveis' => $todasDenuncias
                    ]
                ]);
                exit;
            }
            
            $conn->close();

            echo json_encode([
                'success' => true,
                'denuncia' => $denuncia
            ]);

        } catch (Exception $e) {
            error_log("Erro em getDados: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Atualiza o status da denúncia
     * POST /admin/denuncia/{id}/status
     */
    public function updateStatus($params) {
        header('Content-Type: application/json');
        
        try {
            // Verificar autenticação básica
            if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Não autenticado']);
                exit;
            }

            $id = $params['id'] ?? null;
            $status = $_POST['status'] ?? '';
            $observacao = $_POST['observacao'] ?? '';

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID da denúncia não informado']);
                exit;
            }

            if (empty($status)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Status não informado']);
                exit;
            }

            // Validar status permitidos
            $statusPermitidos = ['Pendente', 'Em Análise', 'Em Investigação', 'Concluída', 'Arquivada'];
            if (!in_array($status, $statusPermitidos)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Status inválido']);
                exit;
            }

            // Conectar diretamente ao banco
            $adminId = $_SESSION['admin']['id'];
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("Erro de conexão: " . $conn->connect_error);
            }
            
            // Atualizar denúncia
            $stmt = $conn->prepare("
                UPDATE denuncias 
                SET status = ?, 
                    admin_responsavel_id = ?,
                    data_conclusao = CASE WHEN ? = 'Concluída' THEN NOW() ELSE NULL END
                WHERE id = ?
            ");
            $stmt->bind_param("sisi", $status, $adminId, $status, $id);
            $success = $stmt->execute();
            
            if (!$success) {
                $conn->close();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status no banco']);
                exit;
            }

            // Inserir no histórico se a tabela existir
            try {
                $stmt2 = $conn->prepare("
                    INSERT INTO historico_status (denuncia_id, status_novo, admin_id, observacao) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt2->bind_param("isis", $id, $status, $adminId, $observacao);
                $stmt2->execute();
            } catch (Exception $e) {
                error_log("Erro ao inserir histórico: " . $e->getMessage());
            }
            
            $conn->close();

            echo json_encode([
                'success' => true,
                'message' => 'Status atualizado com sucesso',
                'id' => $id,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            error_log("Erro em updateStatus: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Adiciona resposta à denúncia
     * POST /admin/denuncia/{id}/responder
     */
    public function responder($params) {
        $this->checkAuth();

        // Verificar permissão para responder denúncias
        if (!$this->middleware->hasPermission('denuncias.respond')) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            exit;
        }

        $id = $params['id'] ?? null;
        $resposta = $_POST['resposta'] ?? '';
        $notificar = isset($_POST['notificar']) && $_POST['notificar'] === 'true';

        if (!$id) {
            $this->jsonError('ID da denúncia não informado', 400);
        }

        if (empty(trim($resposta))) {
            $this->jsonError('Resposta não pode estar vazia', 400);
        }

        if (strlen($resposta) > 5000) {
            $this->jsonError('Resposta muito longa (máximo 5000 caracteres)', 400);
        }

        // Buscar denúncia
        $denuncia = $this->denunciaModel->buscarPorId($id);
        if (!$denuncia) {
            $this->jsonError('Denúncia não encontrada', 404);
        }

        // Verificar se o usuário pode responder esta denúncia específica
        if (!$this->middleware->hasPermission('denuncias.view.all') &&
            $denuncia['admin_responsavel_id'] != Auth::id()) {
            $this->jsonError('Você não tem permissão para responder esta denúncia', 403);
        }

        try {
            $this->conn->begin_transaction();

            // Inserir resposta na tabela respostas
            $stmt = $this->conn->prepare("
                INSERT INTO respostas (denuncia_id, admin_id, resposta, data_criacao)
                VALUES (?, ?, ?, NOW())
            ");

            $stmt->bind_param("iis", $id, $_SESSION['admin']['id'], $resposta);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao salvar resposta: " . $stmt->error);
            }

            // Atualizar denúncia com resposta final se for o caso
            if ($denuncia['status'] !== 'Concluída') {
                $stmt = $this->conn->prepare("
                    UPDATE denuncias
                    SET conclusao_descricao = ?,
                        data_conclusao = NOW(),
                        status = 'Concluída'
                    WHERE id = ?
                ");

                $stmt->bind_param("si", $resposta, $id);

                if (!$stmt->execute()) {
                    throw new Exception("Erro ao atualizar denúncia: " . $stmt->error);
                }
            }

            $this->conn->commit();

            // Registrar atividade
            $this->auth->registerActivity(
                "respondeu_denuncia",
                "denuncia",
                $denuncia['id'],
                ["resposta_length" => strlen($resposta), "notificar" => $notificar]
            );

            // Log de auditoria
            $this->logger->audit('denuncia_response', 'admin', Auth::id(), [
                'denuncia_id' => $denuncia['id'],
                'protocolo' => $denuncia['protocolo'],
                'resposta_length' => strlen($resposta),
                'notificar_denunciante' => $notificar
            ]);

            $this->jsonSuccess([
                'message' => 'Resposta adicionada com sucesso',
                'id' => $denuncia['id'],
                'status' => 'Concluída',
                'responded_by' => $_SESSION['admin']['nome'],
                'responded_at' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao responder denúncia: " . $e->getMessage());
            $this->jsonError('Erro ao processar resposta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper para resposta JSON de sucesso
     */
    private function jsonSuccess($data = []) {
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(array_merge(['success' => true], $data));
        exit;
    }

    /**
     * Helper para resposta JSON de erro
     */
    private function jsonError($message, $statusCode = 400) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }

    /**
     * Renderiza erro
     */
    private function renderError($code, $message) {
        http_response_code($code);
        $pageTitle = 'Erro';
        $error = $message;
        $isAdminPage = true;
        ob_start();
        require __DIR__ . '/../Views/errors/error.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
        exit;
    }
}
