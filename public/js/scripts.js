// Scripts principais do sistema de denúncias HSFA
// Melhorado para acessibilidade e contraste

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar sistema de temas
    initializeThemeSystem();
    
    // Outras inicializações existentes
    initializeFormValidation();
    initializeTooltips();
    initializeImagePreview();
    
    // Melhorias de acessibilidade
    enhanceAccessibility();
    initializeTableResponsiveness();
    initializeKeyboardNavigation();
});

/**
 * Sistema de Temas com Acessibilidade
 */
function initializeThemeSystem() {
    // Verificar preferência salva ou preferência do sistema
    const savedTheme = localStorage.getItem('hsfa-theme');
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const currentTheme = savedTheme || systemTheme;
    
    // Aplicar tema inicial
    setTheme(currentTheme);
    
    // Criar botão de alternância se não existir
    createThemeToggle();
    
    // Escutar mudanças na preferência do sistema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('hsfa-theme')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
}

function createThemeToggle() {
    // Verificar se o botão já existe
    if (document.getElementById('theme-toggle')) {
        return;
    }
    
    // Criar botão de alternância
    const toggleButton = document.createElement('button');
    toggleButton.id = 'theme-toggle';
    toggleButton.className = 'theme-toggle';
    toggleButton.setAttribute('aria-label', 'Alternar entre modo claro e escuro');
    toggleButton.setAttribute('title', 'Alternar tema');
    
    // Ícones para modo claro e escuro
    toggleButton.innerHTML = `
        <i class="fas fa-moon dark-icon" aria-hidden="true"></i>
        <i class="fas fa-sun light-icon" aria-hidden="true"></i>
    `;
    
    // Adicionar event listener
    toggleButton.addEventListener('click', toggleTheme);
    toggleButton.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleTheme();
        }
    });
    
    // Adicionar ao body
    document.body.appendChild(toggleButton);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
    
    // Salvar preferência
    localStorage.setItem('hsfa-theme', newTheme);
    
    // Anunciar mudança para leitores de tela
    announceThemeChange(newTheme);
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    
    // Atualizar aria-label do botão
    const toggleButton = document.getElementById('theme-toggle');
    if (toggleButton) {
        const label = theme === 'dark' 
            ? 'Alternar para modo claro' 
            : 'Alternar para modo escuro';
        toggleButton.setAttribute('aria-label', label);
        toggleButton.setAttribute('title', label);
    }
    
    // Atualizar meta theme-color para dispositivos móveis
    updateThemeColor(theme);
}

function updateThemeColor(theme) {
    let metaTheme = document.querySelector('meta[name="theme-color"]');
    if (!metaTheme) {
        metaTheme = document.createElement('meta');
        metaTheme.name = 'theme-color';
        document.head.appendChild(metaTheme);
    }
    
    // Cores do HSFA para barra de status
    const colors = {
        light: '#2E3A55', // hsfa-secondary
        dark: '#1E293B'   // modo escuro
    };
    
    metaTheme.content = colors[theme];
}

function announceThemeChange(theme) {
    // Criar anúncio para leitores de tela
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = `Tema alterado para modo ${theme === 'dark' ? 'escuro' : 'claro'}`;
    
    document.body.appendChild(announcement);
    
    // Remover após anúncio
    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

/**
 * Validação de formulários com melhor acessibilidade
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                focusFirstError(this);
            }
        });
        
        // Validação em tempo real com debounce
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            let timeoutId;
            input.addEventListener('input', function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    validateField(this);
                }, 300);
            });
            
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    let isValid = true;
    let message = '';
    
    // Limpar erros anteriores
    clearFieldError(field);
    
    // Validar campo obrigatório
    if (isRequired && !value) {
        isValid = false;
        message = 'Este campo é obrigatório';
    }
    
    // Validações específicas por tipo
    if (value && field.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Por favor, insira um e-mail válido';
        }
    }
    
    if (value && field.type === 'tel') {
        const phoneRegex = /^[\d\s\-\(\)\+]+$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            message = 'Por favor, insira um telefone válido';
        }
    }
    
    // Mostrar erro se inválido
    if (!isValid) {
        showFieldError(field, message);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    field.setAttribute('aria-invalid', 'true');
    
    // Criar ou atualizar mensagem de erro
    let errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        errorElement.setAttribute('role', 'alert');
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
    field.setAttribute('aria-describedby', errorElement.id || 'error-' + field.name);
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    field.removeAttribute('aria-invalid');
    
    const errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
        errorElement.remove();
    }
}

function focusFirstError(form) {
    const firstError = form.querySelector('.is-invalid');
    if (firstError) {
        firstError.focus();
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Tooltips acessíveis
 */
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    
    tooltipElements.forEach(element => {
        // Garantir que tooltips sejam acessíveis
        if (!element.getAttribute('aria-describedby')) {
            element.setAttribute('aria-describedby', 'tooltip-' + Math.random().toString(36).substr(2, 9));
        }
    });
    
    // Inicializar tooltips do Bootstrap se disponível
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        tooltipElements.forEach(element => {
            new bootstrap.Tooltip(element);
        });
    }
}

/**
 * Preview de imagens com acessibilidade
 */
function initializeImagePreview() {
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            handleImagePreview(e.target);
        });
    });
}

function handleImagePreview(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validar tipo de arquivo
    if (!file.type.startsWith('image/')) {
        showAlert('Por favor, selecione apenas arquivos de imagem.', 'warning');
        input.value = '';
        return;
    }
    
    // Validar tamanho (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('A imagem deve ter no máximo 5MB.', 'warning');
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        createImagePreview(input, e.target.result, file.name);
    };
    reader.readAsDataURL(file);
}

function createImagePreview(input, src, filename) {
    // Encontrar ou criar container de preview
    let previewContainer = input.parentNode.querySelector('.image-preview');
    if (!previewContainer) {
        previewContainer = document.createElement('div');
        previewContainer.className = 'image-preview mt-2';
        input.parentNode.appendChild(previewContainer);
    }
    
    previewContainer.innerHTML = `
        <div class="d-flex align-items-start gap-3">
            <img src="${src}" alt="Preview de ${filename}" class="preview-image" style="max-width: 200px; max-height: 150px; object-fit: cover;">
            <div class="flex-grow-1">
                <p class="mb-1"><strong>Arquivo:</strong> ${filename}</p>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImagePreview(this)">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                    Remover
                </button>
            </div>
        </div>
    `;
}

function removeImagePreview(button) {
    const previewContainer = button.closest('.image-preview');
    const input = previewContainer.parentNode.querySelector('input[type="file"]');
    
    input.value = '';
    previewContainer.remove();
}

/**
 * Sistema de alertas acessível
 */
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = getOrCreateAlertContainer();
    
    const alertId = 'alert-' + Math.random().toString(36).substr(2, 9);
    const alertElement = document.createElement('div');
    alertElement.id = alertId;
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.setAttribute('role', 'alert');
    alertElement.setAttribute('aria-live', 'assertive');
    
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar alerta"></button>
    `;
    
    alertContainer.appendChild(alertElement);
    
    // Auto-remover após duração especificada
    if (duration > 0) {
        setTimeout(() => {
            if (alertElement.parentNode) {
                alertElement.remove();
            }
        }, duration);
    }
    
    return alertElement;
}

function getOrCreateAlertContainer() {
    let container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    return container;
}

/**
 * Melhorias de acessibilidade para tabelas
 */
function enhanceTableAccessibility() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        // Adicionar caption se não existir
        if (!table.caption) {
            const caption = document.createElement('caption');
            caption.className = 'sr-only';
            caption.textContent = 'Tabela de dados';
            table.appendChild(caption);
        }
        
        // Adicionar scope aos headers
        const headers = table.querySelectorAll('th');
        headers.forEach(header => {
            if (!header.getAttribute('scope')) {
                const isInThead = header.closest('thead');
                header.setAttribute('scope', isInThead ? 'col' : 'row');
            }
        });
    });
}

/**
 * Skip links para acessibilidade
 */
function addSkipLinks() {
    if (document.querySelector('.skip-links')) return;
    
    const skipLinks = document.createElement('div');
    skipLinks.className = 'skip-links';
    skipLinks.innerHTML = `
        <a href="#main-content" class="skip-to-main">
            Pular para o conteúdo principal
        </a>
        <a href="#sidebar" class="skip-to-nav">
            Pular para a navegação
        </a>
    `;
    
    document.body.insertBefore(skipLinks, document.body.firstChild);
}

// Executar melhorias de acessibilidade quando DOM carregado
document.addEventListener('DOMContentLoaded', function() {
    enhanceTableAccessibility();
    addSkipLinks();
});

// Estilo CSS para elementos de acessibilidade
const accessibilityStyles = `
    .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
    
    .skip-links a {
        position: absolute;
        left: -9999px;
        z-index: 9999;
        background: var(--hsfa-primary);
        color: white;
        padding: 1rem;
        text-decoration: none;
        font-weight: 500;
    }
    
    .skip-links a:focus {
        left: 0;
        top: 0;
    }
    
    .theme-toggle {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--hsfa-secondary);
        color: var(--hsfa-text-light);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        transition: all 0.3s ease;
    }
    
    .theme-toggle:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    }
    
    .theme-toggle:focus-visible {
        outline: 2px solid var(--hsfa-primary);
        outline-offset: 3px;
    }
    
    .theme-toggle i {
        font-size: 1.25rem;
    }
    
    .dark-icon {
        display: block;
    }
    
    .light-icon {
        display: none;
    }
    
    [data-theme="dark"] .dark-icon {
        display: none;
    }
    
    [data-theme="dark"] .light-icon {
        display: block;
    }
    
    .image-preview {
        border: 2px dashed var(--border-color);
        border-radius: 0.5rem;
        padding: 1rem;
        background-color: var(--bg-card);
    }
    
    .preview-image {
        border-radius: 0.375rem;
        border: 1px solid var(--border-color);
    }
`;

// Adicionar estilos ao head
const styleSheet = document.createElement('style');
styleSheet.textContent = accessibilityStyles;
document.head.appendChild(styleSheet);

/**
 * Melhorias de Acessibilidade
 */
function enhanceAccessibility() {
    // Adicionar skip links se não existirem
    addSkipLinks();
    
    // Melhorar navegação por teclado
    enhanceKeyboardNavigation();
    
    // Adicionar labels para ícones
    addIconLabels();
    
    // Melhorar contraste de cores
    enhanceColorContrast();
    
    // Adicionar indicadores de foco
    enhanceFocusIndicators();
}

function addSkipLinks() {
    if (document.querySelector('.skip-links')) return;
    
    const skipLinks = document.createElement('div');
    skipLinks.className = 'skip-links';
    skipLinks.innerHTML = `
        <a href="#main-content" class="skip-to-main">
            Pular para o conteúdo principal
        </a>
        <a href="#main-navigation" class="skip-to-nav">
            Pular para a navegação
        </a>
    `;
    
    document.body.insertBefore(skipLinks, document.body.firstChild);
}

function enhanceKeyboardNavigation() {
    // Melhorar navegação por teclado em menus
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Melhorar navegação em tabelas
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const cells = table.querySelectorAll('td, th');
        cells.forEach((cell, index) => {
            cell.setAttribute('tabindex', '0');
            cell.addEventListener('keydown', function(e) {
                const nextCell = cells[index + 1];
                const prevCell = cells[index - 1];
                
                switch(e.key) {
                    case 'ArrowRight':
                        e.preventDefault();
                        if (nextCell) nextCell.focus();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        if (prevCell) prevCell.focus();
                        break;
                }
            });
        });
    });
}

function addIconLabels() {
    // Adicionar labels para ícones sem texto
    const icons = document.querySelectorAll('i[class*="fa-"]:not([aria-label]):not([title])');
    icons.forEach(icon => {
        const parent = icon.parentElement;
        if (parent && !parent.textContent.trim()) {
            const iconClass = icon.className;
            let label = '';
            
            if (iconClass.includes('fa-home')) label = 'Início';
            else if (iconClass.includes('fa-plus')) label = 'Adicionar';
            else if (iconClass.includes('fa-edit')) label = 'Editar';
            else if (iconClass.includes('fa-trash')) label = 'Excluir';
            else if (iconClass.includes('fa-search')) label = 'Pesquisar';
            else if (iconClass.includes('fa-save')) label = 'Salvar';
            else if (iconClass.includes('fa-close')) label = 'Fechar';
            else if (iconClass.includes('fa-menu')) label = 'Menu';
            else if (iconClass.includes('fa-user')) label = 'Usuário';
            else if (iconClass.includes('fa-cog')) label = 'Configurações';
            else label = 'Ícone';
            
            icon.setAttribute('aria-label', label);
        }
    });
}

function enhanceColorContrast() {
    // Verificar e melhorar contraste de cores
    const elements = document.querySelectorAll('.btn, .badge, .alert, .card');
    elements.forEach(element => {
        const computedStyle = window.getComputedStyle(element);
        const backgroundColor = computedStyle.backgroundColor;
        const color = computedStyle.color;
        
        // Adicionar classes para melhor contraste se necessário
        if (element.classList.contains('btn-primary') || element.classList.contains('btn-secondary')) {
            element.style.textShadow = 'none';
        }
    });
}

function enhanceFocusIndicators() {
    // Melhorar indicadores de foco
    const focusableElements = document.querySelectorAll('button, input, select, textarea, a, [tabindex]');
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '2px solid var(--color-primary)';
            this.style.outlineOffset = '2px';
        });
        
        element.addEventListener('blur', function() {
            this.style.outline = '';
            this.style.outlineOffset = '';
        });
    });
}

/**
 * Responsividade de Tabelas
 */
function initializeTableResponsiveness() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        // Adicionar classe para responsividade
        if (!table.classList.contains('table-mobile')) {
            table.classList.add('table-mobile');
        }
        
        // Adicionar data-labels para mobile
        const headers = table.querySelectorAll('th');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index].textContent.trim());
                }
            });
        });
        
        // Adicionar caption se não existir
        if (!table.caption) {
            const caption = document.createElement('caption');
            caption.className = 'sr-only';
            caption.textContent = 'Tabela de dados';
            table.appendChild(caption);
        }
    });
}

/**
 * Navegação por Teclado
 */
function initializeKeyboardNavigation() {
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl + / para foco na busca
        if (e.ctrlKey && e.key === '/') {
            e.preventDefault();
            const searchInput = document.querySelector('input[type="search"], input[placeholder*="buscar"], input[placeholder*="pesquisar"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // ESC para fechar modais
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const closeBtn = modal.querySelector('.btn-close, [data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            });
        }
        
        // Enter para ativar botões focados
        if (e.key === 'Enter') {
            const focusedElement = document.activeElement;
            if (focusedElement && focusedElement.classList.contains('btn')) {
                e.preventDefault();
                focusedElement.click();
            }
        }
    });
}

/**
 * Sistema de Notificações Melhorado
 */
function showNotification(message, type = 'info', options = {}) {
    // Usar o sistema de notificações se disponível
    if (window.notificationSystem) {
        return window.notificationSystem.show(message, type, options);
    }
    
    // Fallback para sistema básico
    const alertContainer = getOrCreateAlertContainer();
    const alertId = 'alert-' + Math.random().toString(36).substr(2, 9);
    const alertElement = document.createElement('div');
    alertElement.id = alertId;
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.setAttribute('role', 'alert');
    alertElement.setAttribute('aria-live', 'assertive');
    
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar alerta"></button>
    `;
    
    alertContainer.appendChild(alertElement);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (alertElement.parentNode) {
            alertElement.remove();
        }
    }, 5000);
    
    return alertId;
}

/**
 * Sistema de Loading Melhorado
 */
function showLoading(element, message = 'Carregando...') {
    // Usar o sistema de loading se disponível
    if (window.loadingSystem) {
        return window.loadingSystem.show(element, message);
    }
    
    // Fallback para sistema básico
    const loader = document.createElement('div');
    loader.className = 'loading-overlay';
    loader.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <span>${message}</span>
        </div>
    `;
    
    element.style.position = 'relative';
    element.appendChild(loader);
    
    return loader;
}

function hideLoading(element) {
    // Usar o sistema de loading se disponível
    if (window.loadingSystem) {
        return window.loadingSystem.hide(element);
    }
    
    // Fallback para sistema básico
    const loader = element.querySelector('.loading-overlay');
    if (loader) {
        loader.remove();
    }
}

/**
 * Melhorias de Formulários
 */
function enhanceFormAccessibility() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Adicionar validação em tempo real
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            // Adicionar aria-describedby para mensagens de erro
            if (!input.getAttribute('aria-describedby')) {
                const errorId = 'error-' + input.name;
                input.setAttribute('aria-describedby', errorId);
            }
            
            // Melhorar labels
            const label = form.querySelector(`label[for="${input.id}"]`);
            if (label && !input.getAttribute('aria-label')) {
                input.setAttribute('aria-label', label.textContent.trim());
            }
        });
        
        // Adicionar indicadores de campos obrigatórios
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            const label = form.querySelector(`label[for="${field.id}"]`);
            if (label && !label.textContent.includes('*')) {
                label.innerHTML += ' <span class="text-danger" aria-label="obrigatório">*</span>';
            }
        });
    });
}

// Executar melhorias de formulários quando DOM carregado
document.addEventListener('DOMContentLoaded', function() {
    enhanceFormAccessibility();
}); 