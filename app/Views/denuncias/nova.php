<div class="denuncia-section dark-theme">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 text-center">
                <h1>Registrar Nova Denúncia</h1>
                <p class="lead">Preencha o formulário abaixo para registrar sua denúncia. Todas as informações são tratadas com sigilo e confidencialidade.</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="hsfa-card dark-card">
                    <form action="/denuncia/enviar" method="post" enctype="multipart/form-data" class="denuncia-form">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-shield-alt me-2"></i>Enviar Denúncia Anônima
                            </h3>
                            
                            <div class="form-section">
                                <h4 class="section-subtitle">
                                    <i class="fas fa-tag me-2"></i>Categoria da Denúncia
                                </h4>
                                
                                <div class="hsfa-form-group">
                                    <select name="categorias[]" id="categorias" class="hsfa-form-control" multiple required>
                                        <option value="assedio_moral">Assédio Moral</option>
                                        <option value="assedio_sexual">Assédio Sexual</option>
                                        <option value="conflito_interesses">Conflito de Interesses</option>
                                        <option value="corrupcao">Corrupção</option>
                                        <option value="discriminacao">Discriminação</option>
                                        <option value="fraude">Fraude</option>
                                        <option value="violacao_normas">Violação de Normas</option>
                                        <option value="seguranca_paciente">Segurança do Paciente</option>
                                        <option value="outros">Outros</option>
                                    </select>
                                    <small class="form-hint"><i class="fas fa-info-circle me-1"></i> Você pode selecionar múltiplas categorias.</small>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4 class="section-subtitle">
                                    <i class="fas fa-file-alt me-2"></i>Descrição da Denúncia
                                </h4>
                                
                                <div class="hsfa-form-group">
                                    <textarea id="descricao" name="descricao" rows="6" class="hsfa-form-control" placeholder="Descreva sua denúncia detalhadamente..." required></textarea>
                                    <small class="form-hint"><i class="fas fa-user-shield me-1"></i> Sua identidade será preservada.</small>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4 class="section-subtitle">
                                    <i class="fas fa-calendar-alt me-2"></i>Data da Ocorrência
                                </h4>
                                
                                <div class="hsfa-form-group">
                                    <input type="date" id="data_ocorrencia" name="data_ocorrencia" class="hsfa-form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4 class="section-subtitle">
                                    <i class="fas fa-map-marker-alt me-2"></i>Local da Ocorrência
                                </h4>
                                
                                <div class="hsfa-form-group">
                                    <input type="text" id="local" name="local" class="hsfa-form-control" placeholder="Ex: Departamento, Setor, Andar..." required>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4 class="section-subtitle">
                                    <i class="fas fa-paperclip me-2"></i>Anexar Arquivo
                                </h4>
                                
                                <div class="hsfa-form-group">
                                    <div class="custom-file-upload">
                                        <input type="file" id="evidencias" name="evidencias[]" class="file-input" multiple>
                                        <label for="evidencias" class="file-label">
                                            <i class="fas fa-upload me-2"></i>Escolher arquivo
                                        </label>
                                        <div id="file-info" class="file-info">Nenhum arquivo escolhido</div>
                                    </div>
                                    <small class="form-hint">Formatos aceitos: PDF, JPG, PNG (máx. 5MB por arquivo)</small>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="hsfa-form-group">
                                    <div class="hsfa-form-check confirmation-check">
                                        <input type="checkbox" id="confirma_verdade" name="confirma_verdade" class="hsfa-form-check-input" required>
                                        <label for="confirma_verdade">
                                            <i class="fas fa-check-circle me-2"></i>
                                            Declaro que as informações prestadas são verdadeiras e estou ciente das consequências de prestar informações falsas.
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="hsfa-btn hsfa-btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Denúncia
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o seletor múltiplo de categorias
    if (typeof Choices !== 'undefined') {
        const categoriaSelect = new Choices('#categorias', {
            removeItemButton: true,
            maxItemCount: -1,
            searchEnabled: true,
            renderChoiceLimit: -1,
            placeholder: true,
            placeholderValue: "Selecione as categorias aplicáveis",
            itemSelectText: 'Clique para selecionar',
            classNames: {
                containerOuter: 'choices hsfa-choices-dark'
            }
        });
    }
    
    // Preview de arquivos selecionados
    const fileInput = document.getElementById('evidencias');
    const fileInfo = document.getElementById('file-info');
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            let fileNames = Array.from(this.files).map(file => file.name).join(', ');
            fileInfo.textContent = fileNames;
            fileInfo.classList.add('has-files');
        } else {
            fileInfo.textContent = 'Nenhum arquivo escolhido';
            fileInfo.classList.remove('has-files');
        }
    });
});
</script>

<style>
.denuncia-section.dark-theme {
    background-color: #0a1c2e;
    color: #fff;
    padding: 3rem 0;
}

.dark-card {
    background-color: #0f2942;
    border-color: #2a3f54;
}

.section-title {
    color: #fff;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid #2a3f54;
}

.section-subtitle {
    color: #60b0cc;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-hint {
    display: block;
    color: #91a7c1;
    margin-top: 0.5rem;
    font-size: 0.85rem;
}

.hsfa-form-control {
    background-color: #1a3353;
    border: 1px solid #2a3f54;
    color: #fff;
}

.hsfa-form-control:focus {
    background-color: #1e3c60;
    border-color: #60b0cc;
    box-shadow: 0 0 0 0.25rem rgba(96, 176, 204, 0.25);
}

.hsfa-form-control::placeholder {
    color: #91a7c1;
}

.custom-file-upload {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.file-input {
    position: absolute;
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    z-index: -1;
}

.file-label {
    background-color: #2a3f54;
    color: #fff;
    padding: 0.75rem 1.25rem;
    border-radius: 6px;
    cursor: pointer;
    display: inline-block;
    transition: all 0.3s;
}

.file-label:hover {
    background-color: #375a7f;
}

.file-info {
    flex: 1;
    background-color: #1a3353;
    padding: 0.75rem;
    border-radius: 6px;
    min-height: 50px;
    display: flex;
    align-items: center;
}

.file-info.has-files {
    color: #60b0cc;
}

.confirmation-check {
    background-color: rgba(96, 176, 204, 0.1);
    border: 1px solid #2a3f54;
    border-radius: 6px;
    padding: 1rem;
}

/* Choices.js custom styling for dark theme */
.hsfa-choices-dark .choices__inner {
    background-color: #1a3353;
    border-color: #2a3f54;
    color: #fff;
}

.hsfa-choices-dark .choices__input {
    background-color: #1a3353;
    color: #fff;
}

.hsfa-choices-dark .choices__list--dropdown {
    background-color: #1a3353;
    border-color: #2a3f54;
}

.hsfa-choices-dark .choices__list--dropdown .choices__item {
    color: #fff;
}

.hsfa-choices-dark .choices__list--dropdown .choices__item--selectable.is-highlighted {
    background-color: #375a7f;
}

.hsfa-choices-dark .choices__list--multiple .choices__item {
    background-color: #60b0cc;
    border: none;
}

.hsfa-choices-dark .choices__list--multiple .choices__item.is-highlighted {
    background-color: #4a99b5;
}

.hsfa-btn-primary {
    background-color: #60b0cc;
    color: #fff;
    border: none;
}

.hsfa-btn-primary:hover {
    background-color: #4a99b5;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}
</style> 