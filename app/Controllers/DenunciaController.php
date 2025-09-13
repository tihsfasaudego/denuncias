<?php
require_once __DIR__ . '/../Models/Denuncia.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/FileUpload.php';

class DenunciaController {
    private $conn;
    private $denunciaModel;

    public function __construct() {
        try {
            // Configurar exibição de erros baseado no ambiente
            if (Environment::isProduction()) {
                ini_set('display_errors', 0);
                error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            } else if (Environment::isDebug()) {
                ini_set('display_errors', 1);
                error_reporting(E_ALL);
            } else {
                ini_set('display_errors', 0);
                error_reporting(E_ALL);
            }
            
            // Verificar se a sessão já foi iniciada antes de iniciar
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $this->conn = Database::getInstance()->getConnection();
            $this->denunciaModel = new Denuncia();
        } catch (Exception $e) {
            error_log("Erro ao conectar ao banco: " . $e->getMessage());
            
            // Resposta de erro mais direta para evitar problemas de headers
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao conectar ao banco de dados.'
            ]);
            exit;
        }
    }

    private function jsonResponse($data, $success = true, $statusCode = 200) {
        // Limpar TUDO antes de qualquer saída
        @ob_clean();
        @ob_end_clean();
        
        // Forçar headers corretos
        if (!headers_sent()) {
            header_remove();
            header('Content-Type: application/json');
            http_response_code($statusCode);
        }

        // Construir resposta básica
        $response = [
            'success' => $success,
            'message' => is_string($data) ? $data : null
        ];

        if (is_array($data)) {
            unset($response['message']);
            $response = array_merge($response, $data);
        }

        // Enviar resposta e encerrar
        die(json_encode($response));
    }

    private function jsonError($message, $statusCode = 400) {
        $this->jsonResponse($message, false, $statusCode);
    }

    private function jsonSuccess($data) {
        $this->jsonResponse($data, true, 200);
    }

    // Exibe o formulário de denúncia
    public function index() {
        try {
            // Verificar se a conexão com o banco está ativa
            if (!$this->conn) {
                throw new Exception("Conexão com o banco de dados falhou.");
            }

            // Gerar novo token CSRF se não existir
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            // Carregar categorias
            $stmt = $this->conn->prepare("
                SELECT id, nome, descricao 
                FROM categorias 
                WHERE ativo = 1 
                ORDER BY nome
            ");

            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta de categorias.");
            }

            $stmt->execute();
            $categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $pageTitle = 'Nova Denúncia';
            ob_start();
            require __DIR__ . '/../Views/denuncias/index.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';

        } catch (Exception $e) {
            error_log("Erro no DenunciaController::index - " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar página de denúncia");
        }
    }

    public function store() {
        // Desabilitar exibição de erros
        @error_reporting(0);
        @ini_set('display_errors', 0);
        
        // Limpar qualquer saída anterior
        @ob_clean();
        @ob_end_clean();

        try {
            // Verificar método
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                $this->jsonError("Método inválido");
                return;
            }
            
            // Verificar rate limiting para criação de denúncias
            if (!Security::checkRateLimit('create_denuncia', 3, 3600)) { // 3 denúncias por hora
                Security::logSuspiciousActivity('rate_limit_exceeded', ['action' => 'create_denuncia']);
                $this->jsonError("Muitas denúncias criadas recentemente. Tente novamente em uma hora.");
                return;
            }

            // Validar token CSRF
            if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
                $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $this->jsonError("Token de segurança inválido");
                return;
            }

            // Validar campos obrigatórios
            if (empty($_POST['descricao'])) {
                $this->jsonError("A descrição da denúncia é obrigatória");
                return;
            }

            if (empty($_POST['categoria']) || !is_array($_POST['categoria'])) {
                $this->jsonError("Selecione pelo menos uma categoria");
                return;
            }

            // Preparar dados
            $dados = [
                'descricao' => trim($_POST['descricao']),
                'data_ocorrencia' => !empty($_POST['data_ocorrencia']) ? $_POST['data_ocorrencia'] : null,
                'local_ocorrencia' => !empty($_POST['local_ocorrencia']) ? trim($_POST['local_ocorrencia']) : null,
                'anexo' => null
            ];

            // Processar anexo se existir
            if (!empty($_FILES['anexo']['name'])) {
                $dados['anexo'] = $this->processarAnexo($_FILES['anexo']);
                if ($dados['anexo'] === false) {
                    $this->jsonError("Erro ao processar o arquivo anexado");
                    return;
                }
            }

            // Salvar denúncia
            $protocolo = $this->denunciaModel->store($dados, $_POST['categoria']);
            
            if (!$protocolo) {
                throw new Exception("Erro ao registrar denúncia");
            }

            // Retornar sucesso
            $this->jsonSuccess([
                'protocolo' => $protocolo,
                'message' => 'Denúncia registrada com sucesso!'
            ]);

        } catch (Exception $e) {
            error_log("Erro ao processar denúncia: " . $e->getMessage());
            $this->jsonError("Erro ao processar denúncia: " . $e->getMessage());
        }
    }

    // Página de consulta de status
    public function status() {
        try {
            $pageTitle = 'Consultar Denúncia';
            ob_start();
            require __DIR__ . '/../Views/denuncias/status.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro no DenunciaController::status - " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar página de consulta");
        }
    }

    // Processa a consulta de status
    public function checkStatus() {
        // Desabilitar a exibição de erros para evitar HTML no output
        @ini_set('display_errors', 0);
        error_reporting(E_ALL);
        
        // Limpar qualquer saída anterior
        @ob_clean();
        @ob_end_clean();
        
        try {
            // Pegar protocolo do GET ou POST
            $protocolo = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $protocolo = $_POST['protocolo'] ?? '';
            } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $protocolo = $_GET['protocolo'] ?? '';
            }
            
            if (empty($protocolo)) {
                throw new Exception("Protocolo não informado");
            }
            
            // Log para diagnóstico
            error_log("Consultando denúncia com protocolo: $protocolo");
            
            $denuncia = $this->denunciaModel->consultar($protocolo);
            
            if ($denuncia) {
                // Garantir que todos os campos existam, mesmo que vazios
                $denuncia = array_merge([
                    'protocolo' => '',
                    'descricao' => '',
                    'data_criacao' => '',
                    'data_atualizacao' => '',
                    'status' => 'Pendente',
                    'situacao' => 'Pendente',
                    'resposta' => '',
                    'parecer' => '',
                    'historico' => []
                ], $denuncia);
                
                // Formata a data para exibição
                if (!empty($denuncia['data_criacao'])) {
                    $denuncia['data_criacao'] = date('d/m/Y H:i', strtotime($denuncia['data_criacao']));
                }
                
                if (!empty($denuncia['data_atualizacao'])) {
                    $denuncia['data_atualizacao'] = date('d/m/Y H:i', strtotime($denuncia['data_atualizacao']));
                }
                
                // Log para diagnóstico
                error_log("Denúncia encontrada: " . json_encode($denuncia));
                
                // Forçar headers corretos
                if (!headers_sent()) {
                    header_remove();
                    header('Content-Type: application/json');
                    http_response_code(200);
                }
                
                echo json_encode([
                    'success' => true,
                    'denuncia' => $denuncia
                ]);
            } else {
                throw new Exception("Denúncia não encontrada");
            }
            
        } catch (Exception $e) {
            error_log("Erro no DenunciaController::checkStatus - " . $e->getMessage());
            
            // Forçar headers corretos
            if (!headers_sent()) {
                header_remove();
                header('Content-Type: application/json');
                http_response_code(400);
            }
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    private function processarAnexo($arquivo) {
        try {
            $fileUpload = new FileUpload();
            $result = $fileUpload->upload($arquivo, 'denuncia');
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            return $result['filename'];
            
        } catch (Exception $e) {
            error_log("Erro no DenunciaController::processarAnexo - " . $e->getMessage());
            throw $e; // Re-lançar para que o erro seja tratado adequadamente
        }
    }

    private function renderError($code, $message) {
        http_response_code($code);
        $pageTitle = 'Erro ' . $code;
        ob_start();
        require __DIR__ . '/../Views/errors/error.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
    }

    public function details() {
        try {
            $protocolo = $_GET['protocolo'] ?? '';
            
            if (empty($protocolo)) {
                throw new Exception("Protocolo não informado");
            }

            $denuncia = $this->denunciaModel->consultar($protocolo);
            
            if (!$denuncia) {
                throw new Exception("Denúncia não encontrada");
            }

            // Formatar datas
            $denuncia['data_criacao'] = date('d/m/Y H:i', strtotime($denuncia['data_criacao']));
            if (!empty($denuncia['data_atualizacao'])) {
                $denuncia['data_atualizacao'] = date('d/m/Y H:i', strtotime($denuncia['data_atualizacao']));
            }

            $pageTitle = 'Detalhes da Denúncia';
            ob_start();
            require __DIR__ . '/../Views/denuncias/details.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';

        } catch (Exception $e) {
            error_log("Erro ao exibir detalhes da denúncia: " . $e->getMessage());
            $this->renderError(404, $e->getMessage());
        }
    }
}
?>
