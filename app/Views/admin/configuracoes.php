<?php
// Definir que esta é uma página de administração
$isAdminPage = true;
$currentPage = 'configuracoes';

$pageTitle = 'Configurações do Sistema';
?>

<div class="container py-4">
    <div class="row">
        <!-- Seção do Logo -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-image me-2"></i>Logo do Hospital
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if (file_exists('uploads/logo.png')): ?>
                            <img src="/uploads/logo.png" alt="Logo atual" class="img-fluid mb-3" style="max-height: 100px;">
                            <p class="text-muted">Logo atual</p>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Nenhum logo carregado
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="/admin/configuracoes/logo" method="POST" enctype="multipart/form-data" class="upload-form">
                        <div class="mb-3">
                            <label for="logo" class="form-label">Selecionar novo logo</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="logo" 
                                   name="logo" 
                                   accept="image/png,image/jpeg"
                                   required>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Formatos aceitos: PNG, JPG (máx. 2MB)
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Atualizar Logo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Seção de Alteração de Senha -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2"></i>Alterar Senha
                    </h5>
                </div>
                <div class="card-body">
                    <form id="formAlterarSenha" onsubmit="alterarSenha(event)">
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="senha_atual" 
                                       name="senha_atual" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('senha_atual')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="nova_senha" 
                                       name="nova_senha" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('nova_senha')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Mínimo 8 caracteres, incluindo maiúsculas, minúsculas e números
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmar_senha" 
                                       name="confirmar_senha" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('confirmar_senha')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Nova Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.currentTarget.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

async function alterarSenha(event) {
    event.preventDefault();
    
    const form = event.target;
    const novaSenha = form.nova_senha.value;
    const confirmarSenha = form.confirmar_senha.value;
    
    if (novaSenha !== confirmarSenha) {
        alert('As senhas não coincidem!');
        return;
    }
    
    try {
        const response = await fetch('/admin/senha/alterar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                senha_atual: form.senha_atual.value,
                nova_senha: novaSenha,
                confirmar_senha: confirmarSenha
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Senha alterada com sucesso!');
            form.reset();
        } else {
            alert(data.message || 'Erro ao alterar senha');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao processar a requisição');
    }
}

// Validação em tempo real das senhas
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = this.value;
    
    if (novaSenha && confirmarSenha) {
        if (novaSenha !== confirmarSenha) {
            this.setCustomValidity('As senhas não coincidem');
        } else {
            this.setCustomValidity('');
        }
    }
});

// Preview da imagem antes do upload
document.getElementById('logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('O arquivo é muito grande! Máximo permitido: 2MB');
            this.value = '';
            return;
        }
        
        if (!['image/png', 'image/jpeg'].includes(file.type)) {
            alert('Formato de arquivo inválido! Use apenas PNG ou JPG');
            this.value = '';
            return;
        }
    }
});
</script>

<style>
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

.upload-form {
    max-width: 100%;
}

.input-group .btn {
    z-index: 0;
}

.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

.btn-outline-secondary:focus {
    box-shadow: none;
}

@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
}
</style> 