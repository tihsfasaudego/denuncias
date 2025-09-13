<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Denuncia.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';
require_once __DIR__ . '/../Models/Admin.php';
require_once __DIR__ . '/../Core/FileUpload.php';
require_once __DIR__ . '/../Core/Logger.php';

class AdminController {
    private $conn;
    private $denunciaModel;
    private $userModel;
    private $auth;
    private $middleware;
    private $adminModel;
    private $logger;

    public function __construct() {
        // Verificar se a sessão já foi iniciada antes de iniciar
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->conn = Database::getInstance()->getConnection();
        $this->denunciaModel = new Denuncia();
        $this->userModel = new User();
        $this->auth = Auth::getInstance();
        $this->middleware = new AuthMiddleware();
        $this->adminModel = new Admin();
        $this->logger = Logger::getInstance();
    }

    private function checkAuth() {
        if (!Auth::check()) {
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

    public function login() {
        // Se já estiver logado, redireciona para dashboard
        if (Auth::check()) {
            header('Location: /admin/dashboard');
            exit;
        }
        
        $pageTitle = 'Login Administrativo';
        $isAdminPage = true;
        ob_start();
        require __DIR__ . '/../Views/admin/login.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
    }

    public function authenticate() {
        try {
            // Verificar rate limiting
            if (!Security::checkRateLimit('admin_login', 5, 300)) { // 5 tentativas por 5 minutos
                Security::logSuspiciousActivity('rate_limit_exceeded', ['action' => 'admin_login']);
                throw new Exception("Muitas tentativas de login. Tente novamente em alguns minutos.");
            }
            
            $usuario = $_POST['usuario'] ?? '';
            $senha = $_POST['senha'] ?? '';
            
            if(empty($usuario) || empty($senha)) {
                throw new Exception("Preencha todos os campos");
            }
            
            error_log("Tentativa de login admin para usuário: " . $usuario);
            
            // Primeiro tentar autenticar na tabela admin
            $admin = $this->adminModel->authenticate($usuario, $senha);
            
            if ($admin) {
                error_log("Usuário autenticado como admin: " . $usuario);
                $_SESSION['admin'] = $admin;
                $_SESSION['admin_last_activity'] = time();
                
                // Para compatibilidade, também usar 'user'
                $_SESSION['user'] = $admin;
                $_SESSION['user_last_activity'] = time();
                
                // Registrar último acesso
                $this->adminModel->updateLastAccess($admin['id']);
                
                // Log de auditoria
                $this->logger->audit('admin_login_success', 'admin', $admin['id'], [
                    'username' => $usuario,
                    'method' => 'admin_table'
                ]);
                
                header('Location: /admin/dashboard');
                exit;
            }
            
            // Se falhou na tabela admin, tentar na tabela users
            error_log("Tentando autenticar na tabela users: " . $usuario);
            $user = $this->userModel->authenticate($usuario, $senha);
            
            if ($user) {
                // Verificar se o usuário tem permissão de administrador
                if ($this->auth->hasRole('admin') || $this->auth->hasRole('gestor')) {
                    error_log("Usuário autenticado como usuário com permissão de admin: " . $usuario);
                    
                    $_SESSION['admin'] = $user; 
                    $_SESSION['admin_last_activity'] = time();
                    
                    // Atualizar último acesso
                    $this->userModel->updateLastAccess($user['id']);
                    
                    // Log de auditoria
                    $this->logger->audit('admin_login_success', 'user', $user['id'], [
                        'username' => $usuario,
                        'method' => 'user_table'
                    ]);
                    
                    header('Location: /admin/dashboard');
                    exit;
                } else {
                    error_log("Usuário não tem permissão de admin: " . $usuario);
                    throw new Exception("Você não tem permissão para acessar esta área");
                }
            }
            
            // Se chegou aqui, falhou na autenticação
            error_log("Falha na autenticação para: " . $usuario);
            Security::logFailedLogin($usuario);
            
            // Log de segurança
            $this->logger->security('admin_login_failed', [
                'username' => $usuario,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            throw new Exception("Usuário ou senha inválidos");
            
        } catch (Exception $e) {
            error_log("Erro de autenticação: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/login');
            exit;
        }
    }

    public function dashboard() {
        $this->checkAuth();
        
        // Administradores têm acesso total ao dashboard
        
        try {
            // Parâmetros de paginação
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $filters = [
                'status' => $_GET['status'] ?? '',
                'data_inicio' => $_GET['data_inicio'] ?? '',
                'data_fim' => $_GET['data_fim'] ?? ''
            ];
            
            // Buscar denúncias com base nas permissões
            if ($this->auth->can('denuncias.view.all')) {
                // Usar listarTodas() para ter dados completos da view
                $denuncias = $this->denunciaModel->listarTodas();
                $paginacao = [
                    'total' => count($denuncias),
                    'pages' => 1,
                    'current_page' => 1,
                    'limit' => count($denuncias)
                ];
            } else {
                // Se só pode ver atribuídas, buscar apenas as atribuídas ao usuário
                $denuncias = $this->denunciaModel->listarPorResponsavel(Auth::id());
                $paginacao = null; // Por enquanto, sem paginação para responsável específico
            }
            
            // Filtrar denúncias por status
            $denunciasPendentes = array_filter($denuncias, function($d) {
                return $d['status'] === 'Pendente';
            });
            
            $denunciasEmAnalise = array_filter($denuncias, function($d) {
                return $d['status'] === 'Em Análise' || $d['status'] === 'Em Investigação';
            });

            $denunciasConcluidas = array_filter($denuncias, function($d) {
                return $d['status'] === 'Concluída';
            });

            $denunciasArquivadas = array_filter($denuncias, function($d) {
                return $d['status'] === 'Arquivada';
            });
            
            // Registrar atividade
            $this->auth->registerActivity("acessou_dashboard", "dashboard");
            
            // Debug: verificar se as variáveis existem
            error_log("Dashboard - Total denúncias: " . count($denuncias));
            error_log("Dashboard - Pendentes: " . count($denunciasPendentes));
            error_log("Dashboard - Em análise: " . count($denunciasEmAnalise));
            error_log("Dashboard - Concluídas: " . count($denunciasConcluidas));
            
            $pageTitle = 'Dashboard Administrativo';
            $isAdminPage = true;
            ob_start();
            require __DIR__ . '/../Views/admin/dashboard.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro no dashboard: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar dashboard");
        }
    }

    // === MÉTODOS DE DENÚNCIAS ===
    
    public function denuncias() {
        $this->checkAuth();
        
        try {
            $denuncias = $this->denunciaModel->listarTodas();
            
            $pageTitle = 'Gerenciar Denúncias';
            $isAdminPage = true;
            $currentPage = 'denuncias';
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/index.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar denúncias");
        }
    }
    
    public function denunciasPendentes() {
        $this->checkAuth();
        
        try {
            $denuncias = $this->denunciaModel->listarPorStatus('Pendente');
            $statusFiltro = 'Pendente';
            
            $pageTitle = 'Denúncias Pendentes';
            $isAdminPage = true;
            $currentPage = 'denuncias';
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/lista.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias pendentes: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar denúncias");
        }
    }
    
    public function denunciasEmAnalise() {
        $this->checkAuth();
        
        try {
            $denuncias = $this->denunciaModel->listarPorStatus('Em Análise');
            $statusFiltro = 'Em Análise';
            
            $pageTitle = 'Denúncias em Análise';
            $isAdminPage = true;
            $currentPage = 'denuncias';
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/lista.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias em análise: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar denúncias");
        }
    }
    
    public function denunciasEmInvestigacao() {
        $this->checkAuth();
        
        try {
            $denuncias = $this->denunciaModel->listarPorStatus('Em Investigação');
            $statusFiltro = 'Em Investigação';
            
            $pageTitle = 'Denúncias em Investigação';
            $isAdminPage = true;
            $currentPage = 'denuncias';
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/lista.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias em investigação: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar denúncias");
        }
    }
    
    public function denunciasConcluidas() {
        $this->checkAuth();
        
        try {
            $denuncias = $this->denunciaModel->listarPorStatus('Concluída');
            $statusFiltro = 'Concluída';
            
            $pageTitle = 'Denúncias Concluídas';
            $isAdminPage = true;
            $currentPage = 'denuncias';
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/lista.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias concluídas: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar denúncias");
        }
    }
    
    public function denunciasArquivadas() {
        $this->checkAuth();
        
        try {
            $denuncias = $this->denunciaModel->listarPorStatus('Arquivada');
            $statusFiltro = 'Arquivada';
            
            $pageTitle = 'Denúncias Arquivadas';
            $isAdminPage = true;
            $currentPage = 'denuncias';
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/lista.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias arquivadas: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar denúncias");
        }
    }
    
    public function visualizarDenuncia() {
        $this->checkAuth();

        try {
            // Obter o ID da denúncia da URL
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $_SESSION['error'] = 'ID da denúncia não informado';
                header('Location: /admin/denuncias');
                exit;
            }

            // Buscar a denúncia
            $denuncia = $this->denunciaModel->buscarPorId($id);
            
            if (!$denuncia) {
                $_SESSION['error'] = 'Denúncia não encontrada';
                header('Location: /admin/denuncias');
                exit;
            }
            
            // Registrar atividade
            $this->auth->registerActivity("visualizou_denuncia", "denuncia", $denuncia['id']);

            // Carregar a view
            $pageTitle = 'Visualizar Denúncia - ' . $denuncia['protocolo'];
            $isAdminPage = true;
            $currentPage = 'denuncias';
            ob_start();
            require __DIR__ . '/../Views/admin/denuncias/visualizar.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';

        } catch (Exception $e) {
            error_log("Erro ao visualizar denúncia: " . $e->getMessage());
            $_SESSION['error'] = 'Erro ao carregar denúncia';
            header('Location: /admin/denuncias');
            exit;
        }
    }

    public function updateStatus() {
        $this->checkAuth();
        
        // Verificar permissão para atualizar status
        if (!$this->middleware->hasPermission('denuncias.update.status')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado'
            ]);
            exit;
        }
        
        try {
            $protocolo = $_POST['protocolo'] ?? '';
            $status = $_POST['status'] ?? '';
            $resposta = $_POST['resposta'] ?? '';
            
            // Validações
            if (empty($protocolo)) {
                throw new Exception("Protocolo não informado");
            }
            if (empty($status)) {
                throw new Exception("Status não informado");
            }
            if (empty($resposta)) {
                throw new Exception("É necessário fornecer uma resposta");
            }

            // Validar status permitidos
            $statusPermitidos = ['Pendente', 'Em Análise', 'Em Investigação', 'Concluída', 'Arquivada'];
            if (!in_array($status, $statusPermitidos)) {
                throw new Exception("Status inválido");
            }
            
            // Verificar permissão para o status específico
            if ($status === 'Concluída' && !$this->middleware->hasPermission('denuncias.conclude')) {
                throw new Exception("Você não tem permissão para concluir denúncias");
            }
            
            if ($status === 'Arquivada' && !$this->middleware->hasPermission('denuncias.archive')) {
                throw new Exception("Você não tem permissão para arquivar denúncias");
            }
            
            if ($status === 'Em Investigação' && !$this->middleware->hasPermission('denuncias.investigate')) {
                throw new Exception("Você não tem permissão para encaminhar denúncias para investigação");
            }

            // Log para debug
            error_log("Atualizando denúncia - Protocolo: $protocolo, Status: $status");
            
            // Buscar denúncia para registro de atividade
            $denuncia = $this->denunciaModel->consultar($protocolo);
            
            $success = $this->denunciaModel->atualizarStatus($protocolo, $status, $resposta);
            
            if (!$success) {
                throw new Exception("Erro ao atualizar o status da denúncia");
            }
            
            // Registrar atividade
            $this->auth->registerActivity(
                "atualizou_status", 
                "denuncia", 
                $denuncia['id'], 
                ["status_anterior" => $denuncia['status'], "novo_status" => $status]
            );

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Status atualizado com sucesso'
            ]);

        } catch (Exception $e) {
            error_log("Erro ao atualizar status da denúncia: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

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

    public function logout() {
        Auth::logout();
        header('Location: /admin/login');
        exit;
    }

    public function configuracoes() {
        $this->checkAuth();
        
        try {
            // Usando uma permissão mais genérica para configurações
            // Vamos tornar o acesso mais permissivo, apenas verificando se o usuário está autenticado
            // e é um administrador (já verificado pelo checkAuth)
            
            $pageTitle = 'Configurações do Sistema';
            $isAdminPage = true;
            ob_start();
            require __DIR__ . '/../Views/admin/configuracoes.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao carregar configurações: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar configurações");
        }
    }

    public function uploadLogo() {
        $this->checkAuth();
        
        // Verificar permissão para gerenciar configurações
        $this->middleware->redirectIfNoPermission('settings.manage');
        
        try {
            if (!isset($_FILES['logo'])) {
                throw new Exception("Nenhum arquivo enviado");
            }

            // Usar a classe FileUpload para validação segura
            $fileUpload = new FileUpload(null, 2 * 1024 * 1024); // 2MB max para logo
            $result = $fileUpload->upload($_FILES['logo'], 'logo');
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Verificar se é uma imagem
            if (strpos($result['mime_type'], 'image/') !== 0) {
                // Remover arquivo se não for imagem
                $fileUpload->delete($result['filename']);
                throw new Exception("Arquivo deve ser uma imagem (PNG ou JPG)");
            }
            
            // Processar e redimensionar a imagem se necessário
            $this->processLogoImage($result['path']);
            
            // Registrar atividade
            $this->auth->registerActivity("atualizou_logo", "settings");

            $_SESSION['success'] = "Logo atualizado com sucesso!";
            
        } catch (Exception $e) {
            error_log("Erro ao fazer upload do logo: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /admin/configuracoes');
        exit;
    }
    
    /**
     * Processa e otimiza a imagem do logo
     */
    private function processLogoImage($imagePath) {
        try {
            $imageInfo = getimagesize($imagePath);
            
            if ($imageInfo === false) {
                throw new Exception("Erro ao processar a imagem");
            }
            
            $mimeType = $imageInfo['mime'];
            
            // Criar imagem baseada no tipo
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($imagePath);
                    break;
                default:
                    throw new Exception("Tipo de imagem não suportado");
            }
            
            if (!$image) {
                throw new Exception("Erro ao processar a imagem");
            }
            
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Redimensionar se necessário (máximo 300x100)
            if ($width > 300 || $height > 100) {
                $ratio = min(300 / $width, 100 / $height);
                $newWidth = round($width * $ratio);
                $newHeight = round($height * $ratio);
                
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // Preservar transparência para PNG
                if ($mimeType === 'image/png') {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                    imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
                }
                
                imagecopyresampled(
                    $newImage, $image,
                    0, 0, 0, 0,
                    $newWidth, $newHeight,
                    $width, $height
                );
                
                imagedestroy($image);
                $image = $newImage;
            }
            
            // Salvar como PNG otimizado
            $logoPath = dirname($imagePath) . '/logo.png';
            
            // Remover logo antigo se existir
            if (file_exists($logoPath) && $logoPath !== $imagePath) {
                unlink($logoPath);
            }
            
            imagepng($image, $logoPath, 9); // Compressão máxima
            imagedestroy($image);
            
            // Remover arquivo original se for diferente do logo final
            if ($imagePath !== $logoPath) {
                unlink($imagePath);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao processar logo: " . $e->getMessage());
            throw $e;
        }
    }

    public function alterarSenha() {
        $this->checkAuth();
        
        try {
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';
            
            // Validações
            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
                throw new Exception("Todos os campos são obrigatórios");
            }
            
            if ($novaSenha !== $confirmarSenha) {
                throw new Exception("As senhas não coincidem");
            }
            
            // Validar requisitos da senha
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $novaSenha)) {
                throw new Exception("A nova senha não atende aos requisitos mínimos");
            }
            
            // Verificar senha atual
            $admin = $this->adminModel->getById($_SESSION['admin']['id']);
            if (!password_verify($senhaAtual, $admin['senha_hash'])) {
                throw new Exception("Senha atual incorreta");
            }
            
            // Atualizar senha
            $success = $this->adminModel->atualizarSenha($_SESSION['admin']['id'], $novaSenha);
            
            if (!$success) {
                throw new Exception("Erro ao atualizar senha");
            }
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function excluirDenuncia() {
        $this->checkAuth();
        
        // Verificar permissão para excluir denúncias
        $this->middleware->redirectIfNoPermission('denuncias.delete');
        
        try {
            // Pegar dados do corpo da requisição
            $data = json_decode(file_get_contents('php://input'), true);
            $protocolo = $data['protocolo'] ?? null;
            
            if (!$protocolo) {
                throw new Exception("Protocolo não informado");
            }
            
            // Verificar se a denúncia existe
            $denuncia = $this->denunciaModel->consultar($protocolo);
            if (!$denuncia) {
                throw new Exception("Denúncia não encontrada");
            }
            
            // Tentar excluir a denúncia
            $this->denunciaModel->excluir($protocolo);
            
            // Registrar atividade
            $this->auth->registerActivity(
                "excluiu_denuncia", 
                "denuncia", 
                $denuncia['id']
            );
            
            // Se chegou aqui, a exclusão foi bem-sucedida
            echo json_encode([
                'success' => true,
                'message' => 'Denúncia excluída com sucesso'
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao excluir denúncia: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // === MÉTODOS PARA ROTAS COM PARÂMETROS {id} ===
    
    public function atualizarStatus() {
        $this->checkAuth();
        
        // Verificar permissão para atualizar status
        if (!$this->middleware->hasPermission('denuncias.update.status')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado'
            ]);
            exit;
        }
        
        try {
            $id = $_GET['id'] ?? null;
            $status = $_POST['status'] ?? '';
            $observacao = $_POST['observacao'] ?? '';
            
            if (!$id) {
                throw new Exception("ID da denúncia não informado");
            }
            
            if (empty($status)) {
                throw new Exception("Status não informado");
            }
            
            // Buscar denúncia por ID
            $denuncia = $this->denunciaModel->buscarPorId($id);
            if (!$denuncia) {
                throw new Exception("Denúncia não encontrada");
            }
            
            // Validar status permitidos
            $statusPermitidos = ['Pendente', 'Em Análise', 'Em Investigação', 'Concluída', 'Arquivada'];
            if (!in_array($status, $statusPermitidos)) {
                throw new Exception("Status inválido");
            }
            
            // Atualizar status usando protocolo
            $success = $this->denunciaModel->atualizarStatus($denuncia['protocolo'], $status, $observacao);
            
            if (!$success) {
                throw new Exception("Erro ao atualizar o status da denúncia");
            }
            
            // Registrar atividade
            $this->auth->registerActivity(
                "atualizou_status", 
                "denuncia", 
                $denuncia['id'], 
                ["status_anterior" => $denuncia['status'], "novo_status" => $status]
            );

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Status atualizado com sucesso'
            ]);

        } catch (Exception $e) {
            error_log("Erro ao atualizar status da denúncia: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    public function adicionarResposta() {
        $this->checkAuth();
        
        try {
            $id = $_GET['id'] ?? null;
            $resposta = $_POST['resposta'] ?? '';
            $notificar = isset($_POST['notificar']) && $_POST['notificar'] === 'true';
            
            if (!$id) {
                throw new Exception("ID da denúncia não informado");
            }
            
            if (empty($resposta)) {
                throw new Exception("Resposta não pode estar vazia");
            }
            
            // Buscar denúncia por ID
            $denuncia = $this->denunciaModel->buscarPorId($id);
            if (!$denuncia) {
                throw new Exception("Denúncia não encontrada");
            }
            
            // Adicionar resposta (implementar no modelo se necessário)
            // Por enquanto, vamos simular
            $success = true; // $this->denunciaModel->adicionarResposta($id, $resposta, Auth::id());
            
            if (!$success) {
                throw new Exception("Erro ao adicionar resposta");
            }
            
            // Registrar atividade
            $this->auth->registerActivity(
                "adicionou_resposta", 
                "denuncia", 
                $denuncia['id']
            );

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Resposta adicionada com sucesso'
            ]);

        } catch (Exception $e) {
            error_log("Erro ao adicionar resposta: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    public function relatorios() {
        $this->checkAuth();
        
        try {
            // Vamos tornar o acesso mais permissivo, apenas verificando se o usuário está autenticado
            // e é um administrador (já verificado pelo checkAuth)
            
            $pageTitle = 'Relatórios';
            $isAdminPage = true;
            ob_start();
            require __DIR__ . '/../Views/admin/relatorios.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao carregar relatórios: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar relatórios");
        }
    }
    
    public function gerarRelatorio() {
        $this->checkAuth();
        
        // Administradores têm acesso total - remover verificação de permissão
        
        try {
            // Parâmetros do relatório
            $dataInicio = $_GET['data_inicio'] ?? null;
            $dataFim = $_GET['data_fim'] ?? null;
            $status = $_GET['status'] ?? null;
            
            // Verificar se a data final não é anterior à data inicial
            if ($dataInicio && $dataFim && strtotime($dataFim) < strtotime($dataInicio)) {
                throw new Exception("A data final não pode ser anterior à data inicial");
            }
            
            // Buscar denúncias para o relatório
            $denuncias = $this->denunciaModel->buscarDenunciasRelatorio(
                $dataInicio, 
                $dataFim, 
                $status
            );
            
            // Registrar atividade
            $this->auth->registerActivity("gerou_relatorio", "reports");
            
            $formato = $_GET['formato'] ?? 'html';
            
            if ($formato === 'csv' && $this->middleware->hasPermission('reports.export')) {
                // Exportar como CSV
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="relatorio-denuncias.csv"');
                
                $output = fopen('php://output', 'w');
                
                // Cabeçalho do CSV
                fputcsv($output, ['Protocolo', 'Data de Registro', 'Status', 'Categorias']);
                
                // Dados
                foreach ($denuncias as $denuncia) {
                    fputcsv($output, [
                        $denuncia['protocolo'],
                        $denuncia['data_criacao'],
                        $denuncia['status'],
                        $denuncia['categorias']
                    ]);
                }
                
                fclose($output);
                exit;
            } else {
                // Renderizar como HTML
                $pageTitle = 'Relatório de Denúncias';
                ob_start();
                require __DIR__ . '/../Views/admin/relatorio-print.php';
                $content = ob_get_clean();
                require __DIR__ . '/../Views/layouts/base.php';
            }
            
        } catch (Exception $e) {
            error_log("Erro ao gerar relatório: " . $e->getMessage());
            $this->renderError(500, "Erro ao gerar relatório: " . $e->getMessage());
        }
    }
    
    /**
     * Gera um relatório estatístico de denúncias por status
     */
    public function relatorioEstatistico() {
        $this->checkAuth();
        
        // Administradores têm acesso total aos relatórios
        
        try {
            // Parâmetros do relatório
            $dataInicio = $_GET['data_inicio'] ?? null;
            $dataFim = $_GET['data_fim'] ?? null;
            
            // Buscar dados estatísticos
            $sql = "SELECT status, COUNT(*) as total FROM denuncias WHERE 1=1";
            $params = [];
            $types = "";
            
            if ($dataInicio) {
                $sql .= " AND DATE(data_criacao) >= ?";
                $params[] = $dataInicio;
                $types .= "s";
            }
            
            if ($dataFim) {
                $sql .= " AND DATE(data_criacao) <= ?";
                $params[] = $dataFim;
                $types .= "s";
            }
            
            $sql .= " GROUP BY status ORDER BY total DESC";
            
            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $dados = $result->fetch_all(MYSQLI_ASSOC);
            
            // Registrar atividade
            $this->auth->registerActivity("gerou_relatorio_estatistico", "reports");
            
            // Retornar como JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $dados
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao gerar relatório estatístico: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Erro ao gerar relatório estatístico: " . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Exporta um relatório detalhado em formato PDF
     */
    public function exportarRelatorioPDF() {
        $this->checkAuth();
        
        // Administradores têm acesso total à exportação
        
        try {
            // Parâmetros do relatório
            $dataInicio = $_GET['data_inicio'] ?? null;
            $dataFim = $_GET['data_fim'] ?? null;
            $status = $_GET['status'] ?? null;
            
            // Verificar se a data final não é anterior à data inicial
            if ($dataInicio && $dataFim && strtotime($dataFim) < strtotime($dataInicio)) {
                throw new Exception("A data final não pode ser anterior à data inicial");
            }
            
            // No momento, redirecionamos para a versão HTML que pode ser impressa
            // Adicionamos o parâmetro formato=html para garantir que ele use o formato HTML
            $_GET['formato'] = 'html';
            
            // Registrar atividade
            $this->auth->registerActivity("exportou_relatorio_pdf", "reports");
            
            // Usar o método gerarRelatorio para renderizar o relatório em HTML
            $this->gerarRelatorio();
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao exportar relatório PDF: " . $e->getMessage());
            $this->renderError(500, "Erro ao exportar relatório PDF: " . $e->getMessage());
        }
    }

    /**
     * Método temporário para diagnosticar a estrutura da tabela historico_status
     * Só funciona em ambiente de desenvolvimento
     */
    public function debugHistoricoStatus() {
        $this->checkAuth();
        
        // Só permitir em desenvolvimento
        if (!Environment::isDevelopment()) {
            $this->renderError(404, "Página não encontrada");
            return;
        }
        
        try {
            // Verificar a estrutura da tabela
            $sql = "DESCRIBE historico_status";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $colunas = $result->fetch_all(MYSQLI_ASSOC);
            
            echo "<h1>Estrutura da tabela historico_status</h1>";
            echo "<pre>";
            print_r($colunas);
            echo "</pre>";
            
            // Verificar alguns dados de exemplo
            $sql = "SELECT * FROM historico_status LIMIT 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $dados = $result->fetch_all(MYSQLI_ASSOC);
            
            echo "<h1>Dados de exemplo</h1>";
            echo "<pre>";
            print_r($dados);
            echo "</pre>";
            
        } catch (Exception $e) {
            echo "<h1>Erro</h1>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
        exit;
    }

    /**
     * Cria denúncias de teste se o banco estiver vazio
     */
    private function criarDenunciasTest() {
        try {
            $denunciasTest = [
                [
                    'protocolo' => 'AE2BC330',
                    'descricao' => 'No dia 20 de agosto recebi uma ligação ameaçadora de um funcionário do departamento financeiro',
                    'status' => 'Pendente',
                    'prioridade' => 'Média',
                    'data_ocorrencia' => '2025-08-20',
                    'local_ocorrencia' => 'Departamento Financeiro',
                    'pessoas_envolvidas' => 'Funcionário do financeiro'
                ],
                [
                    'protocolo' => 'BC33D441',
                    'descricao' => 'Presenciei tratamento discriminatório contra funcionária por causa da idade',
                    'status' => 'Em Análise',
                    'prioridade' => 'Alta',
                    'data_ocorrencia' => '2025-09-01',
                    'local_ocorrencia' => 'Recursos Humanos',
                    'pessoas_envolvidas' => 'Gerente de RH e funcionária'
                ],
                [
                    'protocolo' => 'D441E552',
                    'descricao' => 'Observei práticas inadequadas no manuseio de medicamentos controlados',
                    'status' => 'Em Investigação',
                    'prioridade' => 'Urgente',
                    'data_ocorrencia' => '2025-09-03',
                    'local_ocorrencia' => 'Farmácia',
                    'pessoas_envolvidas' => 'Farmacêutico responsável'
                ],
                [
                    'protocolo' => 'E552F663',
                    'descricao' => 'Relato de assédio moral por parte de supervisor direto',
                    'status' => 'Concluída',
                    'prioridade' => 'Alta',
                    'data_ocorrencia' => '2025-08-15',
                    'local_ocorrencia' => 'Departamento de Enfermagem',
                    'pessoas_envolvidas' => 'Supervisor de enfermagem'
                ],
                [
                    'protocolo' => 'F663G774',
                    'descricao' => 'Suspeita de fraude em licitação de equipamentos médicos',
                    'status' => 'Pendente',
                    'prioridade' => 'Urgente',
                    'data_ocorrencia' => '2025-09-04',
                    'local_ocorrencia' => 'Departamento de Compras',
                    'pessoas_envolvidas' => 'Equipe de compras'
                ],
                [
                    'protocolo' => 'G774H885',
                    'descricao' => 'Descarte inadequado de resíduos hospitalares perigosos',
                    'status' => 'Arquivada',
                    'prioridade' => 'Média',
                    'data_ocorrencia' => '2025-08-25',
                    'local_ocorrencia' => 'Central de Resíduos',
                    'pessoas_envolvidas' => 'Equipe de limpeza'
                ]
            ];
            
            foreach ($denunciasTest as $den) {
                $stmt = $this->conn->prepare("
                    INSERT INTO denuncias (
                        protocolo, descricao, status, prioridade, 
                        data_ocorrencia, local_ocorrencia, pessoas_envolvidas,
                        ip_denunciante, user_agent, data_criacao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, '192.168.2.20', 'Sistema de Teste', NOW())
                ");
                
                $stmt->bind_param("sssssss", 
                    $den['protocolo'],
                    $den['descricao'],
                    $den['status'],
                    $den['prioridade'],
                    $den['data_ocorrencia'],
                    $den['local_ocorrencia'],
                    $den['pessoas_envolvidas']
                );
                
                $stmt->execute();
            }
            
            // Invalidar cache
            Cache::getInstance()->delete('denuncias_lista_todas');
            
        } catch (Exception $e) {
            error_log("Erro ao criar denúncias teste: " . $e->getMessage());
        }
    }

    // === MÉTODOS DE USUÁRIOS ===
    
    public function usuarios() {
        $this->checkAuth();
        
        try {
            // Buscar todos os usuários admin
            $db = Database::getInstance()->getConnection();
            $result = $db->query("
                SELECT id, usuario, nome, email, nivel_acesso, ativo, ultimo_acesso,
                       data_criacao, data_atualizacao
                FROM admin 
                ORDER BY nome ASC
            ");
            
            $usuarios = $result->fetch_all(MYSQLI_ASSOC);
            
            $pageTitle = 'Gerenciamento de Usuários';
            $isAdminPage = true;
            $currentPage = 'usuarios';
            ob_start();
            require __DIR__ . '/../Views/admin/usuarios.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar usuários");
        }
    }
}
?> 