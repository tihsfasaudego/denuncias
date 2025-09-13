<?php
/**
 * Visualização Detalhada de Denúncia
 */
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="/admin/denuncias" class="text-decoration-none">Denúncias</a>
            </li>
            <li class="breadcrumb-item active">
                <?= htmlspecialchars($denuncia['protocolo']) ?>
            </li>
        </ol>
    </nav>

    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="hsfa-title text-primary">
                <i class="fas fa-file-alt me-2"></i>
                Denúncia <?= htmlspecialchars($denuncia['protocolo']) ?>
            </h2>
            <div class="d-flex align-items-center gap-3 mt-2">
                <?php
                $statusClass = [
                    'Pendente' => 'warning',
                    'Em Análise' => 'info',
                    'Em Investigação' => 'primary',
                    'Concluída' => 'success',
                    'Arquivada' => 'secondary'
                ];
                $class = $statusClass[$denuncia['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $class ?> fs-6"><?= htmlspecialchars($denuncia['status']) ?></span>
                
                <?php if (!empty($denuncia['prioridade'])): ?>
                <?php
                $prioridadeClass = [
                    'Baixa' => 'success',
                    'Média' => 'warning',
                    'Alta' => 'danger',
                    'Urgente' => 'danger'
                ];
                $pClass = $prioridadeClass[$denuncia['prioridade']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $pClass ?> fs-6"><?= htmlspecialchars($denuncia['prioridade']) ?></span>
                <?php endif; ?>
                
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    <?= date('d/m/Y H:i', strtotime($denuncia['data_criacao'])) ?>
                </small>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <button class="btn btn-primary" onclick="alterarStatus()">
                    <i class="fas fa-edit me-1"></i>
                    Alterar Status
                </button>
                <button class="btn btn-outline-success" onclick="atribuirResponsavel()">
                    <i class="fas fa-user-plus me-1"></i>
                    Atribuir
                </button>
                <div class="btn-group">
                    <button class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-1"></i>
                        Ações
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" onclick="imprimirDenuncia()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </a></li>
                        <li><a class="dropdown-item" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" onclick="excluirDenuncia()">
                            <i class="fas fa-trash me-2"></i>Excluir
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Principal -->
        <div class="col-lg-8">
            <!-- Detalhes da Denúncia -->
            <div class="card hsfa-card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        Detalhes da Denúncia
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($denuncia['categorias'])): ?>
                    <div class="mb-3">
                        <strong class="text-muted">Categorias:</strong>
                        <div class="mt-1">
                            <?php foreach (explode(',', $denuncia['categorias']) as $categoria): ?>
                                <span class="badge bg-secondary me-1"><?= htmlspecialchars(trim($categoria)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($denuncia['data_ocorrencia'])): ?>
                    <div class="mb-3">
                        <strong class="text-muted">Data da Ocorrência:</strong>
                        <p class="mb-0"><?= date('d/m/Y', strtotime($denuncia['data_ocorrencia'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($denuncia['local_ocorrencia'])): ?>
                    <div class="mb-3">
                        <strong class="text-muted">Local da Ocorrência:</strong>
                        <p class="mb-0"><?= htmlspecialchars($denuncia['local_ocorrencia']) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($denuncia['pessoas_envolvidas'])): ?>
                    <div class="mb-3">
                        <strong class="text-muted">Pessoas Envolvidas:</strong>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($denuncia['pessoas_envolvidas'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <strong class="text-muted">Descrição:</strong>
                        <div class="mt-2 p-3 bg-light rounded">
                            <?= nl2br(htmlspecialchars($denuncia['descricao'])) ?>
                        </div>
                    </div>

                    <?php if (!empty($denuncia['anexo'])): ?>
                    <div class="mb-3">
                        <strong class="text-muted">Anexo:</strong>
                        <div class="mt-2">
                            <a href="/uploads/<?= htmlspecialchars($denuncia['anexo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-paperclip me-1"></i>
                                Visualizar Anexo
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Área de Respostas/Comentários -->
            <div class="card hsfa-card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-comments me-2"></i>
                        Respostas e Acompanhamento
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Formulário para Nova Resposta -->
                    <form id="formResposta" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                        <div class="mb-3">
                            <label class="form-label">Adicionar Resposta/Comentário</label>
                            <textarea class="form-control" rows="4" placeholder="Digite sua resposta ou comentário..." required></textarea>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="notificarDenunciante">
                                    <label class="form-check-label" for="notificarDenunciante">
                                        Notificar denunciante
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-reply me-1"></i>
                                Enviar Resposta
                            </button>
                        </div>
                    </form>

                    <!-- Lista de Respostas -->
                    <div id="listaRespostas">
                        <!-- As respostas serão carregadas aqui via JavaScript -->
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-comments fs-1 mb-2 d-block"></i>
                            Nenhuma resposta ainda
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Informações do Sistema -->
            <div class="card hsfa-card mb-4">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0 text-primary">
                        <i class="fas fa-cog me-2"></i>
                        Informações do Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong class="text-muted d-block">IP do Denunciante:</strong>
                        <code><?= htmlspecialchars($denuncia['ip_denunciante'] ?? 'Não registrado') ?></code>
                    </div>
                    
                    <div class="mb-3">
                        <strong class="text-muted d-block">User Agent:</strong>
                        <small class="text-muted"><?= htmlspecialchars(substr($denuncia['user_agent'] ?? 'Não registrado', 0, 100)) ?></small>
                    </div>

                    <div class="mb-3">
                        <strong class="text-muted d-block">Responsável Atual:</strong>
                        <?php if (!empty($denuncia['responsavel'])): ?>
                            <span class="badge bg-info"><?= htmlspecialchars($denuncia['responsavel']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Não atribuído</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <strong class="text-muted d-block">Última Atualização:</strong>
                        <small><?= date('d/m/Y H:i', strtotime($denuncia['data_atualizacao'])) ?></small>
                    </div>

                    <?php if (!empty($denuncia['data_conclusao'])): ?>
                    <div class="mb-3">
                        <strong class="text-muted d-block">Data de Conclusão:</strong>
                        <small><?= date('d/m/Y H:i', strtotime($denuncia['data_conclusao'])) ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="card hsfa-card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0 text-primary">
                        <i class="fas fa-bolt me-2"></i>
                        Ações Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-warning btn-sm" onclick="alterarPrioridade()">
                            <i class="fas fa-flag me-1"></i>
                            Alterar Prioridade
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="duplicarDenuncia()">
                            <i class="fas fa-copy me-1"></i>
                            Duplicar Denúncia
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="gerarRelatorio()">
                            <i class="fas fa-file-alt me-1"></i>
                            Gerar Relatório
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Alterar Status -->
<div class="modal fade" id="modalAlterarStatus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alterar Status da Denúncia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAlterarStatus">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Novo Status</label>
                        <select class="form-select" name="status" required>
                            <option value="">Selecione...</option>
                            <option value="Pendente" <?= $denuncia['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Em Análise" <?= $denuncia['status'] === 'Em Análise' ? 'selected' : '' ?>>Em Análise</option>
                            <option value="Em Investigação" <?= $denuncia['status'] === 'Em Investigação' ? 'selected' : '' ?>>Em Investigação</option>
                            <option value="Concluída" <?= $denuncia['status'] === 'Concluída' ? 'selected' : '' ?>>Concluída</option>
                            <option value="Arquivada" <?= $denuncia['status'] === 'Arquivada' ? 'selected' : '' ?>>Arquivada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações (opcional)</label>
                        <textarea class="form-control" name="observacao" rows="3" placeholder="Motivo da alteração ou observações..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alteração</button>
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

.breadcrumb-item + .breadcrumb-item::before {
    color: #6c757d;
}

.breadcrumb-item a {
    color: #003a4d;
}

.card-header {
    border-bottom: 1px solid #dee2e6;
}

.badge {
    font-size: 0.875em;
}
</style>

<script>
function alterarStatus() {
    const modal = new bootstrap.Modal(document.getElementById('modalAlterarStatus'));
    modal.show();
}

function atribuirResponsavel() {
    // Implementar modal ou redirect para atribuição
    console.log('Atribuir responsável');
}

function alterarPrioridade() {
    // Implementar modal para alterar prioridade
    console.log('Alterar prioridade');
}

function excluirDenuncia() {
    if (confirm('Tem certeza que deseja excluir esta denúncia? Esta ação não pode ser desfeita.')) {
        // AJAX para exclusão
        console.log('Excluir denúncia');
    }
}

function imprimirDenuncia() {
    window.print();
}

function exportarPDF() {
    window.open(`/admin/relatorios/exportar-pdf?denuncia_id=<?= $denuncia['id'] ?>`, '_blank');
}

function duplicarDenuncia() {
    if (confirm('Deseja criar uma nova denúncia baseada nesta?')) {
        console.log('Duplicar denúncia');
    }
}

function gerarRelatorio() {
    window.open(`/admin/relatorios/individual?denuncia_id=<?= $denuncia['id'] ?>`, '_blank');
}

// Formulário de alteração de status
document.getElementById('formAlterarStatus').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('denuncia_id', <?= $denuncia['id'] ?>);

    // Desabilitar botão
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';

    fetch('/admin/denuncia/<?= $denuncia['id'] ?>/status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAlterarStatus'));
            modal.hide();

            // Atualizar status na página
            const statusBadge = document.querySelector('.badge.bg-warning, .badge.bg-info, .badge.bg-primary, .badge.bg-success, .badge.bg-secondary');
            if (statusBadge) {
                statusBadge.textContent = data.status || '<?= $denuncia['status'] ?>';
                statusBadge.className = 'badge bg-' + getStatusClass(data.status || '<?= $denuncia['status'] ?>');
            }

            // Mostrar sucesso
            showToast('Status atualizado com sucesso!', 'success');
        } else {
            throw new Error(data.message || 'Erro desconhecido');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showToast('Erro ao alterar status: ' + error.message, 'error');
    })
    .finally(() => {
        // Reabilitar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Formulário de resposta
document.getElementById('formResposta').addEventListener('submit', function(e) {
    e.preventDefault();

    const textarea = this.querySelector('textarea');
    const resposta = textarea.value.trim();

    if (!resposta) {
        showToast('Digite uma resposta', 'warning');
        return;
    }

    if (resposta.length > 5000) {
        showToast('Resposta muito longa (máximo 5000 caracteres)', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('resposta', resposta);
    formData.append('notificar', document.getElementById('notificarDenunciante').checked);

    // Desabilitar botão
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';

    fetch('/admin/denuncia/<?= $denuncia['id'] ?>/responder', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            textarea.value = '';
            document.getElementById('notificarDenunciante').checked = false;

            // Atualizar status se foi alterado
            if (data.status) {
                const statusBadge = document.querySelector('.badge.bg-warning, .badge.bg-info, .badge.bg-primary, .badge.bg-success, .badge.bg-secondary');
                if (statusBadge) {
                    statusBadge.textContent = data.status;
                    statusBadge.className = 'badge bg-' + getStatusClass(data.status);
                }
            }

            // Recarregar lista de respostas
            carregarRespostas();

            showToast('Resposta enviada com sucesso!', 'success');
        } else {
            throw new Error(data.message || 'Erro desconhecido');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showToast('Erro ao enviar resposta: ' + error.message, 'error');
    })
    .finally(() => {
        // Reabilitar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function carregarRespostas() {
    // Implementar carregamento das respostas via AJAX
    console.log('Carregando respostas...');
    // TODO: Implementar carregamento das respostas
}

// Função auxiliar para obter classe do status
function getStatusClass(status) {
    const statusClasses = {
        'Pendente': 'warning',
        'Em Análise': 'info',
        'Em Investigação': 'primary',
        'Concluída': 'success',
        'Arquivada': 'secondary'
    };
    return statusClasses[status] || 'secondary';
}

// Função para mostrar toast de notificação
function showToast(message, type = 'info') {
    // Criar elemento de toast se não existir
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    // Criar toast
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Inicializar e mostrar toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 4000
    });
    bsToast.show();

    // Remover toast após ocultar
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Carregar respostas ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    carregarRespostas();
});
</script>
