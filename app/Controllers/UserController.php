<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Role.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/AuthMiddleware.php';

class UserController {
    private $userModel;
    private $roleModel;
    private $auth;
    private $middleware;

    public function __construct() {
        $this->userModel = new User();
        $this->roleModel = new Role();
        $this->auth = Auth::getInstance();
        $this->middleware = new AuthMiddleware();
    }

    private function checkAuth() {
        // Verificar se está autenticado
        if (!Auth::check()) {
            header('Location: /admin/login');
            exit;
        }
        
        // Não verificaremos permissões específicas por enquanto para facilitar o acesso
    }

    public function index() {
        $this->checkAuth();
        
        try {
            // Listar todos os usuários
            $usuarios = $this->userModel->listarTodos();
            
            $pageTitle = 'Gerenciamento de Usuários';
            $isAdminPage = true; // Garantir que a variável esteja definida
            $currentPage = 'usuarios'; // Definir a página atual para o menu
            ob_start();
            require __DIR__ . '/../Views/admin/usuarios.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar usuários");
        }
    }

    public function create() {
        $this->checkAuth();
        
        // Verificar permissão para adicionar usuários
        $this->middleware->redirectIfNoPermission('users.add');
        
        try {
            // Buscar todos os papéis disponíveis
            $roles = $this->roleModel->getAll();
            
            $pageTitle = 'Novo Usuário';
            $isAdminPage = true; // Garantir que a variável esteja definida
            $currentPage = 'usuarios'; // Definir a página atual para o menu
            ob_start();
            require __DIR__ . '/../Views/admin/usuario-form.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao carregar formulário de novo usuário: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar formulário");
        }
    }

    public function store() {
        $this->checkAuth();
        
        // Verificar permissão para adicionar usuários
        $this->middleware->redirectIfNoPermission('users.add');
        
        try {
            // Validar dados do formulário
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $usuario = $_POST['usuario'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $confirmaSenha = $_POST['confirma_senha'] ?? '';
            $roles = $_POST['roles'] ?? [];
            
            if (empty($nome) || empty($email) || empty($usuario) || empty($senha)) {
                throw new Exception("Todos os campos são obrigatórios");
            }
            
            if ($senha !== $confirmaSenha) {
                throw new Exception("As senhas não coincidem");
            }
            
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido");
            }
            
            // Verificar se o usuário já existe
            $existingUser = $this->userModel->getByUsername($usuario);
            if ($existingUser) {
                throw new Exception("Nome de usuário já existe");
            }
            
            // Criar o usuário
            $userId = $this->userModel->createUser($nome, $email, $usuario, $senha, $roles);
            
            if (!$userId) {
                throw new Exception("Erro ao criar usuário");
            }
            
            // Registrar atividade
            $this->auth->registerActivity("criou_usuario", "user", $userId);
            
            $_SESSION['success'] = "Usuário criado com sucesso!";
            header('Location: /admin/usuarios');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/usuarios/novo');
            exit;
        }
    }

    public function edit($id) {
        $this->checkAuth();
        
        // Verificar permissão para editar usuários
        $this->middleware->redirectIfNoPermission('users.edit');
        
        try {
            // Buscar dados do usuário
            $usuario = $this->userModel->getById($id);
            
            if (!$usuario) {
                throw new Exception("Usuário não encontrado");
            }
            
            // Buscar papéis do usuário
            $userRoles = $this->userModel->getUserRoles($id);
            $userRoleIds = array_map(function($role) {
                return $role['id'];
            }, $userRoles);
            
            // Buscar todos os papéis disponíveis
            $roles = $this->roleModel->getAll();
            
            $pageTitle = 'Editar Usuário';
            $isAdminPage = true; // Garantir que a variável esteja definida
            $currentPage = 'usuarios'; // Definir a página atual para o menu
            ob_start();
            require __DIR__ . '/../Views/admin/usuario-form.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao carregar formulário de edição: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/usuarios');
            exit;
        }
    }

    public function update($id) {
        $this->checkAuth();
        
        // Verificar permissão para editar usuários
        $this->middleware->redirectIfNoPermission('users.edit');
        
        try {
            // Validar dados do formulário
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $usuario = $_POST['usuario'] ?? '';
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $roles = $_POST['roles'] ?? [];
            
            if (empty($nome) || empty($email) || empty($usuario)) {
                throw new Exception("Nome, email e usuário são obrigatórios");
            }
            
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido");
            }
            
            // Verificar se o nome de usuário já existe em outro usuário
            $existingUser = $this->userModel->getByUsername($usuario);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception("Nome de usuário já existe");
            }
            
            // Atualizar usuário
            $success = $this->userModel->atualizarUsuario($id, $nome, $email, $usuario, $ativo, $roles);
            
            if (!$success) {
                throw new Exception("Erro ao atualizar usuário");
            }
            
            // Verificar se senha foi informada para alteração
            $senha = $_POST['senha'] ?? '';
            $confirmaSenha = $_POST['confirma_senha'] ?? '';
            
            if (!empty($senha)) {
                if ($senha !== $confirmaSenha) {
                    throw new Exception("As senhas não coincidem");
                }
                
                // Validar requisitos de senha (mínimo 8 caracteres, letras e números)
                if (strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
                    throw new Exception("A senha deve ter pelo menos 8 caracteres, incluindo letras e números");
                }
                
                $this->userModel->atualizarSenha($id, $senha);
            }
            
            // Registrar atividade
            $this->auth->registerActivity("atualizou_usuario", "user", $id);
            
            $_SESSION['success'] = "Usuário atualizado com sucesso!";
            header('Location: /admin/usuarios');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/usuarios/editar/' . $id);
            exit;
        }
    }

    public function delete($id) {
        $this->checkAuth();
        
        // Verificar permissão para excluir usuários
        if (!$this->middleware->hasPermission('users.delete')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado'
            ]);
            exit;
        }
        
        try {
            // Não permitir excluir o próprio usuário
            if ($id == Auth::id()) {
                throw new Exception("Não é possível excluir seu próprio usuário");
            }
            
            $success = $this->userModel->excluirUsuario($id);
            
            if (!$success) {
                throw new Exception("Erro ao excluir usuário");
            }
            
            // Registrar atividade
            $this->auth->registerActivity("excluiu_usuario", "user", $id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Usuário excluído com sucesso'
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao excluir usuário: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function profile() {
        $this->middleware->redirectIfNotAuthenticated();
        
        try {
            // Buscar dados do usuário logado
            $usuario = $this->userModel->getById(Auth::id());
            
            $pageTitle = 'Meu Perfil';
            $isAdminPage = true; // Garantir que a variável esteja definida
            ob_start();
            require __DIR__ . '/../Views/admin/perfil.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro ao carregar perfil: " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar perfil");
        }
    }

    public function updateProfile() {
        $this->middleware->redirectIfNotAuthenticated();
        
        try {
            $id = Auth::id();
            
            // Validar dados do formulário
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            
            if (empty($nome) || empty($email)) {
                throw new Exception("Nome e email são obrigatórios");
            }
            
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email inválido");
            }
            
            // Buscar dados atuais
            $usuario = $this->userModel->getById($id);
            
            // Atualizar usuário mantendo o mesmo username
            $success = $this->userModel->atualizarUsuario($id, $nome, $email, $usuario['usuario'], 1);
            
            if (!$success) {
                throw new Exception("Erro ao atualizar perfil");
            }
            
            // Verificar se senha foi informada para alteração
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmaSenha = $_POST['confirma_senha'] ?? '';
            
            if (!empty($novaSenha)) {
                if (empty($senhaAtual)) {
                    throw new Exception("A senha atual é obrigatória para alteração de senha");
                }
                
                if ($novaSenha !== $confirmaSenha) {
                    throw new Exception("As senhas não coincidem");
                }
                
                // Validar senha atual
                if (!$this->userModel->verificarSenha($id, $senhaAtual)) {
                    throw new Exception("Senha atual incorreta");
                }
                
                // Validar requisitos de senha
                if (strlen($novaSenha) < 8 || !preg_match('/[A-Za-z]/', $novaSenha) || !preg_match('/\d/', $novaSenha)) {
                    throw new Exception("A nova senha deve ter pelo menos 8 caracteres, incluindo letras e números");
                }
                
                $this->userModel->atualizarSenha($id, $novaSenha);
            }
            
            // Registrar atividade
            $this->auth->registerActivity("atualizou_perfil", "user", $id);
            
            $_SESSION['success'] = "Perfil atualizado com sucesso!";
            header('Location: /admin/perfil');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar perfil: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/perfil');
            exit;
        }
    }

    private function renderError($code, $message) {
        http_response_code($code);
        $pageTitle = 'Erro';
        $error = $message;
        $isAdminPage = true; // Garantir que é reconhecido como página administrativa
        ob_start();
        require __DIR__ . '/../Views/errors/error.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
        exit;
    }
    
    public function updateStatus($id) {
        $this->checkAuth();
        
        // Verificar permissão para editar usuários
        if (!$this->middleware->hasPermission('users.edit')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado'
            ]);
            exit;
        }
        
        try {
            // Pegar dados do corpo da requisição
            $data = json_decode(file_get_contents('php://input'), true);
            $status = $data['status'] ?? null;
            
            if ($status === null) {
                throw new Exception("Status não informado");
            }
            
            // Não permitir desativar o próprio usuário
            if ($id == Auth::id()) {
                throw new Exception("Não é possível alterar o status do seu próprio usuário");
            }
            
            // Converter para booleano
            $ativo = (bool)$status;
            
            // Buscar dados atuais
            $usuario = $this->userModel->getById($id);
            if (!$usuario) {
                throw new Exception("Usuário não encontrado");
            }
            
            // Atualizar o status
            $success = $this->userModel->atualizarStatus($id, $ativo);
            
            if (!$success) {
                throw new Exception("Erro ao atualizar status do usuário");
            }
            
            // Registrar atividade
            $this->auth->registerActivity(
                $ativo ? "ativou_usuario" : "desativou_usuario", 
                "user", 
                $id
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Status do usuário atualizado com sucesso'
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar status do usuário: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
?> 