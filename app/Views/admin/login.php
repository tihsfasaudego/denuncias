<?php 
// Início do conteúdo da página

// Verificação de tabelas RBAC
$rbacError = false;
try {
    $db = Database::getInstance()->getConnection();
    $tables = ['roles', 'permissions', 'role_permission', 'user_role'];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if (!($result && $result->num_rows > 0)) {
            $rbacError = true;
            break;
        }
    }
} catch (Exception $e) {
    $rbacError = true;
}
?>

<!-- Aviso de erro do RBAC -->
<?php if ($rbacError): ?>
<div class="alert alert-danger">
    <h4 class="alert-heading">Configuração de Permissões Necessária</h4>
    <p>O sistema de permissões precisa ser configurado antes de continuar.</p>
    <hr>
    <p class="mb-0">
        <a href="/admin-setup-permissions.php" class="btn btn-danger">Configurar Permissões</a>
    </p>
</div>
<?php endif; ?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h3 class="mb-0">
                        <i class="fas fa-user-shield me-2"></i> Área Administrativa
                    </h3>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> 
                            <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> 
                            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/admin/authenticate" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="usuario" class="form-label">
                                <i class="fas fa-user me-2"></i>Usuário
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg" 
                                id="usuario" 
                                name="usuario" 
                                required 
                                autofocus
                                autocomplete="off"
                                aria-describedby="usuarioHelp"
                                value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                            >
                            <div id="usuarioHelp" class="form-text">Digite seu nome de usuário.</div>
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label">
                                <i class="fas fa-lock me-2"></i>Senha
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control form-control-lg" 
                                    id="senha" 
                                    name="senha" 
                                    required
                                    autocomplete="off"
                                    aria-describedby="senhaHelp"
                                >
                                <button 
                                    class="btn btn-outline-secondary" 
                                    type="button" 
                                    id="togglePassword"
                                    aria-label="Mostrar senha"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="senhaHelp" class="form-text">Digite sua senha.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Entrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-center mt-3">
                <a href="/" class="text-muted"><i class="fas fa-arrow-left me-1"></i> Voltar ao início</a>
            </p>
        </div>
    </div>
</div>

<script>
    document.getElementById("togglePassword").addEventListener("click", function () {
        const senhaInput = document.getElementById("senha");
        const icon = this.querySelector("i");
        
        if (senhaInput.type === "password") {
            senhaInput.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            senhaInput.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    });
</script>

<?php
// Fim do conteúdo da página
