<?php
// Definir que esta é uma página de administração
$isAdminPage = true;
$currentPage = 'denuncias';

// Garantir que $denuncia existe
if (!isset($denuncia)) {
    header('Location: /admin/dashboard');
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                Detalhes da Denúncia
            </h5>
            <div class="btn-group">
                <button class="btn btn-outline-secondary" onclick="window.history.back()">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </button>
                <button class="btn btn-primary" onclick="imprimirDenuncia()">
                    <i class="fas fa-print me-2"></i>
                    Imprimir
                </button>
                <button class="btn btn-danger" onclick="confirmarExclusao('<?php echo htmlspecialchars($denuncia['protocolo']); ?>')">
                    <i class="fas fa-trash me-2"></i>
                    Excluir
                </button>
            </div>
        </div>
        
        <div class="card-body" id="conteudo-impressao">
            <!-- Cabeçalho do Relatório -->
            <div class="text-center mb-4 cabecalho-relatorio">
                <h4><?php echo APP_NAME; ?></h4>
                <h5>Relatório de Denúncia</h5>
                <hr>
            </div>

            <!-- Informações Principais -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Protocolo:</strong> <?php echo htmlspecialchars($denuncia['protocolo']); ?></p>
                    <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i', strtotime($denuncia['data_criacao'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?php
                            switch($denuncia['status']) {
                                case 'Pendente': echo 'bg-warning'; break;
                                case 'Em Análise': echo 'bg-info'; break;
                                case 'Concluída': echo 'bg-success'; break;
                                default: echo 'bg-secondary';
                            }
                        ?>">
                            <?php echo htmlspecialchars($denuncia['status']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Categorias:</strong> <?php echo htmlspecialchars($denuncia['categorias'] ?? 'N/A'); ?></p>
                    <p><strong>Última Atualização:</strong> 
                        <?php echo $denuncia['data_atualizacao'] ? date('d/m/Y H:i', strtotime($denuncia['data_atualizacao'])) : 'N/A'; ?>
                    </p>
                </div>
            </div>

            <!-- Descrição da Denúncia -->
            <div class="mb-4">
                <h6 class="fw-bold">Descrição da Denúncia:</h6>
                <div class="p-3 rounded descricao-box">
                    <div class="text-white">
                        <?php echo nl2br(htmlspecialchars($denuncia['descricao'])); ?>
                    </div>
                </div>
            </div>

            <!-- Anexos -->
            <?php if (!empty($denuncia['anexo'])): ?>
            <div class="mb-4">
                <h6 class="fw-bold">Anexos:</h6>
                <div class="p-3 bg-light rounded">
                    <a href="/uploads/<?php echo htmlspecialchars($denuncia['anexo']); ?>" target="_blank">
                        <i class="fas fa-paperclip me-2"></i>
                        <?php echo htmlspecialchars($denuncia['anexo']); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Histórico de Respostas -->
            <?php if (!empty($denuncia['respostas'])): ?>
            <div class="mb-4" id="historico-respostas">
                <h6 class="fw-bold">Histórico de Respostas:</h6>
                <?php foreach ($denuncia['respostas'] as $resposta): ?>
                <div class="p-3 bg-light rounded mb-2 resposta-item">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d/m/Y H:i', strtotime($resposta['data_criacao'])); ?>
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($resposta['usuario']); ?>
                        </small>
                    </div>
                    <?php echo nl2br(htmlspecialchars($resposta['resposta'])); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Adicionar modal de confirmação -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta denúncia? Esta ação não pode ser desfeita.</p>
                <p class="text-danger"><strong>Protocolo:</strong> <span id="protocoloExclusao"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="excluirDenuncia()">
                    <i class="fas fa-trash me-2"></i>
                    Confirmar Exclusão
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    /* Esconder elementos que não devem ser impressos */
    .btn-group, 
    .navbar,
    .sidebar,
    .modal,
    .btn-close {
        display: none !important;
    }
    
    /* Garantir que todo o conteúdo seja visível */
    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
    }
    
    .card-header {
        background-color: white !important;
        border-bottom: 2px solid #000 !important;
    }
    
    .card-body {
        padding: 20px !important;
    }
    
    /* Ajustes para o cabeçalho do relatório */
    .cabecalho-relatorio {
        margin-top: 20px;
        margin-bottom: 30px;
    }
    
    /* Ajustes para badges de status */
    .badge {
        border: 1px solid #000 !important;
        padding: 5px 10px !important;
        color: #000 !important;
        background-color: transparent !important;
    }
    
    /* Ajustes para a descrição */
    .descricao-box {
        background-color: #ffffff !important;
        border: 1px solid #000000 !important;
        margin-bottom: 20px !important;
    }
    
    .descricao-box .text-white {
        color: #000000 !important;
    }
    
    /* Ajustes para anexos e respostas */
    .bg-light {
        background-color: #ffffff !important;
        border: 1px solid #ddd !important;
    }
    
    /* Ajustes para links */
    a {
        text-decoration: none !important;
        color: #000 !important;
    }
    
    /* Ajustes para textos e ícones */
    .text-muted,
    .fas {
        color: #000 !important;
    }
    
    /* Configurações da página */
    @page {
        margin: 2cm;
        size: A4;
    }
    
    /* Evitar quebras de página indesejadas */
    .mb-4 {
        page-break-inside: avoid;
    }
    
    /* Garantir que todo o conteúdo seja impresso */
    html, body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    
    /* Mostrar URLs de anexos entre parênteses */
    a[href^="/uploads/"]:after {
        content: " (" attr(href) ")";
        font-size: 12px;
    }
    
    /* Ajustes para o histórico de respostas */
    #historico-respostas {
        page-break-before: auto;
    }
    
    .resposta-item {
        page-break-inside: avoid;
        margin-bottom: 15px !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<script>
let protocoloParaExcluir = '';

function confirmarExclusao(protocolo) {
    protocoloParaExcluir = protocolo;
    document.getElementById('protocoloExclusao').textContent = protocolo;
    new bootstrap.Modal(document.getElementById('modalConfirmarExclusao')).show();
}

function excluirDenuncia() {
    const btnExcluir = document.querySelector('#modalConfirmarExclusao .btn-danger');
    const btnText = btnExcluir.innerHTML;
    btnExcluir.disabled = true;
    btnExcluir.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Excluindo...';

    fetch('/admin/denuncia/excluir', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            protocolo: protocoloParaExcluir
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Erro ao excluir denúncia');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message || 'Denúncia excluída com sucesso!');
            window.location.href = '/admin/dashboard';
        } else {
            throw new Error(data.message || 'Erro ao excluir denúncia');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert(error.message || 'Erro ao excluir denúncia. Por favor, tente novamente.');
    })
    .finally(() => {
        btnExcluir.disabled = false;
        btnExcluir.innerHTML = btnText;
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmarExclusao')).hide();
    });
}

function imprimirDenuncia() {
    window.print();
}
</script> 