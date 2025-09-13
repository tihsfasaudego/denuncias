<?php
// Garantir que $denuncia existe
if (!isset($denuncia)) {
    header('Location: /denuncia/consultar');
    exit;
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-gradient d-flex align-items-center">
                    <i class="fas fa-file-alt text-accent me-2"></i>
                    <h3 class="mb-0">Detalhes da Denúncia</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h4 class="text-primary">
                            <i class="fas fa-hashtag me-2"></i>
                            Protocolo: <?php echo htmlspecialchars($denuncia['protocolo']); ?>
                        </h4>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong><i class="fas fa-calendar me-2"></i>Data de Registro:</strong>
                            <p><?php echo htmlspecialchars($denuncia['data_criacao']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="fas fa-clock me-2"></i>Status:</strong>
                            <span class="badge <?php
                                switch($denuncia['status']) {
                                    case 'Pendente':
                                        echo 'bg-warning';
                                        break;
                                    case 'Em Análise':
                                    case 'Em Investigação':
                                        echo 'bg-info';
                                        break;
                                    case 'Concluída':
                                        echo 'bg-success';
                                        break;
                                    case 'Arquivada':
                                        echo 'bg-secondary';
                                        break;
                                    default:
                                        echo 'bg-secondary';
                                }
                            ?> ms-2">
                                <?php echo htmlspecialchars($denuncia['status']); ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($denuncia['categorias'])): ?>
                    <div class="mb-4">
                        <strong><i class="fas fa-tags me-2"></i>Categorias:</strong>
                        <p><?php echo htmlspecialchars($denuncia['categorias']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($denuncia['data_ocorrencia'])): ?>
                    <div class="mb-4">
                        <strong><i class="fas fa-calendar-alt me-2"></i>Data da Ocorrência:</strong>
                        <p><?php echo date('d/m/Y', strtotime($denuncia['data_ocorrencia'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($denuncia['local_ocorrencia'])): ?>
                    <div class="mb-4">
                        <strong><i class="fas fa-map-marker-alt me-2"></i>Local da Ocorrência:</strong>
                        <p><?php echo htmlspecialchars($denuncia['local_ocorrencia']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <strong><i class="fas fa-align-left me-2"></i>Descrição:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($denuncia['descricao'])); ?></p>
                    </div>

                    <?php if (!empty($denuncia['anexo'])): ?>
                    <div class="mb-4">
                        <strong><i class="fas fa-paperclip me-2"></i>Anexo:</strong>
                        <p>
                            <a href="/uploads/<?php echo htmlspecialchars($denuncia['anexo']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-2"></i>
                                Baixar Anexo
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($denuncia['historico'])): ?>
                    <div class="mb-4">
                        <h5><i class="fas fa-history me-2"></i>Histórico de Atualizações</h5>
                        <div class="timeline mt-3">
                            <?php foreach ($denuncia['historico'] as $historico): ?>
                            <div class="timeline-item">
                                <div class="timeline-date">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo htmlspecialchars($historico['data_formatada']); ?>
                                </div>
                                <?php if (!empty($historico['admin_nome'])): ?>
                                <div class="timeline-admin">
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($historico['admin_nome']); ?>
                                </div>
                                <?php endif; ?>
                                <div class="timeline-status badge <?php
                                    switch($historico['status_novo']) {
                                        case 'Pendente': echo 'bg-warning'; break;
                                        case 'Em Análise':
                                        case 'Em Investigação': echo 'bg-info'; break;
                                        case 'Concluída': echo 'bg-success'; break;
                                        case 'Arquivada': echo 'bg-secondary'; break;
                                        default: echo 'bg-secondary';
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($historico['status_novo']); ?>
                                </div>
                                <?php if (!empty($historico['observacao'])): ?>
                                <div class="timeline-obs">
                                    <?php echo nl2br(htmlspecialchars($historico['observacao'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($denuncia['status'] === 'Concluída'): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Parecer Final</h5>
                        <?php if (!empty($denuncia['conclusao_descricao'])): ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($denuncia['conclusao_descricao'])); ?></p>
                            <?php if (!empty($denuncia['data_conclusao_formatada'])): ?>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Concluído em: <?php echo htmlspecialchars($denuncia['data_conclusao_formatada']); ?>
                                </small>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="mb-0 text-muted">Nenhum parecer final registrado.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <div class="btn-group">
                            <a href="/denuncia/consultar" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar
                            </a>
                            <button class="btn btn-primary" onclick="imprimirDenuncia()">
                                <i class="fas fa-print me-2"></i>
                                Imprimir
                            </button>
                            <button class="btn btn-success" onclick="baixarPDF()">
                                <i class="fas fa-download me-2"></i>
                                Baixar PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template para impressão -->
<div id="printTemplate" style="display: none;">
    <div class="print-header">
        <h1>Relatório de Denúncia</h1>
        <p>Protocolo: <?php echo htmlspecialchars($denuncia['protocolo']); ?></p>
        <p>Data: <?php echo htmlspecialchars($denuncia['data_criacao']); ?></p>
    </div>
    <div class="print-content">
        <h2>Detalhes da Denúncia</h2>
        <table class="print-table">
            <tr>
                <th>Status:</th>
                <td><?php echo htmlspecialchars($denuncia['status']); ?></td>
            </tr>
            <?php if (!empty($denuncia['categorias'])): ?>
            <tr>
                <th>Categorias:</th>
                <td><?php echo htmlspecialchars($denuncia['categorias']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($denuncia['data_ocorrencia'])): ?>
            <tr>
                <th>Data da Ocorrência:</th>
                <td><?php echo date('d/m/Y', strtotime($denuncia['data_ocorrencia'])); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($denuncia['local_ocorrencia'])): ?>
            <tr>
                <th>Local:</th>
                <td><?php echo htmlspecialchars($denuncia['local_ocorrencia']); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <h3>Descrição</h3>
        <div class="print-description">
            <?php echo nl2br(htmlspecialchars($denuncia['descricao'])); ?>
        </div>

        <?php if (!empty($denuncia['historico'])): ?>
        <h3>Histórico de Atualizações</h3>
        <table class="print-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($denuncia['historico'] as $historico): ?>
                <tr>
                    <td><?php echo htmlspecialchars($historico['data_formatada']); ?></td>
                    <td><?php echo htmlspecialchars($historico['status_novo']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($historico['observacao'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ($denuncia['status'] === 'Concluída' && !empty($denuncia['conclusao_descricao'])): ?>
        <h3>Parecer Final</h3>
        <div class="print-conclusion">
            <?php echo nl2br(htmlspecialchars($denuncia['conclusao_descricao'])); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-color);
}

.timeline-item {
    position: relative;
    margin-left: 40px;
    background: rgba(255, 255, 255, 0.05);
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -33px;
    top: 15px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--action-color);
    border: 2px solid var(--bg-color);
}

.timeline-date {
    font-size: 0.85em;
    color: var(--text-color);
    opacity: 0.8;
}

.timeline-admin {
    font-size: 0.9em;
    color: var(--action-color);
    margin-bottom: 5px;
}

.timeline-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.85em;
    margin-bottom: 5px;
}

.timeline-obs {
    margin-top: 5px;
    font-style: italic;
}

@media print {
    body * {
        visibility: hidden;
    }
    #printTemplate, #printTemplate * {
        visibility: visible;
    }
    #printTemplate {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .print-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .print-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    .print-table th, .print-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .print-description, .print-conclusion {
        margin: 20px 0;
        padding: 10px;
        border: 1px solid #ddd;
    }
}
</style>

<script>
function imprimirDenuncia() {
    window.print();
}

function baixarPDF() {
    // Criar um elemento temporário para o conteúdo
    const printContent = document.getElementById('printTemplate').innerHTML;
    const blob = new Blob([`
        <html>
            <head>
                <title>Denúncia - ${document.querySelector('#printTemplate .print-header p').textContent}</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .print-header { text-align: center; margin-bottom: 30px; }
                    .print-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .print-table th, .print-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    .print-description, .print-conclusion { margin: 20px 0; padding: 10px; border: 1px solid #ddd; }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
        </html>
    `], { type: 'text/html' });

    // Criar URL para download
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `denuncia_${document.querySelector('#printTemplate .print-header p').textContent.replace('Protocolo: ', '')}.html`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script> 