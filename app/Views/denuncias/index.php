<?php 

?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-gradient d-flex align-items-center">
                    <i class="fas fa-shield-alt text-accent me-2"></i>
                    <h3 class="mb-0">Enviar Denúncia Anônima</h3>
                </div>
                <div class="card-body">
                    
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php elseif(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form id="denunciaForm" method="POST" action="/denuncia/criar" enctype="multipart/form-data" class="needs-validation" novalidate>
                        
                        <!-- Token CSRF para proteção contra ataques -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="mb-4">
                            <label for="categoria" class="form-label">
                                <i class="fas fa-tag me-2"></i>Categoria da Denúncia
                            </label>
                            <select class="form-select form-select-lg" id="categoria" name="categoria[]" multiple required>
                                <?php foreach($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['id']); ?>">
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i>
                                Você pode selecionar múltiplas categorias.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="descricao" class="form-label">Descrição da Denúncia</label>
                            <textarea 
                                class="form-control form-control-lg" 
                                id="descricao" 
                                name="descricao" 
                                rows="5" 
                                required 
                                placeholder="Descreva sua denúncia detalhadamente..."
                                autofocus
                            ></textarea>
                            <div class="form-text text-light">
                                <i class="fas fa-info-circle"></i>
                                Sua identidade será preservada.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="data_ocorrencia" class="form-label">Data da Ocorrência</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="data_ocorrencia" 
                                name="data_ocorrencia"
                                max="<?php echo date('Y-m-d'); ?>"
                            >
                        </div>

                        <div class="mb-4">
                            <label for="local_ocorrencia" class="form-label">Local da Ocorrência</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="local_ocorrencia" 
                                name="local_ocorrencia"
                                placeholder="Ex: Departamento, Setor, Andar..."
                            >
                        </div>

                        <div class="mb-4">
                            <label for="anexo" class="form-label">
                                <i class="fas fa-paperclip me-1"></i>
                                Anexar Arquivo
                            </label>
                            <input 
                                type="file" 
                                class="form-control" 
                                id="anexo" 
                                name="anexo"
                                accept=".jpg,.jpeg,.png,.pdf,.docx"
                                onchange="validarArquivo(this)"
                            >
                            <div class="form-text text-light">
                                Formatos aceitos: JPG, PNG, PDF, DOCX (máx. 10MB)
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>
                                Enviar Denúncia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal do Protocolo -->
<div class="modal fade" id="protocoloModal" tabindex="-1" aria-labelledby="protocoloModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="protocoloModalLabel">
                    <i class="fas fa-check-circle me-2"></i>
                    Denúncia Registrada com Sucesso!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center py-4">
                <h4 class="mb-4">Seu Número de Protocolo</h4>
                <div class="protocolo-container mb-4">
                    <h1 class="protocolo-numero" id="numeroProtocolo"></h1>
                    <button class="btn btn-outline-primary btn-copy" onclick="copiarProtocolo()">
                        <i class="fas fa-copy me-2"></i>
                        Clique aqui para copiar
                    </button>
                </div>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Guarde este número! Ele é necessário para consultar o status da sua denúncia.
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="/status" class="btn btn-primary me-2">
                    <i class="fas fa-search me-2"></i>
                    Consultar Status
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.protocolo-container {
    background: rgba(0, 58, 77, 0.05);
    padding: 20px;
    border-radius: 8px;
    border: 2px dashed var(--border-color);
}

.protocolo-numero {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--action-color);
    margin-bottom: 1rem;
    letter-spacing: 2px;
}

.btn-copy {
    transition: all 0.3s ease;
}

.btn-copy:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-copy.copied {
    background-color: var(--success-color);
    color: white;
    border-color: var(--success-color);
}
</style>

<script>
    // Função para validar o tamanho do arquivo
    function validarArquivo(input) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (input.files.length > 0) {
            if (input.files[0].size > maxSize) {
                alert("O arquivo selecionado é muito grande! O tamanho máximo permitido é 10MB.");
                input.value = "";
            }
        }
    }

    function copiarProtocolo() {
        const protocolo = document.getElementById('numeroProtocolo').textContent;
        navigator.clipboard.writeText(protocolo).then(() => {
            const btnCopy = document.querySelector('.btn-copy');
            btnCopy.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
            btnCopy.classList.add('copied');
            
            setTimeout(() => {
                btnCopy.innerHTML = '<i class="fas fa-copy me-2"></i>Clique aqui para copiar';
                btnCopy.classList.remove('copied');
            }, 2000);
        }).catch(err => {
            console.error('Erro ao copiar:', err);
            alert('Não foi possível copiar o protocolo automaticamente. Por favor, copie manualmente.');
        });
    }

    // Manipulador do formulário
    document.getElementById('denunciaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validação básica do formulário
        const descricao = this.querySelector('#descricao').value.trim();
        const categorias = Array.from(this.querySelector('#categoria').selectedOptions);
        
        if (!descricao) {
            alert('Por favor, preencha a descrição da denúncia.');
            return;
        }
        
        if (categorias.length === 0) {
            alert('Por favor, selecione pelo menos uma categoria.');
            return;
        }
        
        const formData = new FormData(this);
        
        // Adicionar token CSRF manualmente para garantir que está sendo enviado
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        formData.set('csrf_token', csrfToken);
        
        // Desabilitar o botão de envio
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
        
        fetch('/denuncia/criar', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Redirecionar para a página de detalhes
                window.location.href = '/denuncia/detalhes?protocolo=' + data.protocolo;
            } else {
                throw new Error(data.message || 'Erro ao processar denúncia');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao enviar denúncia: ' + error.message);
            
            // Reabilitar o botão de envio
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    });
</script>
