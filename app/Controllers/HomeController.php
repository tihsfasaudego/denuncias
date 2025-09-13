<?php

class HomeController {
    public function __construct() {
        // Verificar se a sessão já foi iniciada antes de iniciar
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index() {
        try {
            // Gerar novo token CSRF se não existir
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            $pageTitle = 'Canal de Denúncias - Hospital São Francisco de Assis';
            ob_start();
            require __DIR__ . '/../Views/home/index.php';
            $content = ob_get_clean();
            require __DIR__ . '/../Views/layouts/base.php';
        } catch (Exception $e) {
            error_log("Erro no HomeController::index - " . $e->getMessage());
            $this->renderError(500, "Erro ao carregar página inicial");
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
} 