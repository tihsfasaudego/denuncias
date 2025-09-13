/**
 * Sistema de Notificações HSFA
 * Melhorado para acessibilidade e usabilidade
 */

class NotificationSystem {
    constructor() {
        this.container = null;
        this.notifications = new Map();
        this.init();
    }

    init() {
        this.createContainer();
        this.setupKeyboardNavigation();
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.className = 'notification-container';
        this.container.setAttribute('aria-live', 'polite');
        this.container.setAttribute('aria-atomic', 'false');
        this.container.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1080;
            max-width: 400px;
            width: 100%;
            pointer-events: none;
        `;
        
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', options = {}) {
        const config = {
            id: options.id || this.generateId(),
            message: message,
            type: type,
            duration: options.duration || this.getDefaultDuration(type),
            dismissible: options.dismissible !== false,
            actions: options.actions || [],
            icon: options.icon || this.getDefaultIcon(type),
            title: options.title || this.getDefaultTitle(type),
            ...options
        };

        // Remover notificação existente com mesmo ID
        if (this.notifications.has(config.id)) {
            this.hide(config.id);
        }

        const notification = this.createNotification(config);
        this.container.appendChild(notification);
        this.notifications.set(config.id, notification);

        // Anunciar para leitores de tela
        this.announceToScreenReader(message, type);

        // Auto-remover se tiver duração
        if (config.duration > 0) {
            setTimeout(() => {
                this.hide(config.id);
            }, config.duration);
        }

        return config.id;
    }

    createNotification(config) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${config.type}`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        notification.setAttribute('data-notification-id', config.id);
        notification.style.cssText = `
            background: var(--color-bg-card);
            border: 1px solid var(--color-border-primary);
            border-left: 4px solid ${this.getTypeColor(config.type)};
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            margin-bottom: 0.5rem;
            padding: 1rem;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            pointer-events: auto;
            position: relative;
        `;

        // Adicionar título se fornecido
        let titleHtml = '';
        if (config.title) {
            titleHtml = `<div class="notification-title" style="font-weight: 600; margin-bottom: 0.5rem; color: var(--color-text-primary);">${config.title}</div>`;
        }

        // Adicionar ícone se fornecido
        let iconHtml = '';
        if (config.icon) {
            iconHtml = `<span class="notification-icon" style="margin-right: 0.5rem; font-size: 1.25rem;" aria-hidden="true">${config.icon}</span>`;
        }

        // Adicionar ações se fornecidas
        let actionsHtml = '';
        if (config.actions && config.actions.length > 0) {
            actionsHtml = `<div class="notification-actions" style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">`;
            config.actions.forEach(action => {
                actionsHtml += `<button type="button" class="btn btn-sm ${action.class || 'btn-outline'}" onclick="${action.handler}">${action.label}</button>`;
            });
            actionsHtml += `</div>`;
        }

        // Botão de fechar se dismissible
        let closeButtonHtml = '';
        if (config.dismissible) {
            closeButtonHtml = `
                <button type="button" 
                        class="notification-close" 
                        onclick="notificationSystem.hide('${config.id}')"
                        aria-label="Fechar notificação"
                        style="position: absolute; top: 0.5rem; right: 0.5rem; background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text-muted);">
                    ×
                </button>
            `;
        }

        notification.innerHTML = `
            ${closeButtonHtml}
            <div class="notification-content" style="display: flex; align-items: flex-start;">
                ${iconHtml}
                <div class="notification-body" style="flex: 1;">
                    ${titleHtml}
                    <div class="notification-message" style="color: var(--color-text-primary);">${config.message}</div>
                    ${actionsHtml}
                </div>
            </div>
        `;

        // Animar entrada
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });

        return notification;
    }

    hide(id) {
        const notification = this.notifications.get(id);
        if (!notification) return;

        notification.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            this.notifications.delete(id);
        }, 300);
    }

    hideAll() {
        this.notifications.forEach((notification, id) => {
            this.hide(id);
        });
    }

    getDefaultDuration(type) {
        const durations = {
            success: 5000,
            info: 7000,
            warning: 8000,
            error: 10000
        };
        return durations[type] || 5000;
    }

    getDefaultIcon(type) {
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }

    getDefaultTitle(type) {
        const titles = {
            success: 'Sucesso',
            error: 'Erro',
            warning: 'Atenção',
            info: 'Informação'
        };
        return titles[type] || 'Notificação';
    }

    getTypeColor(type) {
        const colors = {
            success: 'var(--color-success)',
            error: 'var(--color-error)',
            warning: 'var(--color-warning)',
            info: 'var(--color-info)'
        };
        return colors[type] || colors.info;
    }

    generateId() {
        return 'notification-' + Math.random().toString(36).substr(2, 9);
    }

    announceToScreenReader(message, type) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'assertive');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = `${this.getDefaultTitle(type)}: ${message}`;
        
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            if (announcement.parentNode) {
                announcement.parentNode.removeChild(announcement);
            }
        }, 1000);
    }

    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // ESC para fechar todas as notificações
            if (e.key === 'Escape') {
                this.hideAll();
            }
        });
    }
}

/**
 * Sistema de Loading
 */
class LoadingSystem {
    constructor() {
        this.loaders = new Map();
    }

    show(element, message = 'Carregando...') {
        const elementId = element.id || this.generateId();
        
        // Remover loader existente se houver
        this.hide(element);

        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.setAttribute('data-loader-id', elementId);
        loader.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: inherit;
        `;

        loader.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner" style="width: 40px; height: 40px; border: 4px solid var(--color-border-primary); border-radius: 50%; border-top-color: var(--color-primary); animation: spin 1s linear infinite;"></div>
                <div style="margin-top: 1rem; color: var(--color-text-primary); font-weight: 500;">${message}</div>
            </div>
        `;

        // Garantir que o elemento pai tenha position relative
        const computedStyle = window.getComputedStyle(element);
        if (computedStyle.position === 'static') {
            element.style.position = 'relative';
        }

        element.appendChild(loader);
        this.loaders.set(elementId, { element, loader });

        // Adicionar animação de fade in
        requestAnimationFrame(() => {
            loader.style.opacity = '0';
            loader.style.transition = 'opacity 0.3s ease-in-out';
            requestAnimationFrame(() => {
                loader.style.opacity = '1';
            });
        });
    }

    hide(element) {
        const elementId = element.id || Array.from(this.loaders.keys()).find(id => 
            this.loaders.get(id).element === element
        );

        if (!elementId || !this.loaders.has(elementId)) return;

        const { loader } = this.loaders.get(elementId);
        
        if (loader && loader.parentNode) {
            loader.style.opacity = '0';
            setTimeout(() => {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
                this.loaders.delete(elementId);
            }, 300);
        }
    }

    hideAll() {
        this.loaders.forEach(({ loader }, elementId) => {
            if (loader && loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
        });
        this.loaders.clear();
    }

    generateId() {
        return 'loader-' + Math.random().toString(36).substr(2, 9);
    }
}

/**
 * Sistema de Validação de Formulários
 */
class FormValidator {
    constructor(form) {
        this.form = form;
        this.rules = {};
        this.init();
    }

    init() {
        this.form.addEventListener('submit', (e) => {
            if (!this.validate()) {
                e.preventDefault();
                this.focusFirstError();
            }
        });

        // Validação em tempo real
        this.form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });

            field.addEventListener('input', () => {
                // Debounce para evitar validação excessiva
                clearTimeout(field.validationTimeout);
                field.validationTimeout = setTimeout(() => {
                    this.validateField(field);
                }, 300);
            });
        });
    }

    addRule(fieldName, rule) {
        this.rules[fieldName] = rule;
    }

    validate() {
        let isValid = true;
        const fields = this.form.querySelectorAll('input, textarea, select');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
        const fieldName = field.name;
        const rule = this.rules[fieldName];
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Limpar erros anteriores
        this.clearFieldError(field);

        // Validações básicas
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Este campo é obrigatório';
        } else if (value) {
            // Validações específicas por tipo
            if (field.type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    message = 'Por favor, insira um e-mail válido';
                }
            }

            if (field.type === 'tel') {
                const phoneRegex = /^[\d\s\-\(\)\+]+$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    message = 'Por favor, insira um telefone válido';
                }
            }

            if (field.type === 'url') {
                try {
                    new URL(value);
                } catch {
                    isValid = false;
                    message = 'Por favor, insira uma URL válida';
                }
            }

            // Validações customizadas
            if (rule) {
                if (rule.minLength && value.length < rule.minLength) {
                    isValid = false;
                    message = `Mínimo de ${rule.minLength} caracteres`;
                } else if (rule.maxLength && value.length > rule.maxLength) {
                    isValid = false;
                    message = `Máximo de ${rule.maxLength} caracteres`;
                } else if (rule.pattern && !rule.pattern.test(value)) {
                    isValid = false;
                    message = rule.message || 'Formato inválido';
                }
            }
        }

        // Mostrar erro se inválido
        if (!isValid) {
            this.showFieldError(field, message);
        } else {
            this.showFieldSuccess(field);
        }

        return isValid;
    }

    showFieldError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        field.setAttribute('aria-invalid', 'true');

        const errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        errorElement.setAttribute('role', 'alert');
        errorElement.textContent = message;

        field.parentNode.appendChild(errorElement);
        field.setAttribute('aria-describedby', errorElement.id || 'error-' + field.name);
    }

    showFieldSuccess(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        field.removeAttribute('aria-invalid');

        const errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.remove();
        }
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid', 'is-valid');
        field.removeAttribute('aria-invalid');

        const errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.remove();
        }
    }

    focusFirstError() {
        const firstError = this.form.querySelector('.is-invalid');
        if (firstError) {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

// Instâncias globais
const notificationSystem = new NotificationSystem();
const loadingSystem = new LoadingSystem();

// Adicionar CSS para animações
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
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
`;
document.head.appendChild(style);

// Exportar para uso global
window.NotificationSystem = NotificationSystem;
window.LoadingSystem = LoadingSystem;
window.FormValidator = FormValidator;
window.notificationSystem = notificationSystem;
window.loadingSystem = loadingSystem;
