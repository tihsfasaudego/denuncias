<?php
/**
 * Lista de Denúncias por Status
 */
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/admin/denuncias" class="text-decoration-none">Denúncias</a>
                            </li>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($statusFiltro) ?></li>
                        </ol>
                    </nav>
                    <h2 class="hsfa-title text-primary">
                        <?php
                        $iconMap = [
                            'Pendente' => 'fas fa-clock',
                            'Em Análise' => 'fas fa-search',
                            'Em Investigação' => 'fas fa-search-plus', 
                            'Concluída' => 'fas fa-check-circle',
                            'Arquivada' => 'fas fa-archive'
                        ];
                        $icon = $iconMap[$statusFiltro] ?? 'fas fa-list';
                        ?>
                        <i class="<?= $icon ?> me-2"></i>
                        Denúncias <?= htmlspecialchars($statusFiltro) ?>
                        <span class="badge bg-primary ms-2"><?= count($denuncias) ?></span>
                    </h2>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Atualizar
                    </button>
                    <button class="btn btn-success" onclick="exportarLista()">
                        <i class="fas fa-download me-1"></i>
                        Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card hsfa-card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Pesquisar</label>
                    <input type="text" class="form-control" id="pesquisar" placeholder="Protocolo ou descrição...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prioridade</label>
                    <select class="form-select" id="filtrarPrioridade">
                        <option value="">Todas</option>
                        <option value="Baixa">Baixa</option>
                        <option value="Média">Média</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="dataInicial">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="dataFinal">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="aplicarFiltros()">
                            <i class="fas fa-filter me-1"></i>
                            Filtrar
                        </button>
                        <button class="btn btn-outline-secondary" onclick="limparFiltros()">
                            <i class="fas fa-times me-1"></i>
                            Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Denúncias -->
    <div class="card hsfa-card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-primary">
                    <i class="fas fa-list me-2"></i>
                    Lista de Denúncias
                </h5>
                <div class="d-flex align-items-center gap-3">
                    <small class="text-muted">
                        Mostrando <?= count($denuncias) ?> registro(s)
                    </small>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="alterarVisualizacao(this.value)">
                        <option value="tabela">Tabela</option>
                        <option value="cards">Cards</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <!-- Visualização em Tabela -->
            <div id="visualizacaoTabela">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabelaDenuncias">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">
                                    <input type="checkbox" id="selecionarTodos" onchange="selecionarTodos(this.checked)">
                                </th>
                                <th>Protocolo</th>
                                <th>Data</th>
                                <th>Prioridade</th>
                                <th>Descrição</th>
                                <th>Responsável</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($denuncias as $denuncia): ?>
                            <tr class="denuncia-row" data-id="<?= $denuncia['id'] ?>">
                                <td class="px-3">
                                    <input type="checkbox" name="denunciaSelecionada[]" value="<?= $denuncia['id'] ?>">
                                </td>
                                <td>
                                    <code class="text-primary"><?= htmlspecialchars($denuncia['protocolo']) ?></code>
                                </td>
                                <td>
                                    <div>
                                        <small class="d-block"><?= date('d/m/Y', strtotime($denuncia['data_criacao'])) ?></small>
                                        <small class="text-muted"><?= date('H:i', strtotime($denuncia['data_criacao'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $prioridadeClass = [
                                        'Baixa' => 'success',
                                        'Média' => 'warning',
                                        'Alta' => 'danger',
                                        'Urgente' => 'danger'
                                    ];
                                    $pClass = $prioridadeClass[$denuncia['prioridade']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $pClass ?>"><?= htmlspecialchars($denuncia['prioridade']) ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($denuncia['descricao']) ?>">
                                        <?= htmlspecialchars(substr($denuncia['descricao'], 0, 150)) ?><?= strlen($denuncia['descricao']) > 150 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($denuncia['responsavel'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($denuncia['responsavel']) ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Não atribuído</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="visualizarDenuncia(<?= $denuncia['id'] ?>)" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" onclick="alterarStatusModal(<?= $denuncia['id'] ?>)" title="Alterar Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" title="Mais ações">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" onclick="atribuirResponsavel(<?= $denuncia['id'] ?>)">
                                                    <i class="fas fa-user-plus me-2"></i>Atribuir
                                                </a></li>
                                                <li><a class="dropdown-item" onclick="alterarPrioridade(<?= $denuncia['id'] ?>)">
                                                    <i class="fas fa-flag me-2"></i>Prioridade
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" onclick="excluirDenuncia(<?= $denuncia['id'] ?>)">
                                                    <i class="fas fa-trash me-2"></i>Excluir
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($denuncias)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fs-1 mb-3 d-block"></i>
                                    <h5>Nenhuma denúncia encontrada</h5>
                                    <p>Não há denúncias com status "<?= htmlspecialchars($statusFiltro) ?>" no momento.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if (!empty($denuncias)): ?>
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="acoesSelecionadas()">
                        <i class="fas fa-tasks me-1"></i>
                        Ações em Lote
                    </button>
                </div>
                <div>
                    <small class="text-muted">Total: <?= count($denuncias) ?> denúncia(s)</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

.denuncia-row:hover {
    background-color: rgba(0, 58, 77, 0.02);
}

.breadcrumb-item + .breadcrumb-item::before {
    color: #6c757d;
}

.breadcrumb-item a {
    color: #003a4d;
}
</style>

<script>
function aplicarFiltros() {
    const pesquisar = document.getElementById('pesquisar').value;
    const prioridade = document.getElementById('filtrarPrioridade').value;
    const dataInicial = document.getElementById('dataInicial').value;
    const dataFinal = document.getElementById('dataFinal').value;
    
    // Implementar filtros locais ou via AJAX
    console.log('Aplicando filtros:', {pesquisar, prioridade, dataInicial, dataFinal});
}

function limparFiltros() {
    document.getElementById('pesquisar').value = '';
    document.getElementById('filtrarPrioridade').value = '';
    document.getElementById('dataInicial').value = '';
    document.getElementById('dataFinal').value = '';
    aplicarFiltros();
}

function exportarLista() {
    const status = '<?= $statusFiltro ?>';
    window.open(`/admin/relatorios/exportar-pdf?status=${encodeURIComponent(status)}`, '_blank');
}

function selecionarTodos(checked) {
    const checkboxes = document.querySelectorAll('input[name="denunciaSelecionada[]"]');
    checkboxes.forEach(cb => cb.checked = checked);
}

// Modal de visualização de denúncia
function visualizarDenuncia(id) {
    console.log('Tentando carregar denúncia ID:', id);
    
    // Buscar dados da denúncia via AJAX
    fetch(`/admin/denuncia/${id}/dados`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            if (data.success) {
                preencherModalVisualizacao(data.denuncia);
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarDenuncia'));
                modal.show();
            } else {
                console.error('Erro do servidor:', data);
                alert('Erro ao carregar denúncia: ' + (data.message || 'Erro desconhecido'));
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
            }
        })
        .catch(error => {
            console.error('Erro de rede/parsing:', error);
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
    
    // Configurar botões do modal
    document.getElementById('denunciaIdModal').value = denuncia.id;
    
    // Configurar status badge
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

function atribuirResponsavel(id) {
    atribuirResponsavelModal(id);
}

function alterarPrioridade(id) {
    alterarPrioridadeModal(id);
}

function excluirDenuncia(id) {
    if (confirm('Tem certeza que deseja excluir esta denúncia?')) {
        // AJAX para exclusão
        console.log('Excluir denúncia:', id);
    }
}

function acoesSelecionadas() {
    const selecionadas = document.querySelectorAll('input[name="denunciaSelecionada[]"]:checked');
    if (selecionadas.length === 0) {
        alert('Selecione pelo menos uma denúncia.');
        return;
    }
    console.log('Ações em lote para:', Array.from(selecionadas).map(cb => cb.value));
}

function alterarVisualizacao(tipo) {
    // Implementar mudança entre tabela e cards
    console.log('Alterar visualização para:', tipo);
}
</script>

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
                <button type="button" class="btn btn-outline-primary" onclick="responderDenunciaModal()">
                    <i class="fas fa-reply me-1"></i>Responder
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

<!-- Modal Responder -->
<div class="modal fade" id="modalResponder" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-reply me-2"></i>Responder Denúncia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formResponderModal">
                <div class="modal-body">
                    <input type="hidden" id="denunciaIdResposta" name="denuncia_id">
                    <div class="mb-3">
                        <label class="form-label">Resposta</label>
                        <textarea class="form-control" name="resposta" rows="6" placeholder="Digite sua resposta..." required></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notificar" id="notificarDenunciante">
                        <label class="form-check-label" for="notificarDenunciante">
                            Notificar denunciante
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Resposta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function responderDenunciaModal() {
    document.getElementById('denunciaIdResposta').value = document.getElementById('denunciaIdModal').value;
    const modal = new bootstrap.Modal(document.getElementById('modalResponder'));
    modal.show();
}

// Form handlers
document.getElementById('formAlterarStatusModal').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const denunciaId = formData.get('denuncia_id');
    
    console.log('Alterando status da denúncia ID:', denunciaId);
    console.log('Novo status:', formData.get('status'));
    
    fetch('/admin/denuncia/' + denunciaId + '/status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Resposta do servidor:', data);
        if (data.success) {
            alert('Status alterado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao alterar status:', error);
        alert('Erro ao alterar status: ' + error.message);
    });
});

document.getElementById('formResponderModal').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/admin/denuncia/' + formData.get('denuncia_id') + '/responder', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Resposta enviada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao enviar resposta');
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

<script>
// Funções para os novos modais
function alterarPrioridadeModal(id) {
    document.getElementById('denunciaIdPrioridade').value = id;
    const modal = new bootstrap.Modal(document.getElementById('modalAlterarPrioridade'));
    modal.show();
}

function atribuirResponsavelModal(id) {
    document.getElementById('denunciaIdAtribuir').value = id;
    
    // Carregar lista de usuários
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
    
    console.log('Alterando prioridade da denúncia ID:', denunciaId);
    
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
        console.error('Erro ao alterar prioridade:', error);
        alert('Erro ao alterar prioridade: ' + error.message);
    });
});

document.getElementById('formAtribuirResponsavel').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const denunciaId = formData.get('denuncia_id');
    
    console.log('Atribuindo responsável para denúncia ID:', denunciaId);
    
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
        console.error('Erro ao atribuir responsável:', error);
        alert('Erro ao atribuir responsável: ' + error.message);
    });
});
</script>
