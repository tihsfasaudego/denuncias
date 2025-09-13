<?php
/**
 * Dashboard Administrativo - Visão Geral
 */
?>

<!-- Cabeçalho do Dashboard -->
<div class="d-flex justify-between items-center mb-6" data-animate="fadeIn">
    <div>
        <h1 class="text-2xl font-bold text-primary m-0">Dashboard</h1>
        <p class="text-secondary m-0">Visão geral do sistema de denúncias</p>
    </div>
    <div class="d-flex gap-3">
        <button class="hsfa-btn hsfa-btn-outline-primary" onclick="atualizarDados()" data-tooltip="Atualizar dados">
            <i class="fas fa-sync-alt"></i>
            Atualizar
        </button>
        <button class="hsfa-btn hsfa-btn-success" onclick="exportarRelatorio()" data-tooltip="Exportar relatório">
            <i class="fas fa-download"></i>
            Exportar
        </button>
                </div>
            </div>

<!-- Cards de Estatísticas -->
<div class="grid grid-cols-4 gap-6 mb-8" data-animate="slideUp">
    <div class="stat-card" data-animate-delay="0">
        <div class="stat-card-icon bg-warning text-dark">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-card-number"><?= count($denunciasPendentes ?? []) ?></div>
        <div class="stat-card-label">Pendentes</div>
        <div class="stat-card-change positive">
            <i class="fas fa-arrow-up"></i>
            +15% esta semana
        </div>
                </div>
    
    <div class="stat-card" data-animate-delay="100">
        <div class="stat-card-icon bg-info text-white">
            <i class="fas fa-search"></i>
        </div>
        <div class="stat-card-number"><?= count($denunciasEmAnalise ?? []) ?></div>
        <div class="stat-card-label">Em Análise</div>
        <div class="stat-card-change negative">
            <i class="fas fa-arrow-down"></i>
            -5% esta semana
        </div>
    </div>

    <div class="stat-card" data-animate-delay="200">
        <div class="stat-card-icon bg-success text-white">
            <i class="fas fa-check-circle"></i>
                </div>
        <div class="stat-card-number"><?= count($denunciasConcluidas ?? []) ?></div>
        <div class="stat-card-label">Concluídas</div>
        <div class="stat-card-change positive">
            <i class="fas fa-arrow-up"></i>
            +8% esta semana
        </div>
    </div>

    <div class="stat-card" data-animate-delay="300">
        <div class="stat-card-icon bg-primary text-white">
            <i class="fas fa-chart-line"></i>
            </div>
        <div class="stat-card-number"><?php 
            $total = count($denuncias ?? []);
            $concluidas = count($denunciasConcluidas ?? []);
            $taxa = $total > 0 ? round(($concluidas / $total) * 100) : 0;
            echo $taxa;
        ?>%</div>
        <div class="stat-card-label">Taxa de Resolução</div>
        <div class="stat-card-change positive">
            <i class="fas fa-arrow-up"></i>
            +2% esta semana
        </div>
                </div>
                </div>

<!-- Grid de Conteúdo -->
<div class="grid grid-cols-3 gap-6">
    <!-- Denúncias Recentes -->
    <div class="col-span-2">
        <div class="hsfa-card" data-animate="slideLeft" data-animate-delay="400">
            <div class="hsfa-card-header">
                <h2 class="hsfa-card-title">
                    <i class="fas fa-file-alt"></i>
                    Denúncias Recentes
                </h2>
                <p class="hsfa-card-subtitle">Últimas denúncias registradas no sistema</p>
            </div>
            <div class="hsfa-card-body p-0">
                <div class="hsfa-table-container">
                    <table class="hsfa-table">
                    <thead>
                        <tr>
                            <th>Protocolo</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                            <th>Data</th>
                                <th class="table-cell-center">Ações</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php 
                            $denunciasRecentes = array_slice($denuncias ?? [], 0, 5);
                            foreach ($denunciasRecentes as $denuncia): 
                                $statusClass = [
                                    'Pendente' => 'status-pendente',
                                    'Em Análise' => 'status-analise',
                                    'Em Investigação' => 'status-investigacao', 
                                    'Concluída' => 'status-concluida',
                                    'Arquivada' => 'status-arquivada'
                                ][$denuncia['status']] ?? 'status-pendente';
                                
                                $prioridadeClass = [
                                    'Baixa' => 'prioridade-baixa',
                                    'Média' => 'prioridade-media',
                                    'Alta' => 'prioridade-alta',
                                    'Urgente' => 'prioridade-urgente'
                                ][$denuncia['prioridade'] ?? 'Média'] ?? 'prioridade-media';
                            ?>
                            <tr>
                                <td>
                                    <code class="text-primary"><?= htmlspecialchars($denuncia['protocolo']) ?></code>
                                </td>
                                <td>
                                    <span class="hsfa-badge <?= $statusClass ?>"><?= htmlspecialchars($denuncia['status']) ?></span>
                                </td>
                                <td>
                                    <span class="hsfa-badge <?= $prioridadeClass ?>"><?= htmlspecialchars($denuncia['prioridade'] ?? 'Média') ?></span>
                                </td>
                                <td>
                                    <small class="text-secondary"><?= date('d/m/Y H:i', strtotime($denuncia['data_criacao'])) ?></small>
                                </td>
                                <td class="table-cell-center">
                                    <div class="d-flex gap-2 justify-center">
                                        <button class="hsfa-btn hsfa-btn-sm hsfa-btn-outline-primary" onclick="visualizarDenuncia(<?= $denuncia['id'] ?>)" data-tooltip="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="hsfa-btn hsfa-btn-sm hsfa-btn-outline-secondary" onclick="alterarStatusModal(<?= $denuncia['id'] ?>)" data-tooltip="Alterar Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            
                            <?php if (empty($denunciasRecentes)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-inbox fs-1 mb-3 d-block text-muted"></i>
                                    <p class="text-muted">Nenhuma denúncia encontrada</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="hsfa-card-footer">
                <a href="/admin/denuncias" class="hsfa-btn hsfa-btn-primary">
                    <i class="fas fa-list"></i>
                    Ver Todas as Denúncias
                </a>
            </div>
        </div>
            </div>
            
    <!-- Sidebar com Informações -->
    <div class="col-span-1">
        <!-- Ações Rápidas -->
        <div class="hsfa-card mb-6" data-animate="slideRight" data-animate-delay="500">
            <div class="hsfa-card-header">
                <h3 class="hsfa-card-title">
                    <i class="fas fa-bolt"></i>
                    Ações Rápidas
                </h3>
                </div>
            <div class="hsfa-card-body">
                <div class="d-flex flex-column gap-3">
                    <button class="hsfa-btn hsfa-btn-outline-primary justify-start" onclick="novaDenuncia()" data-loading-text="Redirecionando...">
                        <i class="fas fa-plus"></i>
                        Nova Denúncia
                    </button>
                    <button class="hsfa-btn hsfa-btn-outline-secondary justify-start" onclick="gerarRelatorio()">
                        <i class="fas fa-chart-bar"></i>
                        Gerar Relatório
                    </button>
                    <button class="hsfa-btn hsfa-btn-outline-info justify-start" onclick="configuracoes()">
                        <i class="fas fa-cog"></i>
                        Configurações
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Alertas do Sistema -->
        <div class="hsfa-card" data-animate="slideRight" data-animate-delay="600">
            <div class="hsfa-card-header">
                <h3 class="hsfa-card-title">
                    <i class="fas fa-bell"></i>
                    Alertas do Sistema
                </h3>
            </div>
            <div class="hsfa-card-body">
                <div class="d-flex flex-column gap-4">
                    <div class="hsfa-alert hsfa-alert-warning" role="alert">
                        <i class="hsfa-alert-icon fas fa-exclamation-triangle"></i>
                        <div class="hsfa-alert-content">
                            <h4 class="hsfa-alert-title">Denúncias Pendentes</h4>
                            <p class="hsfa-alert-message">5 denúncias aguardam análise há mais de 3 dias.</p>
                        </div>
                    </div>
                    
                    <div class="hsfa-alert hsfa-alert-info" role="alert">
                        <i class="hsfa-alert-icon fas fa-info-circle"></i>
                        <div class="hsfa-alert-content">
                            <h4 class="hsfa-alert-title">Backup Realizado</h4>
                            <p class="hsfa-alert-message">Último backup realizado hoje às 02:00.</p>
                        </div>
                    </div>
                    
                    <div class="hsfa-alert hsfa-alert-success" role="alert">
                        <i class="hsfa-alert-icon fas fa-check-circle"></i>
                        <div class="hsfa-alert-content">
                            <h4 class="hsfa-alert-title">Sistema Atualizado</h4>
                            <p class="hsfa-alert-message">Todas as funcionalidades estão operando normalmente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function atualizarDados() {
    const btn = event.target.closest('button');
    window.HSFA.loading.setButtonLoading(btn);
    
    // Simular carregamento
    setTimeout(() => {
        window.HSFA.loading.removeButtonLoading(btn);
        window.HSFA.toast.success('Dados atualizados com sucesso!');
        
        // Animar contadores
        animateCounters();
    }, 2000);
}

function exportarRelatorio() {
    window.HSFA.toast.info('Gerando relatório...', 3000);
    
    setTimeout(() => {
        window.HSFA.toast.success('Relatório gerado! Download iniciado.');
        // Simular download
    }, 2000);
}

function novaDenuncia() {
    const btn = event.target.closest('button');
    window.HSFA.loading.setButtonLoading(btn);
    
    setTimeout(() => {
        window.location.href = '/admin/denuncias/nova';
    }, 1000);
}

function gerarRelatorio() {
    window.location.href = '/admin/relatorios';
}

function configuracoes() {
    window.location.href = '/admin/configuracoes';
}

function animateCounters() {
    document.querySelectorAll('.stat-card-number').forEach(counter => {
        const target = parseInt(counter.textContent.replace(/\D/g, ''));
        let current = 0;
        const increment = target / 20;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target + (counter.textContent.includes('%') ? '%' : '');
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current) + (counter.textContent.includes('%') ? '%' : '');
            }
        }, 50);
    });
}

// Funções do modal (mesmas da lista.php)
function visualizarDenuncia(id) {
    console.log('Dashboard - Tentando carregar denúncia ID:', id);
    
    fetch(`/admin/denuncia/${id}/dados`)
        .then(response => {
            console.log('Dashboard - Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dashboard - Dados recebidos:', data);
            if (data.success) {
                preencherModalVisualizacao(data.denuncia);
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarDenuncia'));
                modal.show();
            } else {
                console.error('Dashboard - Erro do servidor:', data);
                alert('Erro ao carregar denúncia: ' + (data.message || 'Erro desconhecido'));
                if (data.debug) {
                    console.log('Dashboard - Debug info:', data.debug);
                }
            }
        })
        .catch(error => {
            console.error('Dashboard - Erro de rede/parsing:', error);
            alert('Erro ao carregar denúncia: ' + error.message);
        });
}

function alterarStatusModal(id) {
    document.getElementById('denunciaIdStatus').value = id;
    const modal = new bootstrap.Modal(document.getElementById('modalAlterarStatus'));
    modal.show();
}

function preencherModalVisualizacao(denuncia) {
    document.getElementById('modalProtocolo').textContent = denuncia.protocolo;
    document.getElementById('modalStatus').textContent = denuncia.status;
    document.getElementById('modalPrioridade').textContent = denuncia.prioridade || 'Não definida';
    document.getElementById('modalDataCriacao').textContent = new Date(denuncia.data_criacao).toLocaleString('pt-BR');
    document.getElementById('modalDescricao').textContent = denuncia.descricao;
    document.getElementById('modalDataOcorrencia').textContent = denuncia.data_ocorrencia ? new Date(denuncia.data_ocorrencia).toLocaleDateString('pt-BR') : 'Não informada';
    document.getElementById('modalLocal').textContent = denuncia.local_ocorrencia || 'Não informado';
    document.getElementById('modalPessoas').textContent = denuncia.pessoas_envolvidas || 'Não informado';
    document.getElementById('modalResponsavel').textContent = denuncia.responsavel || 'Não atribuído';
    document.getElementById('denunciaIdModal').value = denuncia.id;
    
    const statusBadge = document.getElementById('modalStatus');
    statusBadge.className = 'badge bg-' + getStatusClass(denuncia.status);
}

function getStatusClass(status) {
    const classes = {
        'Pendente': 'warning',
        'Em Análise': 'info',
        'Em Investigação': 'primary',
        'Concluída': 'success',
        'Arquivada': 'secondary'
    };
    return classes[status] || 'secondary';
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Toast de boas-vindas
    setTimeout(() => {
        window.HSFA.toast.success('Bem-vindo ao painel administrativo!', 3000);
    }, 1500);
    
    // Animar contadores após um tempo
    setTimeout(() => {
        animateCounters();
    }, 2000);
});
</script>

<!-- Modais do Dashboard (mesmos da lista.php) -->
<!-- Modal Visualizar Denúncia -->
<div class="modal fade" id="modalVisualizarDenuncia" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Visualizar Denúncia - <span id="modalProtocolo">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Informações Básicas</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Status:</strong>
                                    <span id="modalStatus" class="badge ms-2">-</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Prioridade:</strong>
                                    <span id="modalPrioridade">-</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Data de Criação:</strong>
                                    <span id="modalDataCriacao">-</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Data da Ocorrência:</strong>
                                    <span id="modalDataOcorrencia">-</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Local:</strong>
                                    <span id="modalLocal">-</span>
                                </div>
                                <div class="mb-0">
                                    <strong>Responsável:</strong>
                                    <span id="modalResponsavel">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>Descrição</h6>
                            </div>
                            <div class="card-body">
                                <div class="p-3 bg-light rounded" style="min-height: 200px; max-height: 300px; overflow-y: auto;">
                                    <span id="modalDescricao">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="fas fa-users me-2"></i>Pessoas Envolvidas</h6>
                            </div>
                            <div class="card-body">
                                <div class="p-3 bg-light rounded">
                                    <span id="modalPessoas">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="denunciaIdModal" value="">
                <button type="button" class="btn btn-outline-info" onclick="imprimirDenunciaModal()">
                    <i class="fas fa-print me-1"></i>Imprimir
                </button>
                <button type="button" class="btn btn-outline-success" onclick="alterarStatusModal(document.getElementById('denunciaIdModal').value)">
                    <i class="fas fa-edit me-1"></i>Alterar Status
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="alterarPrioridadeModal(document.getElementById('denunciaIdModal').value)">
                    <i class="fas fa-flag me-1"></i>Prioridade
                </button>
                <button type="button" class="btn btn-outline-info" onclick="atribuirResponsavelModal(document.getElementById('denunciaIdModal').value)">
                    <i class="fas fa-user-plus me-1"></i>Atribuir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Alterar Status -->
<div class="modal fade" id="modalAlterarStatus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Alterar Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAlterarStatusModal">
                <div class="modal-body">
                    <input type="hidden" id="denunciaIdStatus" name="denuncia_id">
                    <div class="mb-3">
                        <label class="form-label">Novo Status</label>
                        <select class="form-select" name="status" required>
                            <option value="">Selecione...</option>
                            <option value="Pendente">Pendente</option>
                            <option value="Em Análise">Em Análise</option>
                            <option value="Em Investigação">Em Investigação</option>
                            <option value="Concluída">Concluída</option>
                            <option value="Arquivada">Arquivada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacao" rows="3" placeholder="Motivo da alteração..."></textarea>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
// Função de impressão melhorada com layout profissional
function imprimirDenunciaModal() {
    const protocolo = document.getElementById('modalProtocolo').textContent;
    const status = document.getElementById('modalStatus').textContent;
    const prioridade = document.getElementById('modalPrioridade').textContent;
    const descricao = document.getElementById('modalDescricao').textContent;
    const dataOcorrencia = document.getElementById('modalDataOcorrencia').textContent;
    const dataCriacao = document.getElementById('modalDataCriacao').textContent;
    const local = document.getElementById('modalLocal').textContent;
    const pessoas = document.getElementById('modalPessoas').textContent;
    const responsavel = document.getElementById('modalResponsavel').textContent;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Denúncia ${protocolo} - HSFA</title>
            <style>
                @page { 
                    size: A4 portrait;
                    margin: 2cm 1.5cm;
                }
                body { 
                    font-family: 'Arial', sans-serif; 
                    margin: 0; 
                    padding: 0;
                    line-height: 1.5;
                    color: #333;
                    width: 210mm;
                    max-width: 210mm;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    padding-bottom: 20px;
                    border-bottom: 3px solid #003a4d;
                }
                .logo {
                    width: 120px;
                    height: auto;
                    margin-bottom: 10px;
                }
                .hospital-name {
                    font-size: 16px;
                    font-weight: bold;
                    color: #003a4d;
                    margin: 3px 0;
                }
                .document-title {
                    font-size: 20px;
                    font-weight: bold;
                    color: #003a4d;
                    margin: 10px 0 5px 0;
                }
                .confidencial {
                    background: #ff4444;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 15px;
                    font-weight: bold;
                    font-size: 10px;
                    display: inline-block;
                    margin: 8px 0;
                }
                .protocolo-box {
                    background: #f8f9fa;
                    border: 2px solid #003a4d;
                    padding: 12px;
                    text-align: center;
                    margin: 15px 0;
                    border-radius: 6px;
                }
                .protocolo-number {
                    font-size: 24px;
                    font-weight: bold;
                    color: #003a4d;
                    letter-spacing: 2px;
                }
                .field { 
                    margin-bottom: 15px; 
                    page-break-inside: avoid;
                }
                .field-label { 
                    font-weight: bold; 
                    color: #003a4d;
                    font-size: 12px;
                    margin-bottom: 4px;
                    display: block;
                }
                .field-content { 
                    background: #f8f9fa; 
                    border-radius: 4px; 
                    padding: 8px;
                    border-left: 3px solid #003a4d;
                    min-height: 16px;
                    font-size: 11px;
                }
                .status-badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-size: 10px;
                    font-weight: bold;
                    color: white;
                    background: #6c757d;
                }
                .status-pendente { background: #ffc107; color: #000; }
                .status-analise { background: #17a2b8; }
                .status-investigacao { background: #007bff; }
                .status-concluida { background: #28a745; }
                .status-arquivada { background: #6c757d; }
                .footer {
                    position: fixed;
                    bottom: 1cm;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    font-size: 72px;
                    color: rgba(0, 58, 77, 0.05);
                    font-weight: bold;
                    z-index: -1;
                    pointer-events: none;
                }
                .metadata {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="watermark">CONFIDENCIAL</div>
            
            <div class="header">
                <div class="hospital-name">HOSPITAL SÃO FRANCISCO DE ASSIS</div>
                <div class="hospital-name" style="font-size: 14px; font-weight: normal;">Canal de Denúncias - Ouvidoria</div>
                <div class="document-title">RELATÓRIO DE DENÚNCIA</div>
                <div class="confidencial">DOCUMENTO CONFIDENCIAL</div>
            </div>
            
            <div class="protocolo-box">
                <div style="font-size: 14px; margin-bottom: 5px;">Protocolo de Identificação</div>
                <div class="protocolo-number">${protocolo}</div>
            </div>
            
            <div class="metadata">
                <div class="field">
                    <div class="field-label">Status Atual:</div>
                    <div class="field-content">
                        <span class="status-badge status-${status.toLowerCase().replace(' ', '-')}">${status}</span>
                    </div>
                </div>
                <div class="field">
                    <div class="field-label">Prioridade:</div>
                    <div class="field-content">${prioridade}</div>
                </div>
            </div>
            
            <div class="metadata">
                <div class="field">
                    <div class="field-label">Data de Registro:</div>
                    <div class="field-content">${dataCriacao}</div>
                </div>
                <div class="field">
                    <div class="field-label">Data da Ocorrência:</div>
                    <div class="field-content">${dataOcorrencia}</div>
                </div>
            </div>
            
            <div class="field">
                <div class="field-label">Local da Ocorrência:</div>
                <div class="field-content">${local}</div>
            </div>
            
            <div class="field">
                <div class="field-label">Pessoas Envolvidas:</div>
                <div class="field-content">${pessoas}</div>
            </div>
            
            <div class="field">
                <div class="field-label">Descrição Completa:</div>
                <div class="field-content" style="min-height: 100px;">${descricao}</div>
            </div>
            
            <div class="field">
                <div class="field-label">Responsável pela Análise:</div>
                <div class="field-content">${responsavel}</div>
            </div>
            
            <div class="footer">
                <div>
                    <strong>HOSPITAL SÃO FRANCISCO DE ASSIS</strong><br>
                    Canal de Denúncias - Ouvidoria<br>
                    Este documento contém informações confidenciais e deve ser tratado com sigilo absoluto<br>
                    Gerado em: ${new Date().toLocaleString('pt-BR')} | Usuário: ${document.querySelector('.admin-user-name')?.textContent || 'Administrador'}
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Form handler para alterar status
document.getElementById('formAlterarStatusModal').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const denunciaId = formData.get('denuncia_id');
    
    console.log('Dashboard - Alterando status da denúncia ID:', denunciaId);
    console.log('Dashboard - Novo status:', formData.get('status'));
    
    fetch('/admin/denuncia/' + denunciaId + '/status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Dashboard - Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Dashboard - Resposta do servidor:', data);
        if (data.success) {
            alert('Status alterado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Dashboard - Erro ao alterar status:', error);
        alert('Erro ao alterar status: ' + error.message);
    });
});

// Funções para os novos modais
function alterarPrioridadeModal(id) {
    document.getElementById('denunciaIdPrioridade').value = id;
    const modal = new bootstrap.Modal(document.getElementById('modalAlterarPrioridade'));
    modal.show();
}

function atribuirResponsavelModal(id) {
    document.getElementById('denunciaIdAtribuir').value = id;
    carregarUsuariosDisponiveis();
    const modal = new bootstrap.Modal(document.getElementById('modalAtribuirResponsavel'));
    modal.show();
}

function carregarUsuariosDisponiveis() {
    const select = document.getElementById('selectResponsavel');
    select.innerHTML = '<option value="">Carregando...</option>';
    
    fetch('/admin/usuarios/listar-admins')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                select.innerHTML = '<option value="">Não atribuído</option>';
                data.usuarios.forEach(usuario => {
                    select.innerHTML += `<option value="${usuario.id}">${usuario.nome} (${usuario.nivel_acesso})</option>`;
                });
            } else {
                select.innerHTML = '<option value="">Erro ao carregar usuários</option>';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar usuários:', error);
            select.innerHTML = '<option value="">Erro ao carregar usuários</option>';
        });
}

// Form handlers para os novos modais
document.getElementById('formAlterarPrioridade').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const denunciaId = formData.get('denuncia_id');
    
    console.log('Dashboard - Alterando prioridade da denúncia ID:', denunciaId);
    
    fetch('/admin/denuncia/' + denunciaId + '/prioridade', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Prioridade alterada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Dashboard - Erro ao alterar prioridade:', error);
        alert('Erro ao alterar prioridade: ' + error.message);
    });
});

document.getElementById('formAtribuirResponsavel').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const denunciaId = formData.get('denuncia_id');
    
    console.log('Dashboard - Atribuindo responsável para denúncia ID:', denunciaId);
    
    fetch('/admin/denuncia/' + denunciaId + '/atribuir', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Responsável atribuído com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Dashboard - Erro ao atribuir responsável:', error);
        alert('Erro ao atribuir responsável: ' + error.message);
    });
});
</script>

<!-- Modal Alterar Prioridade -->
<div class="modal fade" id="modalAlterarPrioridade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-flag me-2"></i>Alterar Prioridade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAlterarPrioridade">
                <div class="modal-body">
                    <input type="hidden" id="denunciaIdPrioridade" name="denuncia_id">
                    <div class="mb-3">
                        <label class="form-label">Nova Prioridade</label>
                        <select class="form-select" name="prioridade" required>
                            <option value="">Selecione...</option>
                            <option value="Baixa">Baixa</option>
                            <option value="Média">Média</option>
                            <option value="Alta">Alta</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Justificativa</label>
                        <textarea class="form-control" name="justificativa" rows="3" placeholder="Motivo da alteração de prioridade..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Alterar Prioridade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Atribuir Responsável -->
<div class="modal fade" id="modalAtribuirResponsavel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Atribuir Responsável</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAtribuirResponsavel">
                <div class="modal-body">
                    <input type="hidden" id="denunciaIdAtribuir" name="denuncia_id">
                    <div class="mb-3">
                        <label class="form-label">Responsável</label>
                        <select class="form-select" name="admin_id" required id="selectResponsavel">
                            <option value="">Carregando usuários...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacao" rows="3" placeholder="Motivo da atribuição ou instruções..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Atribuir</button>
                </div>
            </form>
        </div>
    </div>
</div>