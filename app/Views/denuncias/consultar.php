<div class="denuncia-section">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 text-center">
                <h1>Consultar Denúncia</h1>
                <p class="lead">Informe o número de protocolo para verificar o status da sua denúncia.</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="hsfa-card">
                    <form action="/denuncia/consultar" method="get" class="consulta-form">
                        <div class="hsfa-form-group">
                            <label for="protocolo">Número do Protocolo</label>
                            <div class="input-group">
                                <input type="text" id="protocolo" name="protocolo" class="hsfa-form-control" placeholder="Ex: DEN-2025-12345" value="<?= isset($_GET['protocolo']) ? htmlspecialchars($_GET['protocolo']) : '' ?>" required>
                                <button type="submit" class="hsfa-btn hsfa-btn-primary">Consultar</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="alert hsfa-alert-danger mt-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($denuncia)): ?>
                <div class="hsfa-card mt-4">
                    <h3 class="mb-4"><i class="fas fa-clipboard-list me-2"></i> Informações da Denúncia</h3>
                    
                    <div class="protocolo-box">
                        <p>Protocolo:</p>
                        <div class="protocolo-number"><?= htmlspecialchars($denuncia['protocolo']) ?></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Data de Registro:</span>
                                <span class="detail-value"><?= date('d/m/Y', strtotime($denuncia['data_registro'])) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value status-badge status-<?= strtolower($denuncia['status']) ?>">
                                    <?php
                                    $statusIcon = '';
                                    switch(strtolower($denuncia['status'])) {
                                        case 'pendente':
                                            $statusIcon = '<i class="fas fa-clock me-1"></i>';
                                            break;
                                        case 'em análise':
                                            $statusIcon = '<i class="fas fa-search me-1"></i>';
                                            break;
                                        case 'concluída':
                                            $statusIcon = '<i class="fas fa-check-circle me-1"></i>';
                                            break;
                                        case 'arquivada':
                                            $statusIcon = '<i class="fas fa-archive me-1"></i>';
                                            break;
                                    }
                                    echo $statusIcon . htmlspecialchars($denuncia['status']);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Tipo:</span>
                                <span class="detail-value"><?= htmlspecialchars($denuncia['tipo']) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Local:</span>
                                <span class="detail-value"><?= htmlspecialchars($denuncia['local']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h4>Histórico de Atualizações</h4>
                        <div class="timeline">
                            <?php foreach ($denuncia['historico'] as $atualizacao): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-date"><?= date('d/m/Y', strtotime($atualizacao['data'])) ?></div>
                                    <div class="timeline-title"><?= htmlspecialchars($atualizacao['status']) ?></div>
                                    <?php if (!empty($atualizacao['comentario'])): ?>
                                    <div class="timeline-text"><?= htmlspecialchars($atualizacao['comentario']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($denuncia['resposta'])): ?>
                    <div class="mt-4">
                        <h4>Resposta da Comissão</h4>
                        <div class="resposta-box">
                            <?= htmlspecialchars($denuncia['resposta']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-3">
                <div class="hsfa-card">
                    <h3 class="h5"><i class="fas fa-info-circle me-2"></i> Informações</h3>
                    <p>O número de protocolo foi fornecido no momento do registro da denúncia e também enviado por e-mail, caso você tenha fornecido um endereço.</p>
                    <hr>
                    <h4 class="h6">Status possíveis:</h4>
                    <ul class="status-list">
                        <li><span class="status-indicator pendente"></span> Pendente: Aguardando análise inicial</li>
                        <li><span class="status-indicator analise"></span> Em Análise: Em investigação pela comissão</li>
                        <li><span class="status-indicator concluida"></span> Concluída: Investigação finalizada</li>
                        <li><span class="status-indicator arquivada"></span> Arquivada: Denúncia encerrada</li>
                    </ul>
                </div>
                
                <div class="hsfa-card mt-4">
                    <h3 class="h5"><i class="fas fa-question-circle me-2"></i> Dúvidas?</h3>
                    <p>Se você não possui o número de protocolo ou precisa de ajuda, entre em contato:</p>
                    <div class="contato-item">
                        <i class="fas fa-envelope me-2"></i>
                        <a href="mailto:ouvidoria@hsfasaude.com.br">ouvidoria@hsfasaude.com.br</a>
                    </div>
                    <div class="contato-item">
                        <i class="fas fa-phone me-2"></i>
                        (xx) xxxx-xxxx
                    </div>
                    <div class="text-center mt-3">
                        <a href="/denuncia/nova" class="hsfa-btn hsfa-btn-outline btn-sm">Registrar Nova Denúncia</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.detail-item {
    margin-bottom: 1rem;
}

.detail-label {
    display: block;
    font-family: 'Roboto', sans-serif;
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.detail-value {
    font-weight: 500;
    color: var(--heading-secondary);
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-pendente {
    background-color: #FFE082;
    color: #7B6012;
}

.status-em, .status-análise {
    background-color: #B3E5FC;
    color: #014361;
}

.status-concluída {
    background-color: #C8E6C9;
    color: #1B5E20;
}

.status-arquivada {
    background-color: #E0E0E0;
    color: #424242;
}

.timeline {
    position: relative;
    padding-left: 30px;
    margin-top: 20px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 6px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: var(--hsfa-accent);
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-dot {
    position: absolute;
    left: -30px;
    top: 0;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background-color: var(--hsfa-primary);
}

.timeline-content {
    padding-bottom: 15px;
}

.timeline-date {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 5px;
}

.timeline-title {
    font-weight: 600;
    color: var(--heading-secondary);
    margin-bottom: 5px;
}

.timeline-text {
    font-size: 0.9rem;
}

.resposta-box {
    background-color: var(--alert-success-bg);
    border: 1px solid var(--alert-success-border);
    border-radius: 8px;
    padding: 1rem;
    white-space: pre-line;
}

.status-list {
    list-style: none;
    padding: 0;
    margin: 1rem 0 0 0;
}

.status-list li {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-indicator.pendente {
    background-color: #FFE082;
}

.status-indicator.analise {
    background-color: #B3E5FC;
}

.status-indicator.concluida {
    background-color: #C8E6C9;
}

.status-indicator.arquivada {
    background-color: #E0E0E0;
}

.contato-item {
    margin-bottom: 0.5rem;
}
</style>

<script>
// If there's a protocol in the URL params, auto-fill and submit the form
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const protocolo = urlParams.get('protocolo');
    
    if (protocolo) {
        const inputProtocolo = document.getElementById('protocolo');
        if (inputProtocolo) {
            inputProtocolo.value = protocolo;
            // Trigger form submission if protocol is in URL
            setTimeout(function() {
                const submitButton = document.querySelector('button[type="submit"]');
                if (submitButton) submitButton.click();
            }, 500);
        }
    }
});

// Handle form submission for direct query
document.querySelector('.consulta-form').addEventListener('submit', function(e) {
    // Regular form submission for GET requests is fine
    // Make sure the form action is correct
    this.action = '/denuncia/consultar';
    
    // Log the submission for debugging
    console.log('Submitting consultation form with protocol:', document.getElementById('protocolo').value);
});

// Add error handling for failed fetch requests
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled Promise Rejection:', event.reason);
    
    // Show a user-friendly error message
    if (!document.querySelector('.hsfa-alert-danger')) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert hsfa-alert-danger mt-4';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Erro de conexão ao consultar denúncia. Por favor, tente novamente mais tarde.';
        
        const formCard = document.querySelector('.hsfa-card');
        if (formCard && formCard.parentNode) {
            formCard.parentNode.insertBefore(errorDiv, formCard.nextSibling);
        }
    }
});
</script> 