/**
 * Sistema de notificações em tempo real
 * Gerencia SSE, toasts e interface de notificações
 */

class NotificationSystem {
    constructor(options = {}) {
        this.options = {
            sseUrl: '/sse.php',
            apiUrl: '/api/notifications',
            soundEnabled: true,
            autoMarkAsRead: true,
            maxToasts: 5,
            toastDuration: 5000,
            ...options
        };
        
        this.eventSource = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.notificationQueue = [];
        this.activeToasts = new Set();
        
        this.init();
    }
    
    /**
     * Inicializa o sistema
     */
    init() {
        this.createNotificationContainer();
        this.loadSettings();
        this.bindEvents();
        this.connect();
        this.loadInitialNotifications();
    }
    
    /**
     * Cria container para toasts
     */
    createNotificationContainer() {
        if (document.getElementById('notification-container')) {
            return;
        }
        
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        container.innerHTML = `
            <style>
                .notification-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 400px;
                }
                
                .notification-toast {
                    background: var(--bg-card);
                    border: 1px solid var(--border-color);
                    border-radius: 8px;
                    padding: 16px;
                    margin-bottom: 12px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                    transform: translateX(100%);
                    transition: all 0.3s ease;
                    cursor: pointer;
                    position: relative;
                    overflow: hidden;
                }
                
                .notification-toast.show {
                    transform: translateX(0);
                }
                
                .notification-toast.hide {
                    transform: translateX(100%);
                    opacity: 0;
                }
                
                .notification-toast::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: var(--notification-color, #007bff);
                }
                
                .notification-toast.success::before {
                    background: var(--success, #28a745);
                }
                
                .notification-toast.warning::before {
                    background: var(--warning, #ffc107);
                }
                
                .notification-toast.error::before {
                    background: var(--danger, #dc3545);
                }
                
                .notification-toast.info::before {
                    background: var(--info, #17a2b8);
                }
                
                .notification-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 8px;
                }
                
                .notification-title {
                    font-weight: 600;
                    color: var(--text-color);
                    margin: 0;
                    font-size: 14px;
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    color: var(--text-muted);
                    cursor: pointer;
                    padding: 0;
                    font-size: 16px;
                    line-height: 1;
                }
                
                .notification-close:hover {
                    color: var(--text-color);
                }
                
                .notification-message {
                    color: var(--text-muted);
                    margin: 0 0 8px 0;
                    font-size: 13px;
                    line-height: 1.4;
                }
                
                .notification-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: 12px;
                }
                
                .notification-action {
                    background: var(--hsfa-primary);
                    color: white;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    font-size: 12px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                }
                
                .notification-action:hover {
                    background: var(--hsfa-primary-dark);
                    color: white;
                    text-decoration: none;
                }
                
                .notification-timestamp {
                    font-size: 11px;
                    color: var(--text-muted);
                    margin-top: 4px;
                }
                
                .notification-progress {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 2px;
                    background: var(--notification-color, #007bff);
                    transition: width linear;
                }
                
                @media (max-width: 768px) {
                    .notification-container {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                        max-width: none;
                    }
                }
            </style>
        `;
        
        document.body.appendChild(container);
    }
    
    /**
     * Conecta ao SSE
     */
    connect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        
        try {
            this.eventSource = new EventSource(this.options.sseUrl);
            
            this.eventSource.onopen = () => {
                console.log('✅ Notificações conectadas');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.updateConnectionStatus(true);
            };
            
            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleNotification(data);
                } catch (e) {
                    console.error('Erro ao processar notificação:', e);
                }
            };
            
            this.eventSource.addEventListener('notification', (event) => {
                try {
                    const notification = JSON.parse(event.data);
                    this.handleNotification(notification);
                } catch (e) {
                    console.error('Erro ao processar notificação:', e);
                }
            });
            
            this.eventSource.addEventListener('heartbeat', (event) => {
                // Manter conexão viva
                this.updateConnectionStatus(true);
            });
            
            this.eventSource.onerror = () => {
                console.warn('❌ Erro na conexão de notificações');
                this.isConnected = false;
                this.updateConnectionStatus(false);
                this.handleReconnect();
            };
            
        } catch (e) {
            console.error('Erro ao conectar SSE:', e);
            this.handleReconnect();
        }
    }
    
    /**
     * Tenta reconectar
     */
    handleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Máximo de tentativas de reconexão atingido');
            this.showToast({
                title: 'Conexão perdida',
                message: 'Não foi possível reconectar às notificações. Recarregue a página.',
                type: 'error'
            });
            return;
        }
        
        this.reconnectAttempts++;
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
        
        console.log(`🔄 Tentando reconectar em ${delay}ms (tentativa ${this.reconnectAttempts})`);
        
        setTimeout(() => {
            this.connect();
        }, delay);
    }
    
    /**
     * Atualiza status da conexão na interface
     */
    updateConnectionStatus(connected) {
        const indicator = document.querySelector('.connection-indicator');
        if (indicator) {
            indicator.className = `connection-indicator ${connected ? 'connected' : 'disconnected'}`;
            indicator.title = connected ? 'Notificações conectadas' : 'Notificações desconectadas';
        }
        
        // Atualizar ícone de notificações
        const notificationIcon = document.querySelector('.notification-icon');
        if (notificationIcon) {
            notificationIcon.classList.toggle('disconnected', !connected);
        }
    }
    
    /**
     * Processa notificação recebida
     */
    handleNotification(notification) {
        console.log('📧 Nova notificação:', notification);
        
        // Adicionar à fila
        this.notificationQueue.push(notification);
        
        // Mostrar toast se configurado
        if (this.settings.browser_enabled) {
            this.showToast(notification);
        }
        
        // Tocar som se configurado
        if (this.settings.sound_enabled) {
            this.playNotificationSound();
        }
        
        // Atualizar contador
        this.updateNotificationCounter();
        
        // Marcar como lida automaticamente se configurado
        if (this.options.autoMarkAsRead && notification.id) {
            setTimeout(() => {
                this.markAsRead(notification.id);
            }, 3000);
        }
    }
    
    /**
     * Mostra toast de notificação
     */
    showToast(notification) {
        if (this.activeToasts.size >= this.options.maxToasts) {
            // Remove o toast mais antigo
            const oldestToast = this.activeToasts.values().next().value;
            this.hideToast(oldestToast);
        }
        
        const toast = this.createToastElement(notification);
        const container = document.getElementById('notification-container');
        
        container.appendChild(toast);
        this.activeToasts.add(toast);
        
        // Mostrar com animação
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Auto-remover após duração configurada
        if (this.options.toastDuration > 0) {
            const progressBar = toast.querySelector('.notification-progress');
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.transitionDuration = this.options.toastDuration + 'ms';
                
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 100);
            }
            
            setTimeout(() => {
                this.hideToast(toast);
            }, this.options.toastDuration);
        }
    }
    
    /**
     * Cria elemento do toast
     */
    createToastElement(notification) {
        const toast = document.createElement('div');
        toast.className = `notification-toast ${notification.type || 'info'}`;
        toast.dataset.notificationId = notification.id;
        
        const timestamp = notification.timestamp ? 
            new Date(notification.timestamp * 1000).toLocaleString('pt-BR') : 
            'Agora';
        
        toast.innerHTML = `
            <div class="notification-header">
                <h6 class="notification-title">${this.escapeHtml(notification.title)}</h6>
                <button class="notification-close" onclick="notificationSystem.hideToast(this.closest('.notification-toast'))">&times;</button>
            </div>
            <p class="notification-message">${this.escapeHtml(notification.message)}</p>
            ${notification.action_url ? `
                <div class="notification-actions">
                    <a href="${notification.action_url}" class="notification-action">
                        ${notification.action_text || 'Ver Detalhes'}
                    </a>
                </div>
            ` : ''}
            <div class="notification-timestamp">${timestamp}</div>
            ${this.options.toastDuration > 0 ? '<div class="notification-progress"></div>' : ''}
        `;
        
        // Click para fechar
        toast.addEventListener('click', (e) => {
            if (!e.target.closest('a, button')) {
                this.hideToast(toast);
            }
        });
        
        return toast;
    }
    
    /**
     * Esconde toast
     */
    hideToast(toast) {
        if (!toast || !this.activeToasts.has(toast)) {
            return;
        }
        
        toast.classList.add('hide');
        this.activeToasts.delete(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
    
    /**
     * Toca som de notificação
     */
    playNotificationSound() {
        if (!this.settings.sound_enabled) {
            return;
        }
        
        try {
            // Criar áudio se não existir
            if (!this.notificationAudio) {
                this.notificationAudio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhCjaI0fPTgjsFJHrI7+ONQQ0VYbXp66tZFAg+ltryxnkpBSl+zPLZizYIG2q+7OSXQQwOUarm7blmGAU8j9r0xHQoBS+CzvLZizYIG2q+7OSXQQwOUarm7blmGAU8j9r0xHQoBS+CzvLZizYIG2q+7OSXQQwOUarm7blmGAU8j9r0xHQoBS+CzvLZizYIG2q+7OSXQQwOUarm7blmGAU8j9r0xHQoBS+CzvLZizYIG2q+7OSXQQwOUarm7blmGAU8j9r0xHQoBS+CzvLZizYIG2q+7OSXQQwOUarm7blmGAU8j9r0xHQo');
            }
            
            this.notificationAudio.currentTime = 0;
            this.notificationAudio.play().catch(e => {
                // Ignorar erros de autoplay
                console.log('Não foi possível tocar som de notificação:', e.message);
            });
        } catch (e) {
            console.error('Erro ao tocar som:', e);
        }
    }
    
    /**
     * Atualiza contador de notificações
     */
    updateNotificationCounter() {
        this.fetchUnreadCount().then(count => {
            const badges = document.querySelectorAll('.notification-badge');
            badges.forEach(badge => {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline' : 'none';
            });
            
            // Atualizar título da página
            if (count > 0) {
                document.title = `(${count}) ${document.title.replace(/^\(\d+\)\s/, '')}`;
            } else {
                document.title = document.title.replace(/^\(\d+\)\s/, '');
            }
        });
    }
    
    /**
     * Carrega notificações iniciais
     */
    loadInitialNotifications() {
        this.fetchNotifications(10, true).then(data => {
            this.updateNotificationCounter();
        });
    }
    
    /**
     * Busca notificações via API
     */
    async fetchNotifications(limit = 20, onlyUnread = false) {
        try {
            const params = new URLSearchParams({
                limit: limit,
                unread: onlyUnread
            });
            
            const response = await fetch(`/admin/notifications?${params}`);
            const data = await response.json();
            
            if (data.success) {
                return data.data;
            } else {
                throw new Error(data.error?.message || 'Erro ao buscar notificações');
            }
        } catch (e) {
            console.error('Erro ao buscar notificações:', e);
            return { notifications: [], unread_count: 0 };
        }
    }
    
    /**
     * Busca contador de não lidas
     */
    async fetchUnreadCount() {
        try {
            const response = await fetch('/admin/notifications/unread-count');
            const data = await response.json();
            
            if (data.success) {
                return data.data.unread_count;
            }
        } catch (e) {
            console.error('Erro ao buscar contador:', e);
        }
        
        return 0;
    }
    
    /**
     * Marca notificação como lida
     */
    async markAsRead(notificationId) {
        try {
            const response = await fetch('/admin/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationCounter();
                return true;
            } else {
                console.error('Erro ao marcar como lida:', data.error?.message);
                return false;
            }
        } catch (e) {
            console.error('Erro ao marcar como lida:', e);
            return false;
        }
    }
    
    /**
     * Marca todas como lidas
     */
    async markAllAsRead() {
        try {
            const response = await fetch('/admin/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationCounter();
                this.showToast({
                    title: 'Sucesso',
                    message: 'Todas as notificações foram marcadas como lidas',
                    type: 'success'
                });
                return true;
            } else {
                throw new Error(data.error?.message || 'Erro ao marcar todas como lidas');
            }
        } catch (e) {
            console.error('Erro ao marcar todas como lidas:', e);
            this.showToast({
                title: 'Erro',
                message: 'Não foi possível marcar todas como lidas',
                type: 'error'
            });
            return false;
        }
    }
    
    /**
     * Carrega configurações
     */
    loadSettings() {
        const saved = localStorage.getItem('notification_settings');
        this.settings = saved ? JSON.parse(saved) : {
            browser_enabled: true,
            sound_enabled: true,
            email_enabled: true
        };
    }
    
    /**
     * Salva configurações
     */
    saveSettings() {
        localStorage.setItem('notification_settings', JSON.stringify(this.settings));
    }
    
    /**
     * Bind de eventos
     */
    bindEvents() {
        // Visibilidade da página
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateNotificationCounter();
            }
        });
        
        // Antes de fechar página
        window.addEventListener('beforeunload', () => {
            if (this.eventSource) {
                this.eventSource.close();
            }
        });
    }
    
    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Desconecta SSE
     */
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        this.isConnected = false;
    }
    
    /**
     * Reconecta SSE
     */
    reconnect() {
        this.disconnect();
        this.reconnectAttempts = 0;
        this.connect();
    }
    
    /**
     * Status da conexão
     */
    getConnectionStatus() {
        return {
            connected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            queueLength: this.notificationQueue.length
        };
    }
}

// Instância global
let notificationSystem;

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se está em página admin
    if (document.body.classList.contains('admin-page') || window.location.pathname.includes('/admin')) {
        notificationSystem = new NotificationSystem();
        
        // Expor globalmente para debug
        window.notificationSystem = notificationSystem;
        
        console.log('🔔 Sistema de notificações inicializado');
    }
});

// Exportar para uso em módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}
