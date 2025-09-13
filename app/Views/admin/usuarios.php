<?php
/**
 * Gerenciamento de Usuários - Layout Atualizado
 */
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho da Página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="hsfa-title text-primary">
                    <i class="fas fa-users me-2"></i>
                    Gerenciamento de Usuários
                </h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="atualizarListagem()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Atualizar
                    </button>
                    <button class="btn btn-success" onclick="novoUsuario()">
                        <i class="fas fa-user-plus me-1"></i>
                        Novo Usuário
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Usuários -->
    <div class="card hsfa-card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-primary">
                    <i class="fas fa-list me-2"></i>
                    Lista de Usuários
                </h5>
                <small class="text-muted">
                    Total: <?= count($usuarios ?? []) ?> usuário(s)
                </small>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="usuariosTable">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Nome</th>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Último Acesso</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-users fs-1 mb-3 d-block"></i>
                                <h5>Nenhum usuário encontrado</h5>
                                <p>Clique em "Novo Usuário" para começar.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr class="usuario-row" data-id="<?= $usuario['id'] ?>">
                                <td class="px-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initial me-2">
                                            <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($usuario['nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($usuario['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="text-primary"><?= htmlspecialchars($usuario['usuario']) ?></code>
                                </td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($usuario['email']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($usuario['email']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $nivelClass = [
                                        'admin' => 'badge-danger',
                                        'supervisor' => 'badge-primary', 
                                        'analista' => 'badge-success'
                                    ];
                                    $class = $nivelClass[$usuario['nivel_acesso']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?= $class ?>"><?= ucfirst($usuario['nivel_acesso']) ?></span>
                                </td>
                                <td>
                                    <?php if ($usuario['ativo']): ?>
                                        <span class="badge badge--done">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge--archived">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ultimo_acesso']): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Nunca acessou</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn--ghost" onclick="editarUsuario(<?= $usuario['id'] ?>)" title="Editar usuário">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn--ghost" onclick="resetarSenha(<?= $usuario['id'] ?>)" title="Resetar senha">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($usuario['ativo']): ?>
                                            <button class="btn btn--ghost text-warning" onclick="toggleStatus(<?= $usuario['id'] ?>, 0)" title="Bloquear usuário" data-confirm="Deseja bloquear este usuário?">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn--ghost text-success" onclick="toggleStatus(<?= $usuario['id'] ?>, 1)" title="Desbloquear usuário" data-confirm="Deseja ativar este usuário?">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($usuario['id'] != ($_SESSION['admin']['id'] ?? 0)): ?>
                                            <button class="btn btn--ghost text-danger" onclick="excluirUsuario(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nome']) ?>')" title="Excluir usuário" data-confirm="Esta ação não pode ser desfeita!">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo/Editar Usuário -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    <span id="modalTitulo">Novo Usuário</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUsuario">
                <div class="modal-body">
                    <input type="hidden" id="usuarioId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Usuário *</label>
                                <input type="text" class="form-control" name="usuario" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nível de Acesso *</label>
                                <select class="form-select" name="nivel_acesso" required>
                                    <option value="">Selecione...</option>
                                    <option value="admin">Administrador</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="analista">Analista</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="senhaFields">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Senha *</label>
                                <input type="password" class="form-control" name="senha" required>
                                <div class="form-text">Mínimo 8 caracteres</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Confirmar Senha *</label>
                                <input type="password" class="form-control" name="senha_confirmacao" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ativo" id="usuarioAtivo" checked>
                        <label class="form-check-label" for="usuarioAtivo">
                            Usuário ativo
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Resetar Senha -->
<div class="modal fade" id="modalResetarSenha" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>
                    Resetar Senha
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formResetarSenha">
                <div class="modal-body">
                    <input type="hidden" id="usuarioIdSenha" name="usuario_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        O usuário receberá a nova senha por email e será obrigatório alterá-la no primeiro login.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nova Senha *</label>
                        <input type="password" class="form-control" name="nova_senha" required>
                        <div class="form-text">Mínimo 8 caracteres</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar Nova Senha *</label>
                        <input type="password" class="form-control" name="confirmar_senha" required>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notificar_usuario" id="notificarUsuario" checked>
                        <label class="form-check-label" for="notificarUsuario">
                            Notificar usuário por email
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Resetar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.hsfa-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-radius: 10px;
}

.hsfa-title {
    color: #003a4d !important;
    font-weight: 600;
}

.text-primary {
    color: #003a4d !important;
}

.btn-primary {
    background-color: #003a4d;
    border-color: #003a4d;
}

.btn-primary:hover {
    background-color: #005066;
    border-color: #005066;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    color: #003a4d;
    font-weight: 600;
}

.usuario-row:hover {
    background-color: rgba(0, 58, 77, 0.02);
}

.avatar-initial {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #003a4d, #005066);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
}

.badge-danger {
    background-color: #dc3545 !important;
}

.badge-primary {
    background-color: #0d6efd !important;
}

.badge-success {
    background-color: #198754 !important;
}

.badge-secondary {
    background-color: #6c757d !important;
}

.badge--done {
    background-color: #28a745;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
}

.badge--archived {
    background-color: #6c757d;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
}

.btn--ghost {
    background: transparent;
    border: 1px solid #dee2e6;
    color: #6c757d;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn--ghost:hover {
    background: #f8f9fa;
    border-color: #003a4d;
    color: #003a4d;
}

.btn--ghost.text-warning:hover {
    color: #ffc107 !important;
    border-color: #ffc107;
}

.btn--ghost.text-success:hover {
    color: #28a745 !important;
    border-color: #28a745;
}

.btn--ghost.text-danger:hover {
    color: #dc3545 !important;
    border-color: #dc3545;
}
</style>

<script>
function atualizarListagem() {
    window.location.reload();
}

function novoUsuario() {
    document.getElementById('modalTitulo').textContent = 'Novo Usuário';
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioId').value = '';
    document.getElementById('senhaFields').style.display = 'block';
    document.querySelector('[name="senha"]').required = true;
    document.querySelector('[name="senha_confirmacao"]').required = true;
    
    const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    modal.show();
}

function editarUsuario(id) {
    document.getElementById('modalTitulo').textContent = 'Editar Usuário';
    document.getElementById('usuarioId').value = id;
    document.getElementById('senhaFields').style.display = 'none';
    document.querySelector('[name="senha"]').required = false;
    document.querySelector('[name="senha_confirmacao"]').required = false;
    
    // Carregar dados do usuário
    fetch(`/admin/usuarios/${id}/dados`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const form = document.getElementById('formUsuario');
                form.querySelector('[name="nome"]').value = data.usuario.nome;
                form.querySelector('[name="usuario"]').value = data.usuario.usuario;
                form.querySelector('[name="email"]').value = data.usuario.email;
                form.querySelector('[name="nivel_acesso"]').value = data.usuario.nivel_acesso;
                form.querySelector('[name="ativo"]').checked = data.usuario.ativo == 1;
                
                const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
                modal.show();
            } else {
                alert('Erro ao carregar dados do usuário');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar usuário');
        });
}

function resetarSenha(id) {
    document.getElementById('usuarioIdSenha').value = id;
    const modal = new bootstrap.Modal(document.getElementById('modalResetarSenha'));
    modal.show();
}

function toggleStatus(id, novoStatus) {
    const acao = novoStatus == 1 ? 'ativar' : 'bloquear';
    
    if (confirm(`Deseja realmente ${acao} este usuário?`)) {
        fetch(`/admin/usuarios/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: novoStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Usuário ${acao === 'ativar' ? 'ativado' : 'bloqueado'} com sucesso!`);
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao alterar status do usuário');
        });
    }
}

function excluirUsuario(id, nome) {
    if (confirm(`Deseja realmente excluir o usuário "${nome}"?\n\nEsta ação não pode ser desfeita!`)) {
        fetch(`/admin/usuarios/${id}/excluir`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Usuário excluído com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir usuário');
        });
    }
}

// Form handlers
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const usuarioId = formData.get('id');
    
    // Validar senhas se for novo usuário
    if (!usuarioId) {
        const senha = formData.get('senha');
        const confirmacao = formData.get('senha_confirmacao');
        
        if (senha !== confirmacao) {
            alert('As senhas não conferem!');
            return;
        }
        
        if (senha.length < 8) {
            alert('A senha deve ter pelo menos 8 caracteres!');
            return;
        }
    }
    
    const url = usuarioId ? `/admin/usuarios/${usuarioId}/atualizar` : '/admin/usuarios/criar';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(usuarioId ? 'Usuário atualizado com sucesso!' : 'Usuário criado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar usuário');
    });
});

document.getElementById('formResetarSenha').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    const novaSenha = formData.get('nova_senha');
    const confirmarSenha = formData.get('confirmar_senha');
    
    if (novaSenha !== confirmarSenha) {
        alert('As senhas não conferem!');
        return;
    }
    
    if (novaSenha.length < 8) {
        alert('A senha deve ter pelo menos 8 caracteres!');
        return;
    }
    
    const usuarioId = formData.get('usuario_id');
    
    fetch(`/admin/usuarios/${usuarioId}/resetar-senha`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Senha resetada com sucesso!');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalResetarSenha'));
            modal.hide();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao resetar senha');
    });
});
</script> 