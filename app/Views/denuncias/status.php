<?php
$pageTitle = 'Consultar Status da Denúncia';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-gradient d-flex align-items-center">
                    <i class="fas fa-search text-accent me-2"></i>
                    <h3 class="mb-0">Consultar Status da Denúncia</h3>
                </div>
                <div class="card-body">
                    <form id="consultaForm" method="POST" action="/denuncia/consultar" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="protocolo" class="form-label">Número do Protocolo</label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg" 
                                id="protocolo" 
                                name="protocolo" 
                                required 
                                placeholder="Digite o número do protocolo"
                                autofocus
                            >
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i>
                                Digite o número do protocolo recebido ao registrar a denúncia.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>
                                Consultar
                            </button>
                        </div>
                    </form>

                    <!-- Resultado da consulta -->
                    <div id="resultadoConsulta" class="mt-4" style="display: none;">
                        <hr>
                        <h4 class="mb-4">Detalhes da Denúncia</h4>
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Protocolo:</strong>
                                    <span id="resultProtocolo" class="ms-2"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Status:</strong>
                                    <span id="resultStatus" class="badge ms-2"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Data de Registro:</strong>
                                    <span id="resultData" class="ms-2"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Última Atualização:</strong>
                                    <span id="resultAtualizacao" class="ms-2">-</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Descrição:</strong>
                                    <p id="resultDescricao" class="mt-2 mb-3"></p>
                                </div>

                                <!-- Histórico de Status -->
                                <div id="blocoHistorico" class="mb-3" style="display: none;">
                                    <strong>Histórico de Tratativas:</strong>
                                    <div class="timeline mt-3">
                                        <div id="timelineContent"></div>
                                    </div>
                                </div>

                                <div id="blocoResposta" class="mb-3" style="display: none;">
                                    <strong>Parecer Final:</strong>
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <span id="resultResposta"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
</style>

<script>
document.getElementById('consultaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const protocolo = document.getElementById('protocolo').value.trim();
    if (!protocolo) {
        alert('Por favor, informe o protocolo da denúncia.');
        return;
    }
    
    const formData = new FormData();
    formData.append('protocolo', protocolo);
    
    // Show loading indicator
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Consultando...';
    submitBtn.disabled = true;
    
    fetch('/denuncia/consultar', {
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
        // Reset button
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
        
        if (data.success && data.denuncia) {
            // Display the result directly instead of redirecting
            displayDenunciaDetails(data.denuncia);
        } else {
            throw new Error(data.message || 'Denúncia não encontrada');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        
        // Reset button
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
        
        // Show error message
        alert('Erro ao consultar denúncia: ' + error.message);
        document.getElementById('resultadoConsulta').style.display = 'none';
    });
});

/**
 * Displays the complaint details in the result section
 */
function displayDenunciaDetails(denuncia) {
    // Display result container
    const resultContainer = document.getElementById('resultadoConsulta');
    resultContainer.style.display = 'block';
    
    // Debug the received data
    console.log('Received denuncia data:', denuncia);
    
    // Set basic info with fallbacks for missing data
    document.getElementById('resultProtocolo').textContent = denuncia.protocolo || 'N/A';
    
    const statusElement = document.getElementById('resultStatus');
    // Check if status exists, use a default if not
    const status = denuncia.status || denuncia.situacao || 'Pendente';
    statusElement.textContent = status;
    statusElement.className = 'badge ms-2 ' + getStatusClass(status);
    
    document.getElementById('resultData').textContent = denuncia.data_criacao || denuncia.data_registro || '-';
    document.getElementById('resultAtualizacao').textContent = denuncia.data_atualizacao || denuncia.ultima_atualizacao || '-';
    document.getElementById('resultDescricao').textContent = denuncia.descricao || denuncia.texto || '-';
    
    // Handle history if available
    const blocoHistorico = document.getElementById('blocoHistorico');
    const timelineContent = document.getElementById('timelineContent');
    
    if (denuncia.historico && denuncia.historico.length > 0) {
        timelineContent.innerHTML = '';
        denuncia.historico.forEach(item => {
            const itemHtml = `
                <div class="timeline-item">
                    <div class="timeline-date">${item.data || '-'}</div>
                    ${item.admin ? `<div class="timeline-admin">Por: ${item.admin}</div>` : ''}
                    <div class="timeline-status ${getStatusClass(item.status)}">${item.status || 'Atualização'}</div>
                    ${item.observacao ? `<div class="timeline-obs">${item.observacao}</div>` : ''}
                </div>
            `;
            timelineContent.innerHTML += itemHtml;
        });
        blocoHistorico.style.display = 'block';
    } else {
        blocoHistorico.style.display = 'none';
    }
    
    // Handle response if available
    const blocoResposta = document.getElementById('blocoResposta');
    const resultResposta = document.getElementById('resultResposta');
    
    if (denuncia.resposta || denuncia.parecer) {
        resultResposta.textContent = denuncia.resposta || denuncia.parecer;
        blocoResposta.style.display = 'block';
    } else {
        blocoResposta.style.display = 'none';
    }
    
    // Scroll to results
    resultContainer.scrollIntoView({ behavior: 'smooth' });
}

function getStatusClass(status) {
    // Check if status is undefined or null
    if (!status) {
        return 'bg-secondary';
    }
    
    try {
        // Convert to lowercase safely
        const statusLower = String(status).toLowerCase();
        
        switch(statusLower) {
            case 'pendente':
                return 'bg-warning';
            case 'em análise':
            case 'em analise':
            case 'em investigação':
            case 'em investigacao':
                return 'bg-info';
            case 'concluída':
            case 'concluida':
                return 'bg-success';
            case 'arquivada':
                return 'bg-secondary';
            default:
                return 'bg-secondary';
        }
    } catch (e) {
        console.error('Error processing status:', e);
        return 'bg-secondary';
    }
}
</script> 