# Arquitetura de Melhorias - Sistema de Denúncias HSFA

## Visão Geral

Este documento apresenta a arquitetura de melhorias para o sistema de denúncias do Hospital São Francisco de Assis, baseado na análise completa realizada no frontend e painel administrativo.

## Estrutura Atual do Sistema

```
Sistema de Denúncias HSFA
├── Frontend Público
│   ├── Página Inicial (/)
│   ├── Criação de Denúncias (/denuncia/criar)
│   ├── Consulta de Denúncias (/denuncia/consultar)
│   └── Área Administrativa (/admin/login)
├── Painel Administrativo
│   ├── Dashboard (/admin/dashboard)
│   ├── Gerenciamento de Denúncias (/admin/denuncias)
│   ├── Relatórios (/admin/relatorios)
│   ├── Usuários (/admin/usuarios)
│   └── Configurações (/admin/configuracoes)
└── Backend
    ├── Controllers
    ├── Models
    ├── Core
    └── Database
```

## Problemas Identificados

### 1. Problemas Críticos de Acessibilidade
- **Ícones sem texto alternativo**: Muitos ícones não possuem atributos `alt` adequados
- **Contraste de cores**: Alguns elementos podem ter contraste insuficiente
- **Navegação por teclado**: Falta de indicadores visuais para foco
- **Estrutura semântica**: Uso inadequado de elementos HTML semânticos
- **Formulários**: Falta de labels associados corretamente aos campos

### 2. Problemas de Responsividade
- **Layout mobile**: Interface não se adapta adequadamente a telas pequenas
- **Tabelas**: Tabelas com muitas colunas ficam ilegíveis em dispositivos móveis
- **Menu lateral**: Menu lateral não colapsa adequadamente em mobile
- **Botões e campos**: Tamanho inadequado para interação touch

### 3. Problemas Técnicos
- **Erros JavaScript**: Múltiplos erros no console afetando funcionalidades
- **Service Worker**: Falha no registro do Service Worker
- **SSL/Certificados**: Problemas com certificados SSL
- **Performance**: Possível impacto na performance devido aos erros

### 4. Problemas de Usabilidade
- **Feedback visual**: Falta de feedback imediato para ações do usuário
- **Validação de formulários**: Não há validação em tempo real
- **Mensagens de erro**: Falta de mensagens claras de erro e sucesso
- **Loading states**: Ausência de indicadores de carregamento

## Arquitetura de Melhorias

### Fase 1: Correções Críticas (2-3 semanas)

#### 1.1 Correção de Erros JavaScript
```javascript
// Estrutura de tratamento de erros
class ErrorHandler {
    static init() {
        window.addEventListener('error', this.handleError);
        window.addEventListener('unhandledrejection', this.handlePromiseRejection);
    }
    
    static handleError(event) {
        console.error('Erro capturado:', event.error);
        // Enviar para serviço de monitoramento
        this.reportError(event.error);
    }
    
    static handlePromiseRejection(event) {
        console.error('Promise rejeitada:', event.reason);
        this.reportError(event.reason);
    }
}
```

#### 1.2 Melhorias de Acessibilidade
```html
<!-- Estrutura semântica melhorada -->
<header role="banner">
    <nav role="navigation" aria-label="Menu principal">
        <ul>
            <li><a href="/" aria-current="page">Início</a></li>
            <li><a href="/denuncia/criar">Nova Denúncia</a></li>
            <li><a href="/denuncia/consultar">Consultar</a></li>
        </ul>
    </nav>
</header>

<main role="main" id="main-content">
    <section aria-labelledby="denuncia-heading">
        <h1 id="denuncia-heading">Canal de Denúncias</h1>
        <!-- Conteúdo -->
    </section>
</main>

<footer role="contentinfo">
    <!-- Rodapé -->
</footer>
```

#### 1.3 Responsividade Mobile
```css
/* Sistema de breakpoints */
:root {
    --breakpoint-mobile: 375px;
    --breakpoint-tablet: 768px;
    --breakpoint-desktop: 1024px;
    --breakpoint-large: 1440px;
}

/* Layout responsivo */
.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

@media (max-width: 768px) {
    .container {
        padding: 0 0.5rem;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table-mobile {
        display: block;
    }
    
    .table-mobile thead {
        display: none;
    }
    
    .table-mobile tbody,
    .table-mobile tr,
    .table-mobile td {
        display: block;
    }
    
    .table-mobile tr {
        border: 1px solid #ccc;
        margin-bottom: 1rem;
        padding: 1rem;
    }
    
    .table-mobile td:before {
        content: attr(data-label) ": ";
        font-weight: bold;
    }
}
```

### Fase 2: Melhorias de Design e UX (3-4 semanas)

#### 2.1 Sistema de Design Consistente
```css
/* Design System */
:root {
    /* Cores */
    --color-primary: #2563eb;
    --color-secondary: #64748b;
    --color-success: #059669;
    --color-warning: #d97706;
    --color-error: #dc2626;
    --color-info: #0891b2;
    
    /* Tipografia */
    --font-family-primary: 'Inter', sans-serif;
    --font-size-xs: 0.75rem;
    --font-size-sm: 0.875rem;
    --font-size-base: 1rem;
    --font-size-lg: 1.125rem;
    --font-size-xl: 1.25rem;
    --font-size-2xl: 1.5rem;
    
    /* Espaçamento */
    --spacing-1: 0.25rem;
    --spacing-2: 0.5rem;
    --spacing-3: 0.75rem;
    --spacing-4: 1rem;
    --spacing-6: 1.5rem;
    --spacing-8: 2rem;
    
    /* Sombras */
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    
    /* Bordas */
    --border-radius-sm: 0.25rem;
    --border-radius-md: 0.375rem;
    --border-radius-lg: 0.5rem;
    --border-radius-xl: 0.75rem;
}

/* Componentes base */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-2) var(--spacing-4);
    border: 1px solid transparent;
    border-radius: var(--border-radius-md);
    font-size: var(--font-size-sm);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.btn-primary {
    background-color: var(--color-primary);
    color: white;
}

.btn-primary:hover {
    background-color: #1d4ed8;
}

.card {
    background: white;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    padding: var(--spacing-6);
    border: 1px solid #e2e8f0;
}
```

#### 2.2 Sistema de Feedback Visual
```javascript
// Sistema de notificações
class NotificationSystem {
    static show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${this.getIcon(type)}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('notification-show');
        }, 100);
        
        if (duration > 0) {
            setTimeout(() => {
                this.hide(notification);
            }, duration);
        }
    }
    
    static hide(notification) {
        notification.classList.add('notification-hide');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
    
    static getIcon(type) {
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }
}

// Sistema de loading
class LoadingSystem {
    static show(element) {
        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <span>Carregando...</span>
            </div>
        `;
        
        element.style.position = 'relative';
        element.appendChild(loader);
    }
    
    static hide(element) {
        const loader = element.querySelector('.loading-overlay');
        if (loader) {
            loader.remove();
        }
    }
}
```

#### 2.3 Validação de Formulários
```javascript
// Sistema de validação
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
            }
        });
        
        // Validação em tempo real
        this.form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
        });
    }
    
    addRule(fieldName, rule) {
        this.rules[fieldName] = rule;
    }
    
    validate() {
        let isValid = true;
        
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field && !this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(field) {
        const fieldName = field.name;
        const rule = this.rules[fieldName];
        
        if (!rule) return true;
        
        const value = field.value.trim();
        let isValid = true;
        let message = '';
        
        // Validações comuns
        if (rule.required && !value) {
            isValid = false;
            message = 'Este campo é obrigatório';
        } else if (rule.minLength && value.length < rule.minLength) {
            isValid = false;
            message = `Mínimo de ${rule.minLength} caracteres`;
        } else if (rule.pattern && !rule.pattern.test(value)) {
            isValid = false;
            message = rule.message || 'Formato inválido';
        }
        
        this.showFieldError(field, isValid, message);
        return isValid;
    }
    
    showFieldError(field, isValid, message) {
        const errorElement = field.parentNode.querySelector('.field-error');
        
        if (errorElement) {
            errorElement.remove();
        }
        
        if (!isValid) {
            const error = document.createElement('div');
            error.className = 'field-error';
            error.textContent = message;
            field.parentNode.appendChild(error);
            field.classList.add('error');
        } else {
            field.classList.remove('error');
        }
    }
}
```

### Fase 3: Funcionalidades Avançadas (4-5 semanas)

#### 3.1 Sistema de Filtros Avançados
```javascript
// Sistema de filtros
class AdvancedFilters {
    constructor(container) {
        this.container = container;
        this.filters = {};
        this.init();
    }
    
    init() {
        this.container.addEventListener('change', (e) => {
            if (e.target.matches('.filter-input')) {
                this.updateFilter(e.target.name, e.target.value);
            }
        });
    }
    
    updateFilter(name, value) {
        this.filters[name] = value;
        this.applyFilters();
    }
    
    applyFilters() {
        const url = new URL(window.location);
        
        Object.keys(this.filters).forEach(key => {
            if (this.filters[key]) {
                url.searchParams.set(key, this.filters[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        
        window.history.pushState({}, '', url);
        this.loadData();
    }
    
    async loadData() {
        const params = new URLSearchParams(window.location.search);
        const response = await fetch(`/api/denuncias?${params}`);
        const data = await response.json();
        
        this.renderData(data);
    }
    
    renderData(data) {
        // Renderizar dados filtrados
    }
}
```

#### 3.2 Sistema de Paginação
```javascript
// Sistema de paginação
class PaginationSystem {
    constructor(container, options = {}) {
        this.container = container;
        this.currentPage = options.currentPage || 1;
        this.totalPages = options.totalPages || 1;
        this.onPageChange = options.onPageChange || (() => {});
        this.init();
    }
    
    init() {
        this.render();
    }
    
    render() {
        this.container.innerHTML = this.generateHTML();
        this.bindEvents();
    }
    
    generateHTML() {
        const pages = this.generatePageNumbers();
        
        return `
            <nav class="pagination" role="navigation" aria-label="Paginação">
                <button class="pagination-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                        data-page="${this.currentPage - 1}" 
                        ${this.currentPage === 1 ? 'disabled' : ''}>
                    Anterior
                </button>
                
                <div class="pagination-pages">
                    ${pages.map(page => `
                        <button class="pagination-btn ${page === this.currentPage ? 'active' : ''}" 
                                data-page="${page}">
                            ${page}
                        </button>
                    `).join('')}
                </div>
                
                <button class="pagination-btn ${this.currentPage === this.totalPages ? 'disabled' : ''}" 
                        data-page="${this.currentPage + 1}" 
                        ${this.currentPage === this.totalPages ? 'disabled' : ''}>
                    Próxima
                </button>
            </nav>
        `;
    }
    
    generatePageNumbers() {
        const pages = [];
        const maxVisible = 5;
        
        let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
        let end = Math.min(this.totalPages, start + maxVisible - 1);
        
        if (end - start + 1 < maxVisible) {
            start = Math.max(1, end - maxVisible + 1);
        }
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        
        return pages;
    }
    
    bindEvents() {
        this.container.addEventListener('click', (e) => {
            if (e.target.matches('.pagination-btn:not(.disabled)')) {
                const page = parseInt(e.target.dataset.page);
                this.goToPage(page);
            }
        });
    }
    
    goToPage(page) {
        if (page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            this.onPageChange(page);
            this.render();
        }
    }
}
```

#### 3.3 Sistema de Busca em Tempo Real
```javascript
// Sistema de busca
class SearchSystem {
    constructor(input, options = {}) {
        this.input = input;
        this.minLength = options.minLength || 3;
        this.delay = options.delay || 300;
        this.onSearch = options.onSearch || (() => {});
        this.timer = null;
        this.init();
    }
    
    init() {
        this.input.addEventListener('input', (e) => {
            clearTimeout(this.timer);
            
            if (e.target.value.length >= this.minLength) {
                this.timer = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, this.delay);
            } else if (e.target.value.length === 0) {
                this.clearSearch();
            }
        });
    }
    
    async performSearch(query) {
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
            const results = await response.json();
            this.onSearch(results);
        } catch (error) {
            console.error('Erro na busca:', error);
        }
    }
    
    clearSearch() {
        this.onSearch([]);
    }
}
```

## Estrutura de Arquivos Recomendada

```
public/
├── css/
│   ├── design-system.css
│   ├── components/
│   │   ├── buttons.css
│   │   ├── forms.css
│   │   ├── tables.css
│   │   └── notifications.css
│   ├── layouts/
│   │   ├── header.css
│   │   ├── sidebar.css
│   │   └── footer.css
│   └── responsive/
│       ├── mobile.css
│       ├── tablet.css
│       └── desktop.css
├── js/
│   ├── core/
│   │   ├── error-handler.js
│   │   ├── notification-system.js
│   │   └── loading-system.js
│   ├── components/
│   │   ├── form-validator.js
│   │   ├── advanced-filters.js
│   │   ├── pagination-system.js
│   │   └── search-system.js
│   └── pages/
│       ├── admin-dashboard.js
│       ├── admin-denuncias.js
│       └── admin-relatorios.js
└── assets/
    ├── icons/
    └── images/
```

## Métricas de Sucesso

### Acessibilidade
- [ ] 100% dos ícones com texto alternativo
- [ ] Navegação por teclado funcional
- [ ] Contraste de cores WCAG AA
- [ ] Estrutura semântica adequada

### Responsividade
- [ ] Layout funcional em dispositivos móveis
- [ ] Tabelas responsivas implementadas
- [ ] Menu mobile funcional
- [ ] Botões com tamanho adequado para touch

### Performance
- [ ] Tempo de carregamento < 3 segundos
- [ ] Erros JavaScript < 5
- [ ] Service Worker funcionando
- [ ] SSL funcionando corretamente

### Usabilidade
- [ ] Feedback visual para todas as ações
- [ ] Validação em tempo real
- [ ] Mensagens de erro claras
- [ ] Indicadores de carregamento

## Cronograma de Implementação

### Semana 1-2: Correções Críticas
- Correção de erros JavaScript
- Implementação de tratamento de erros
- Melhorias básicas de acessibilidade

### Semana 3-4: Responsividade
- Implementação de layout responsivo
- Criação de versão mobile das tabelas
- Menu mobile funcional

### Semana 5-6: Design System
- Implementação do sistema de design
- Componentes base
- Sistema de notificações

### Semana 7-8: Funcionalidades Avançadas
- Sistema de filtros
- Paginação
- Busca em tempo real

### Semana 9-10: Testes e Refinamentos
- Testes de acessibilidade
- Testes de responsividade
- Ajustes finais

## Conclusão

Esta arquitetura de melhorias foi projetada para transformar o sistema de denúncias HSFA em uma aplicação moderna, acessível e eficiente. A implementação deve seguir as fases definidas, priorizando as correções críticas e evoluindo gradualmente para funcionalidades mais avançadas.

O foco principal é garantir que o sistema seja utilizável por todos os usuários, em qualquer dispositivo, com uma experiência de usuário excepcional e funcionalidades robustas para os administradores.
