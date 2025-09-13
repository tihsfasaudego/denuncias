<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/User.php';

class AuthController {
    private $auth;
    private $userModel;

    public function __construct() {
        session_start();
        $this->auth = Auth::getInstance();
        $this->userModel = new User();
    }

    /**
     * Exibir formulário de login
     */
    public function login() {
        // Se já estiver logado, redireciona para dashboard
        if (Auth::check()) {
            header('Location: /admin/dashboard');
            exit;
        }
        
        $pageTitle = 'Login';
        ob_start();
        require __DIR__ . '/../Views/admin/login.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
    }

    /**
     * Processar autenticação do usuário
     */
    public function authenticate() {
        try {
            $usuario = $_POST['usuario'] ?? '';
            $senha = $_POST['senha'] ?? '';
            
            if (empty($usuario) || empty($senha)) {
                throw new Exception("Usuário e senha são obrigatórios");
            }
            
            $success = $this->auth->login($usuario, $senha);
            
            if (!$success) {
                throw new Exception("Usuário ou senha inválidos");
            }
            
            // Registrar atividade
            $this->auth->registerActivity("login", "auth");
            
            // Redirecionar para o dashboard
            header('Location: /admin/dashboard');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro de autenticação: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * Realizar logout do usuário
     */
    public function logout() {
        if (Auth::check()) {
            $this->auth->registerActivity("logout", "auth");
        }
        
        Auth::logout();
        header('Location: /admin/login');
        exit;
    }

    /**
     * Exibir formulário de redefinição de senha
     */
    public function forgotPassword() {
        // Se já estiver logado, redireciona para dashboard
        if (Auth::check()) {
            header('Location: /admin/dashboard');
            exit;
        }
        
        $pageTitle = 'Recuperar Senha';
        ob_start();
        require __DIR__ . '/../Views/admin/esqueci-senha.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
    }

    /**
     * Processar solicitação de redefinição de senha
     */
    public function requestPasswordReset() {
        try {
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                throw new Exception("Email é obrigatório");
            }
            
            // Verificar se o email existe
            $user = $this->userModel->getByEmail($email);
            
            if (!$user) {
                throw new Exception("Email não encontrado");
            }
            
            // Gerar token e salvar no banco
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $success = $this->userModel->salvarTokenReset($user['id'], $token, $expira);
            
            if (!$success) {
                throw new Exception("Erro ao processar solicitação");
            }
            
            // Enviar email (simulação)
            $resetUrl = "http://{$_SERVER['HTTP_HOST']}/admin/redefinir-senha?token=$token";
            error_log("Email de redefinição para {$email}: $resetUrl");
            
            $_SESSION['success'] = "Instruções para redefinição de senha foram enviadas para seu email";
            header('Location: /admin/login');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao solicitar redefinição de senha: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/esqueci-senha');
            exit;
        }
    }

    /**
     * Exibir formulário de redefinição de senha com token
     */
    public function resetPasswordForm() {
        // Se já estiver logado, redireciona para dashboard
        if (Auth::check()) {
            header('Location: /admin/dashboard');
            exit;
        }
        
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $_SESSION['error'] = "Token inválido";
            header('Location: /admin/login');
            exit;
        }
        
        // Verificar se o token é válido
        $user = $this->userModel->getUserByResetToken($token);
        
        if (!$user) {
            $_SESSION['error'] = "Token inválido ou expirado";
            header('Location: /admin/login');
            exit;
        }
        
        $pageTitle = 'Redefinir Senha';
        ob_start();
        require __DIR__ . '/../Views/admin/redefinir-senha.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layouts/base.php';
    }

    /**
     * Processar redefinição de senha
     */
    public function resetPassword() {
        try {
            $token = $_POST['token'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $confirmaSenha = $_POST['confirma_senha'] ?? '';
            
            if (empty($token) || empty($senha) || empty($confirmaSenha)) {
                throw new Exception("Todos os campos são obrigatórios");
            }
            
            if ($senha !== $confirmaSenha) {
                throw new Exception("As senhas não coincidem");
            }
            
            // Verificar se o token é válido
            $user = $this->userModel->getUserByResetToken($token);
            
            if (!$user) {
                throw new Exception("Token inválido ou expirado");
            }
            
            // Atualizar senha
            $success = $this->userModel->atualizarSenha($user['id'], $senha);
            
            if (!$success) {
                throw new Exception("Erro ao atualizar senha");
            }
            
            // Limpar token
            $this->userModel->limparTokenReset($user['id']);
            
            $_SESSION['success'] = "Senha redefinida com sucesso! Faça login com sua nova senha.";
            header('Location: /admin/login');
            exit;
            
        } catch (Exception $e) {
            error_log("Erro ao redefinir senha: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/redefinir-senha?token=' . urlencode($token ?? ''));
            exit;
        }
    }
}
?> 