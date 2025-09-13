/**
 * HSFA - Scripts principais com tema claro forçado
 * Sistema de Denúncias - Hospital São Francisco de Assis
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar sistema de tema claro forçado
    initializeHSFALightTheme();
    
    // Inicializar outros sistemas
    initializeNotifications();
    initializeFormValidation();
    initializeAccessibility();
    initializeProgressiveEnhancement();
    
    console.log('HSFA: Sistema inicializado com tema claro forçado');
});

/**
 * HSFA - Sistema de Tema Claro Forçado
 */
function initializeHSFALightTheme() {
    // Forçar tema claro sempre
    forceHSFALightTheme();
    
    // Remover qualquer preferência de tema salva
    try {
        localStorage.removeItem('hsfa-theme');
        localStorage.removeItem('theme');
        localStorage.removeItem('darkMode');
        localStorage.removeItem('colorScheme');
    } catch (e) {
        // Ignorar erros de localStorage
    }
    
    // Neutralizar listeners de mudança de sistema
    neutralizeSystemThemeListeners();
    
    // Verificar tema a cada 2 segundos (failsafe)
    setInterval(forceHSFALightTheme, 2000);
}

function forceHSFALightTheme() {
    // Força atributos de tema claro
    document.documentElement.setAttribute('data-theme', 'light');
    document.documentElement.style.colorScheme = 'light';
    
    // Remove classes de dark mode
    document.documentElement.classList.remove('dark', 'theme-dark', 'dark-mode');
    document.body.classList.remove('dark', 'theme-dark', 'dark-mode');
    
    // Atualizar meta theme-color com cor HSFA
    updateHSFAThemeColor();
    
    // Remover botões de alternância se existirem
    removeThemeToggles();
}

function neutralizeSystemThemeListeners() {
    const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Neutralizar métodos
    darkModeQuery.onchange = null;
    darkModeQuery.addListener = function() {};
    darkModeQuery.addEventListener = function() {};
    darkModeQuery.removeEventListener = function() {};
}

function updateHSFAThemeColor() {
    let metaTheme = document.querySelector('meta[name="theme-color"]');
    if (!metaTheme) {
        metaTheme = document.createElement('meta');
        metaTheme.name = 'theme-color';
        document.head.appendChild(metaTheme);
    }
    metaTheme.content = '#01717B';
}

function removeThemeToggles() {
    const toggles = document.querySelectorAll(
        '#theme-toggle, .theme-toggle, .dark-mode-toggle, [data-theme-toggle]'
    );
    toggles.forEach(toggle => toggle.remove());
}

// Neutralizar funções antigas de tema
function toggleTheme() {
    console.warn('HSFA: Alternância de tema desabilitada');
}

function setTheme(theme) {
    console.warn('HSFA: Forçando tema claro, ignorando:', theme);
    forceHSFALightTheme();
}

function createThemeToggle() {
    console.log('HSFA: Botão de alternância desabilitado');
}

/**
 * Sistema de Notificações
 */
function initializeNotifications() {
    // Verificar se há mensagens para exibir
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('success')) {
        showNotification(decodeURIComponent(urlParams.get('success')), 'success');
    }
    
    if (urlParams.has('error')) {
        showNotification(decodeURIComponent(urlParams.get('error')), 'error');
    }
    
    if (urlParams.has('info')) {
        showNotification(decodeURIComponent(urlParams.get('info')), 'info');
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `hsfa-notification hsfa-notification-${type}`;
    notification.innerHTML = `
        <div class="hsfa-notification-content">
            <i class="hsfa-notification-icon fas ${getNotificationIcon(type)}"></i>
            <span class="hsfa-notification-message">${message}</span>
            <button class="hsfa-notification-close" aria-label="Fechar notificação">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Adicionar ao body
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.add('hsfa-notification-show');
    }, 100);
    
    // Fechar automaticamente
    const autoClose = setTimeout(() => {
        closeNotification(notification);
    }, duration);
    
    // Botão de fechar
    const closeBtn = notification.querySelector('.hsfa-notification-close');
    closeBtn.addEventListener('click', () => {
        clearTimeout(autoClose);
        closeNotification(notification);
    });
    
    return notification;
}

function closeNotification(notification) {
    notification.classList.remove('hsfa-notification-show');
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

/**
 * Validação de Formulários
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        // Validação em tempo real
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearFieldError(input));
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    let isValid = true;
    let errorMessage = '';
    
    // Verificar se campo obrigatório está vazio
    if (isRequired && !value) {
        isValid = false;
        errorMessage = 'Este campo é obrigatório.';
    }
    
    // Validações específicas por tipo
    if (isValid && value) {
        switch (field.type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Digite um e-mail válido.';
                }
                break;
                
            case 'tel':
                const phoneRegex = /^[\d\s\(\)\-\+]{10,}$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Digite um telefone válido.';
                }
                break;
        }
    }
    
    // Aplicar estilo de erro ou sucesso
    if (isValid) {
        showFieldSuccess(field);
    } else {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldStatus(field);
    
    field.classList.add('hsfa-field-error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'hsfa-field-error-message';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function showFieldSuccess(field) {
    clearFieldStatus(field);
    field.classList.add('hsfa-field-success');
}

function clearFieldError(field) {
    field.classList.remove('hsfa-field-error');
    const errorMessage = field.parentNode.querySelector('.hsfa-field-error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

function clearFieldStatus(field) {
    field.classList.remove('hsfa-field-error', 'hsfa-field-success');
    const errorMessage = field.parentNode.querySelector('.hsfa-field-error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

/**
 * Melhorias de Acessibilidade
 */
function initializeAccessibility() {
    // Skip links
    addSkipLinks();
    
    // Focus management
    improveFocusManagement();
    
    // Keyboard navigation
    enhanceKeyboardNavigation();
    
    // ARIA labels dinâmicos
    updateAriaLabels();
}

function addSkipLinks() {
    if (document.querySelector('.hsfa-skip-links')) return;
    
    const skipLinks = document.createElement('div');
    skipLinks.className = 'hsfa-skip-links';
    skipLinks.innerHTML = `
        <a href="#main-content" class="hsfa-skip-link">Pular para o conteúdo principal</a>
        <a href="#main-navigation" class="hsfa-skip-link">Pular para a navegação</a>
    `;
    
    document.body.insertBefore(skipLinks, document.body.firstChild);
}

function improveFocusManagement() {
    // Melhorar foco visível
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('hsfa-keyboard-focus');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('hsfa-keyboard-focus');
    });
}

function enhanceKeyboardNavigation() {
    // Esc para fechar modais
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const bootstrap = window.bootstrap;
                if (bootstrap) {
                    bootstrap.Modal.getInstance(modal).hide();
                }
            }
        }
    });
}

function updateAriaLabels() {
    // Atualizar labels baseados no contexto
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && !submitBtn.hasAttribute('aria-label')) {
            const formTitle = form.querySelector('h1, h2, h3')?.textContent || 'formulário';
            submitBtn.setAttribute('aria-label', `Enviar ${formTitle.toLowerCase()}`);
        }
    });
}

/**
 * Progressive Enhancement
 */
function initializeProgressiveEnhancement() {
    // Lazy loading de imagens
    initializeLazyLoading();
    
    // Service Worker (se disponível)
    registerServiceWorker();
    
    // Preload de recursos críticos
    preloadCriticalResources();
}

function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
}

function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registrado:', registration);
            })
            .catch(error => {
                console.log('SW falhou:', error);
            });
    }
}

function preloadCriticalResources() {
    // Preload de recursos baseado no contexto da página
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('/admin/')) {
        preloadResource('/css/admin.css', 'style');
        preloadResource('/js/admin.js', 'script');
    }
}

function preloadResource(href, as) {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.href = href;
    link.as = as;
    document.head.appendChild(link);
}

// Expor funções globais necessárias
window.HSFA = window.HSFA || {};
window.HSFA.showNotification = showNotification;
window.HSFA.forceHSFALightTheme = forceHSFALightTheme;
window.HSFA.validateForm = validateForm;
