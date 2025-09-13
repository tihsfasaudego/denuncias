<?php
$pageTitle = 'Novo Usuário';
$isAdminPage = true;
$currentPage = 'usuarios';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><i class="fas fa-user-plus text-primary me-2"></i>Novo Usuário</h2>
                    <p class="text-muted mb-0">Crie uma nova conta de usuário no sistema</p>
                </div>
                <div>
                    <a href="/admin/usuarios" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Informações do Usuário
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form id="usuarioForm" method="POST" action="/admin/usuarios/salvar" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">
                                        <i class="fas fa-user me-1"></i>Nome Completo *
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="nome" 
                                        name="nome" 
                                        required 
                                        placeholder="Digite o nome completo"
                                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                                    >
                                    <div class="invalid-feedback">
                                        Por favor, informe o nome completo.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>E-mail *
                                    </label>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email" 
                                        name="email" 
                                        required 
                                        placeholder="usuario@exemplo.com"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    >
                                    <div class="invalid-feedback">
                                        Por favor, informe um e-mail válido.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuario" class="form-label">
                                        <i class="fas fa-at me-1"></i>Nome de Usuário *
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="usuario" 
                                        name="usuario" 
                                        required 
                                        placeholder="Digite o nome de usuário"
                                        value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                                        pattern="[a-zA-Z0-9_]{3,20}"
                                        title="Apenas letras, números e underscore. Entre 3 e 20 caracteres."
                                    >
                                    <div class="invalid-feedback">
                                        Nome de usuário deve ter entre 3 e 20 caracteres (apenas letras, números e underscore).
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Apenas letras, números e underscore. Entre 3 e 20 caracteres.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="roles" class="form-label">
                                        <i class="fas fa-user-tag me-1"></i>Papéis *
                                    </label>
                                    <select class="form-select" id="roles" name="roles[]" multiple required>
                                        <?php foreach($roles as $role): ?>
                                            <option value="<?= $role['id'] ?>" 
                                                <?= (isset($_POST['roles']) && in_array($role['id'], $_POST['roles'])) ? 'selected' : '' ?>
                                            >
                                                <?= htmlspecialchars($role['nome']) ?>
                                                <?= $role['descricao'] ? ' - ' . htmlspecialchars($role['descricao']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor, selecione pelo menos um papel.
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Segure Ctrl para selecionar múltiplos papéis.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="senha" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Senha *
                                    </label>
                                    <div class="input-group">
                                        <input 
                                            type="password" 
                                            class="form-control" 
                                            id="senha" 
                                            name="senha" 
                                            required 
                                            placeholder="Digite a senha"
                                            minlength="6"
                                        >
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha')">
                                            <i class="fas fa-eye" id="senha-icon"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        A senha deve ter pelo menos 6 caracteres.
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Mínimo de 6 caracteres.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirma_senha" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Confirmar Senha *
                                    </label>
                                    <div class="input-group">
                                        <input 
                                            type="password" 
                                            class="form-control" 
                                            id="confirma_senha" 
                                            name="confirma_senha" 
                                            required 
                                            placeholder="Confirme a senha"
                                            minlength="6"
                                        >
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirma_senha')">
                                            <i class="fas fa-eye" id="confirma_senha-icon"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        As senhas devem ser iguais.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="ativo" 
                                        name="ativo" 
                                        value="1" 
                                        checked
                                    >
                                    <label class="form-check-label" for="ativo">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Usuário ativo
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Usuários inativos não conseguem fazer login no sistema.
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="/admin/usuarios" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Criar Usuário
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df, #224abe) !important;
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.form-select:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #224abe;
    border-color: #224abe;
}

.alert {
    border: none;
    border-radius: 0.5rem;
}

.card {
    border: none;
    border-radius: 0.75rem;
}

.card-header {
    border-radius: 0.75rem 0.75rem 0 0 !important;
    border: none;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.invalid-feedback {
    display: block;
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #28a745;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('usuarioForm');
    const senha = document.getElementById('senha');
    const confirmaSenha = document.getElementById('confirma_senha');
    
    // Validação em tempo real
    senha.addEventListener('input', function() {
        validatePasswordMatch();
    });
    
    confirmaSenha.addEventListener('input', function() {
        validatePasswordMatch();
    });
    
    // Validação do formulário
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        // Validação adicional de senhas
        if (!validatePasswordMatch()) {
            return;
        }
        
        // Validação de papéis
        const roles = document.getElementById('roles');
        if (roles.selectedOptions.length === 0) {
            roles.setCustomValidity('Selecione pelo menos um papel');
            roles.reportValidity();
            return;
        } else {
            roles.setCustomValidity('');
        }
        
        // Se chegou até aqui, enviar o formulário
        form.submit();
    });
    
    function validatePasswordMatch() {
        if (senha.value !== confirmaSenha.value) {
            confirmaSenha.setCustomValidity('As senhas não coincidem');
            confirmaSenha.reportValidity();
            return false;
        } else {
            confirmaSenha.setCustomValidity('');
            return true;
        }
    }
    
    // Validação de nome de usuário em tempo real
    const usuario = document.getElementById('usuario');
    usuario.addEventListener('input', function() {
        const value = this.value;
        const pattern = /^[a-zA-Z0-9_]{3,20}$/;
        
        if (value && !pattern.test(value)) {
            this.setCustomValidity('Apenas letras, números e underscore. Entre 3 e 20 caracteres.');
        } else {
            this.setCustomValidity('');
        }
    });
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
