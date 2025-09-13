/**
 * HSFA Admin UI Micro-interactions
 * Módulo JavaScript para microinterações e feedback visual
 * Não altera regras de negócio, apenas melhora UX
 */

(function() {
  'use strict';

  // ============================================================================
  // UTILITÁRIOS GERAIS
  // ============================================================================

  const Utils = {
    // Debounce para performance
    debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    },

    // Throttle para scroll events
    throttle(func, limit) {
      let inThrottle;
      return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
          func.apply(context, args);
          inThrottle = true;
          setTimeout(() => inThrottle = false, limit);
        }
      };
    },

    // Detectar se o usuário prefere movimento reduzido
    prefersReducedMotion() {
      return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    },

    // Gerar ID único
    generateId() {
      return 'hsfa-' + Math.random().toString(36).substr(2, 9);
    },

    // Verificar se elemento está visível
    isElementVisible(element) {
      const rect = element.getBoundingClientRect();
      return rect.top >= 0 && rect.left >= 0 && 
             rect.bottom <= window.innerHeight && 
             rect.right <= window.innerWidth;
    }
  };

  // ============================================================================
  // SISTEMA DE TOOLTIPS
  // ============================================================================

  class TooltipManager {
    constructor() {
      this.tooltips = new Map();
      this.init();
    }

    init() {
      document.addEventListener('mouseenter', this.handleMouseEnter.bind(this), true);
      document.addEventListener('mouseleave', this.handleMouseLeave.bind(this), true);
      document.addEventListener('focus', this.handleFocus.bind(this), true);
      document.addEventListener('blur', this.handleBlur.bind(this), true);
    }

    handleMouseEnter(e) {
      const element = e.target.closest('[data-tooltip]');
      if (element && !this.tooltips.has(element)) {
        this.showTooltip(element);
      }
    }

    handleMouseLeave(e) {
      const element = e.target.closest('[data-tooltip]');
      if (element) {
        this.hideTooltip(element);
      }
    }

    handleFocus(e) {
      const element = e.target.closest('[data-tooltip]');
      if (element && !this.tooltips.has(element)) {
        this.showTooltip(element);
      }
    }

    handleBlur(e) {
      const element = e.target.closest('[data-tooltip]');
      if (element) {
        this.hideTooltip(element);
      }
    }

    showTooltip(element) {
      const text = element.getAttribute('data-tooltip');
      const position = element.getAttribute('data-tooltip-position') || 'top';
      
      if (!text) return;

      const tooltip = this.createTooltip(text, position);
      const elementRect = element.getBoundingClientRect();
      
      document.body.appendChild(tooltip);
      this.tooltips.set(element, tooltip);

      // Posicionar tooltip
      this.positionTooltip(tooltip, elementRect, position);

      // Mostrar com animação
      requestAnimationFrame(() => {
        tooltip.classList.add('show');
      });
    }

    hideTooltip(element) {
      const tooltip = this.tooltips.get(element);
      if (tooltip) {
        tooltip.classList.remove('show');
        setTimeout(() => {
          if (tooltip.parentNode) {
            tooltip.parentNode.removeChild(tooltip);
          }
          this.tooltips.delete(element);
        }, Utils.prefersReducedMotion() ? 0 : 150);
      }
    }

    createTooltip(text, position) {
      const tooltip = document.createElement('div');
      tooltip.className = `hsfa-tooltip hsfa-tooltip-${position}`;
      tooltip.textContent = text;
      tooltip.setAttribute('role', 'tooltip');
      
      const arrow = document.createElement('div');
      arrow.className = 'hsfa-tooltip-arrow';
      tooltip.appendChild(arrow);

      return tooltip;
    }

    positionTooltip(tooltip, elementRect, position) {
      const tooltipRect = tooltip.getBoundingClientRect();
      const spacing = 8;
      let top, left;

      switch (position) {
        case 'top':
          top = elementRect.top - tooltipRect.height - spacing;
          left = elementRect.left + (elementRect.width - tooltipRect.width) / 2;
          break;
        case 'bottom':
          top = elementRect.bottom + spacing;
          left = elementRect.left + (elementRect.width - tooltipRect.width) / 2;
          break;
        case 'left':
          top = elementRect.top + (elementRect.height - tooltipRect.height) / 2;
          left = elementRect.left - tooltipRect.width - spacing;
          break;
        case 'right':
          top = elementRect.top + (elementRect.height - tooltipRect.height) / 2;
          left = elementRect.right + spacing;
          break;
      }

      // Ajustar para manter dentro da viewport
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      if (left < 0) left = spacing;
      if (left + tooltipRect.width > viewportWidth) {
        left = viewportWidth - tooltipRect.width - spacing;
      }
      if (top < 0) top = spacing;
      if (top + tooltipRect.height > viewportHeight) {
        top = viewportHeight - tooltipRect.height - spacing;
      }

      tooltip.style.top = `${top + window.scrollY}px`;
      tooltip.style.left = `${left + window.scrollX}px`;
    }
  }

  // ============================================================================
  // SISTEMA DE LOADING STATES
  // ============================================================================

  class LoadingManager {
    constructor() {
      this.loadingElements = new Set();
      this.init();
    }

    init() {
      // Interceptar formulários para mostrar loading
      document.addEventListener('submit', this.handleFormSubmit.bind(this));
      
      // Interceptar botões com loading
      document.addEventListener('click', this.handleButtonClick.bind(this));
    }

    handleFormSubmit(e) {
      const form = e.target;
      const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
      
      if (submitButton && !submitButton.disabled) {
        this.setButtonLoading(submitButton);
        
        // Remover loading após um tempo (fallback)
        setTimeout(() => {
          this.removeButtonLoading(submitButton);
        }, 30000);
      }
    }

    handleButtonClick(e) {
      const button = e.target.closest('[data-loading]');
      if (button && !button.disabled) {
        this.setButtonLoading(button);
      }
    }

    setButtonLoading(button) {
      if (this.loadingElements.has(button)) return;

      const originalContent = button.innerHTML;
      const loadingText = button.getAttribute('data-loading-text') || 'Carregando...';
      
      button.setAttribute('data-original-content', originalContent);
      button.disabled = true;
      button.classList.add('loading');
      
      button.innerHTML = `
        <span class="hsfa-spinner" aria-hidden="true"></span>
        <span class="loading-text">${loadingText}</span>
      `;

      this.loadingElements.add(button);
    }

    removeButtonLoading(button) {
      if (!this.loadingElements.has(button)) return;

      const originalContent = button.getAttribute('data-original-content');
      if (originalContent) {
        button.innerHTML = originalContent;
        button.removeAttribute('data-original-content');
      }
      
      button.disabled = false;
      button.classList.remove('loading');
      this.loadingElements.delete(button);
    }

    setElementLoading(element, text = 'Carregando...') {
      const loadingOverlay = document.createElement('div');
      loadingOverlay.className = 'hsfa-loading-overlay';
      loadingOverlay.innerHTML = `
        <div class="hsfa-loading-content">
          <div class="hsfa-spinner"></div>
          <span class="loading-text">${text}</span>
        </div>
      `;

      element.style.position = 'relative';
      element.appendChild(loadingOverlay);
      element.setAttribute('data-loading', 'true');
    }

    removeElementLoading(element) {
      const overlay = element.querySelector('.hsfa-loading-overlay');
      if (overlay) {
        overlay.remove();
        element.removeAttribute('data-loading');
      }
    }
  }

  // ============================================================================
  // SISTEMA DE NOTIFICAÇÕES TOAST
  // ============================================================================

  class ToastManager {
    constructor() {
      this.container = null;
      this.toasts = [];
      this.init();
    }

    init() {
      this.createContainer();
      
      // Escutar eventos customizados
      document.addEventListener('hsfa:toast', this.handleToastEvent.bind(this));
    }

    createContainer() {
      this.container = document.createElement('div');
      this.container.className = 'toast-container';
      this.container.setAttribute('aria-live', 'polite');
      this.container.setAttribute('aria-atomic', 'true');
      document.body.appendChild(this.container);
    }

    handleToastEvent(e) {
      const { type, message, duration } = e.detail;
      this.show(message, type, duration);
    }

    show(message, type = 'info', duration = 5000) {
      const toast = this.createToast(message, type);
      this.container.appendChild(toast);
      this.toasts.push(toast);

      // Mostrar com animação
      requestAnimationFrame(() => {
        toast.classList.add('show');
      });

      // Auto-remover
      setTimeout(() => {
        this.hide(toast);
      }, duration);

      // Limitar número de toasts
      if (this.toasts.length > 5) {
        this.hide(this.toasts[0]);
      }

      return toast;
    }

    createToast(message, type) {
      const toast = document.createElement('div');
      toast.className = `hsfa-toast hsfa-toast-${type}`;
      toast.setAttribute('role', 'alert');
      
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      };

      toast.innerHTML = `
        <div class="hsfa-toast-content">
          <i class="${icons[type] || icons.info}" aria-hidden="true"></i>
          <span class="hsfa-toast-message">${message}</span>
        </div>
        <button type="button" class="hsfa-toast-close" aria-label="Fechar notificação">
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      `;

      // Adicionar evento de fechar
      const closeButton = toast.querySelector('.hsfa-toast-close');
      closeButton.addEventListener('click', () => this.hide(toast));

      return toast;
    }

    hide(toast) {
      toast.classList.remove('show');
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
        const index = this.toasts.indexOf(toast);
        if (index > -1) {
          this.toasts.splice(index, 1);
        }
      }, Utils.prefersReducedMotion() ? 0 : 300);
    }

    // Métodos de conveniência
    success(message, duration) {
      return this.show(message, 'success', duration);
    }

    error(message, duration) {
      return this.show(message, 'error', duration);
    }

    warning(message, duration) {
      return this.show(message, 'warning', duration);
    }

    info(message, duration) {
      return this.show(message, 'info', duration);
    }
  }

  // ============================================================================
  // SISTEMA DE DROPDOWN
  // ============================================================================

  class DropdownManager {
    constructor() {
      this.activeDropdown = null;
      this.init();
    }

    init() {
      document.addEventListener('click', this.handleClick.bind(this));
      document.addEventListener('keydown', this.handleKeydown.bind(this));
    }

    handleClick(e) {
      const trigger = e.target.closest('[data-dropdown-trigger]');
      
      if (trigger) {
        e.preventDefault();
        e.stopPropagation();
        this.toggle(trigger);
      } else {
        this.closeAll();
      }
    }

    handleKeydown(e) {
      if (e.key === 'Escape') {
        this.closeAll();
      }
    }

    toggle(trigger) {
      const dropdown = trigger.closest('.hsfa-dropdown');
      
      if (this.activeDropdown && this.activeDropdown !== dropdown) {
        this.close(this.activeDropdown);
      }

      if (dropdown.classList.contains('show')) {
        this.close(dropdown);
      } else {
        this.open(dropdown);
      }
    }

    open(dropdown) {
      dropdown.classList.add('show');
      this.activeDropdown = dropdown;
      
      // Focar primeiro item focável
      const firstFocusable = dropdown.querySelector('a, button, input, [tabindex]');
      if (firstFocusable) {
        firstFocusable.focus();
      }
    }

    close(dropdown) {
      dropdown.classList.remove('show');
      if (this.activeDropdown === dropdown) {
        this.activeDropdown = null;
      }
    }

    closeAll() {
      document.querySelectorAll('.hsfa-dropdown.show').forEach(dropdown => {
        this.close(dropdown);
      });
    }
  }

  // ============================================================================
  // SISTEMA DE MODAL
  // ============================================================================

  class ModalManager {
    constructor() {
      this.activeModal = null;
      this.init();
    }

    init() {
      document.addEventListener('click', this.handleClick.bind(this));
      document.addEventListener('keydown', this.handleKeydown.bind(this));
    }

    handleClick(e) {
      const trigger = e.target.closest('[data-modal-trigger]');
      const close = e.target.closest('[data-modal-close]');
      const overlay = e.target.closest('.hsfa-modal-overlay');

      if (trigger) {
        e.preventDefault();
        const modalId = trigger.getAttribute('data-modal-trigger');
        this.open(modalId);
      } else if (close || (overlay && e.target === overlay)) {
        e.preventDefault();
        this.close();
      }
    }

    handleKeydown(e) {
      if (e.key === 'Escape' && this.activeModal) {
        this.close();
      }

      // Trap focus dentro do modal
      if (e.key === 'Tab' && this.activeModal) {
        this.trapFocus(e);
      }
    }

    open(modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) return;

      // Criar overlay se não existir
      let overlay = modal.querySelector('.hsfa-modal-overlay');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'hsfa-modal-overlay';
        modal.appendChild(overlay);
      }

      // Salvar elemento focado
      this.previouslyFocused = document.activeElement;

      // Mostrar modal
      modal.style.display = 'block';
      document.body.classList.add('modal-open');
      
      requestAnimationFrame(() => {
        overlay.classList.add('show');
        modal.classList.add('show');
      });

      this.activeModal = modal;

      // Focar primeiro elemento focável
      const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (firstFocusable) {
        firstFocusable.focus();
      }
    }

    close() {
      if (!this.activeModal) return;

      const modal = this.activeModal;
      const overlay = modal.querySelector('.hsfa-modal-overlay');

      modal.classList.remove('show');
      if (overlay) {
        overlay.classList.remove('show');
      }

      setTimeout(() => {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Restaurar foco
        if (this.previouslyFocused) {
          this.previouslyFocused.focus();
        }
      }, Utils.prefersReducedMotion() ? 0 : 300);

      this.activeModal = null;
    }

    trapFocus(e) {
      const modal = this.activeModal;
      const focusableElements = modal.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      
      const firstFocusable = focusableElements[0];
      const lastFocusable = focusableElements[focusableElements.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === firstFocusable) {
          lastFocusable.focus();
          e.preventDefault();
        }
      } else {
        if (document.activeElement === lastFocusable) {
          firstFocusable.focus();
          e.preventDefault();
        }
      }
    }
  }

  // ============================================================================
  // SISTEMA DE ANIMAÇÕES DE ENTRADA
  // ============================================================================

  class AnimationManager {
    constructor() {
      this.observer = null;
      this.init();
    }

    init() {
      if (!Utils.prefersReducedMotion() && 'IntersectionObserver' in window) {
        this.createObserver();
        this.observeElements();
      }
    }

    createObserver() {
      this.observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.animateElement(entry.target);
            this.observer.unobserve(entry.target);
          }
        });
      }, {
        rootMargin: '50px',
        threshold: 0.1
      });
    }

    observeElements() {
      const elements = document.querySelectorAll('[data-animate]');
      elements.forEach(element => {
        element.classList.add('animate-hidden');
        this.observer.observe(element);
      });
    }

    animateElement(element) {
      const animation = element.getAttribute('data-animate') || 'fadeIn';
      const delay = element.getAttribute('data-animate-delay') || '0';
      
      setTimeout(() => {
        element.classList.remove('animate-hidden');
        element.classList.add(`animate-${animation}`);
      }, parseInt(delay));
    }

    refresh() {
      if (this.observer) {
        this.observeElements();
      }
    }
  }

  // ============================================================================
  // MELHORIAS DE TABELA
  // ============================================================================

  class TableEnhancer {
    constructor() {
      this.init();
    }

    init() {
      this.enhanceSort();
      this.enhanceSelection();
      this.enhanceSearch();
    }

    enhanceSort() {
      document.querySelectorAll('[data-sortable]').forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
          this.sortTable(header);
        });
        
        // Adicionar ícone de ordenação
        if (!header.querySelector('.sort-icon')) {
          const icon = document.createElement('i');
          icon.className = 'fas fa-sort sort-icon';
          icon.style.marginLeft = '0.5rem';
          header.appendChild(icon);
        }
      });
    }

    sortTable(header) {
      const table = header.closest('table');
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      const columnIndex = Array.from(header.parentNode.children).indexOf(header);
      const isAscending = !header.classList.contains('sort-asc');

      // Limpar ordenação anterior
      header.parentNode.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        const icon = th.querySelector('.sort-icon');
        if (icon) {
          icon.className = 'fas fa-sort sort-icon';
        }
      });

      // Aplicar nova ordenação
      header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
      const icon = header.querySelector('.sort-icon');
      if (icon) {
        icon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} sort-icon`;
      }

      // Ordenar linhas
      rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        let comparison = 0;
        if (!isNaN(aNum) && !isNaN(bNum)) {
          comparison = aNum - bNum;
        } else {
          comparison = aValue.localeCompare(bValue);
        }

        return isAscending ? comparison : -comparison;
      });

      // Reordenar DOM
      rows.forEach(row => tbody.appendChild(row));
    }

    enhanceSelection() {
      document.querySelectorAll('[data-select-all]').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
          const targetSelector = checkbox.getAttribute('data-select-all');
          const targets = document.querySelectorAll(targetSelector);
          
          targets.forEach(target => {
            target.checked = e.target.checked;
            this.updateRowSelection(target);
          });
        });
      });

      document.querySelectorAll('[data-selectable]').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
          this.updateRowSelection(e.target);
        });
      });
    }

    updateRowSelection(checkbox) {
      const row = checkbox.closest('tr');
      if (row) {
        row.classList.toggle('selected', checkbox.checked);
      }
    }

    enhanceSearch() {
      document.querySelectorAll('[data-table-search]').forEach(input => {
        const tableSelector = input.getAttribute('data-table-search');
        const table = document.querySelector(tableSelector);
        
        if (table) {
          const debouncedSearch = Utils.debounce((query) => {
            this.searchTable(table, query);
          }, 300);

          input.addEventListener('input', (e) => {
            debouncedSearch(e.target.value);
          });
        }
      });
    }

    searchTable(table, query) {
      const rows = table.querySelectorAll('tbody tr');
      const searchTerm = query.toLowerCase();

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm);
        row.style.display = matches ? '' : 'none';
      });
    }
  }

  // ============================================================================
  // SIDEBAR RESPONSIVA
  // ============================================================================

  class SidebarManager {
    constructor() {
      this.sidebar = document.querySelector('.admin-sidebar');
      this.toggleButton = null;
      this.init();
    }

    init() {
      this.createToggleButton();
      this.handleResize();
      
      window.addEventListener('resize', Utils.debounce(() => {
        this.handleResize();
      }, 100));
    }

    createToggleButton() {
      const topbar = document.querySelector('.admin-topbar .topbar-left');
      if (!topbar) return;

      this.toggleButton = document.createElement('button');
      this.toggleButton.className = 'hsfa-btn hsfa-btn-ghost sidebar-toggle';
      this.toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
      this.toggleButton.setAttribute('aria-label', 'Alternar menu lateral');
      this.toggleButton.style.display = 'none';

      this.toggleButton.addEventListener('click', () => {
        this.toggle();
      });

      topbar.insertBefore(this.toggleButton, topbar.firstChild);
    }

    toggle() {
      document.body.classList.toggle('sidebar-open');
    }

    handleResize() {
      if (window.innerWidth <= 1024) {
        this.toggleButton.style.display = 'flex';
        document.body.classList.remove('sidebar-open');
      } else {
        this.toggleButton.style.display = 'none';
        document.body.classList.remove('sidebar-open');
      }
    }
  }

  // ============================================================================
  // INICIALIZAÇÃO
  // ============================================================================

  // Aguardar DOM ready
  function domReady(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  // Inicializar apenas em páginas admin
  domReady(() => {
    if (!document.body.classList.contains('body-admin')) {
      return;
    }

    // Instanciar managers
    const tooltipManager = new TooltipManager();
    const loadingManager = new LoadingManager();
    const toastManager = new ToastManager();
    const dropdownManager = new DropdownManager();
    const modalManager = new ModalManager();
    const animationManager = new AnimationManager();
    const tableEnhancer = new TableEnhancer();
    const sidebarManager = new SidebarManager();

    // Adicionar CSS para componentes
    addComponentStyles();

    // Expor API global
    window.HSFA = {
      toast: toastManager,
      loading: loadingManager,
      modal: modalManager,
      animation: animationManager,
      utils: Utils
    };

    // Anunciar que o sistema está pronto
    document.dispatchEvent(new CustomEvent('hsfa:ready'));
  });

  // ============================================================================
  // ESTILOS DINÂMICOS DOS COMPONENTES
  // ============================================================================

  function addComponentStyles() {
    const styles = `
      /* Tooltip Styles */
      .hsfa-tooltip {
        position: absolute;
        z-index: 1070;
        background-color: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        max-width: 200px;
        word-wrap: break-word;
        opacity: 0;
        transform: scale(0.8);
        transition: opacity 150ms ease-in-out, transform 150ms ease-in-out;
        pointer-events: none;
      }

      .hsfa-tooltip.show {
        opacity: 1;
        transform: scale(1);
      }

      .hsfa-tooltip-arrow {
        position: absolute;
        width: 0;
        height: 0;
        border: 5px solid transparent;
      }

      .hsfa-tooltip-top .hsfa-tooltip-arrow {
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        border-top-color: rgba(0, 0, 0, 0.9);
      }

      .hsfa-tooltip-bottom .hsfa-tooltip-arrow {
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        border-bottom-color: rgba(0, 0, 0, 0.9);
      }

      .hsfa-tooltip-left .hsfa-tooltip-arrow {
        right: -10px;
        top: 50%;
        transform: translateY(-50%);
        border-left-color: rgba(0, 0, 0, 0.9);
      }

      .hsfa-tooltip-right .hsfa-tooltip-arrow {
        left: -10px;
        top: 50%;
        transform: translateY(-50%);
        border-right-color: rgba(0, 0, 0, 0.9);
      }

      /* Loading Overlay */
      .hsfa-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
      }

      .hsfa-loading-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
      }

      .hsfa-loading-content .loading-text {
        font-size: 14px;
        color: #6c757d;
      }

      /* Toast Styles */
      .toast-container {
        position: fixed;
        top: 24px;
        right: 24px;
        z-index: 1070;
        max-width: 400px;
        pointer-events: none;
      }

      .hsfa-toast {
        background-color: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        margin-bottom: 12px;
        padding: 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        transform: translateX(100%);
        transition: transform 300ms ease-in-out;
        pointer-events: auto;
      }

      .hsfa-toast.show {
        transform: translateX(0);
      }

      .hsfa-toast-content {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .hsfa-toast-message {
        font-size: 14px;
        line-height: 1.4;
      }

      .hsfa-toast-close {
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background-color 150ms ease-in-out;
      }

      .hsfa-toast-close:hover {
        background-color: #f8f9fa;
      }

      .hsfa-toast-success {
        border-left: 4px solid #198754;
      }

      .hsfa-toast-success i {
        color: #198754;
      }

      .hsfa-toast-error {
        border-left: 4px solid #dc3545;
      }

      .hsfa-toast-error i {
        color: #dc3545;
      }

      .hsfa-toast-warning {
        border-left: 4px solid #ffc107;
      }

      .hsfa-toast-warning i {
        color: #ffc107;
      }

      .hsfa-toast-info {
        border-left: 4px solid #0dcaf0;
      }

      .hsfa-toast-info i {
        color: #0dcaf0;
      }

      /* Animation Classes */
      .animate-hidden {
        opacity: 0;
        transform: translateY(20px);
      }

      .animate-fadeIn {
        animation: fadeIn 600ms ease-out forwards;
      }

      .animate-slideUp {
        animation: slideUp 600ms ease-out forwards;
      }

      .animate-slideLeft {
        animation: slideLeft 600ms ease-out forwards;
      }

      @keyframes fadeIn {
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes slideUp {
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes slideLeft {
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }

      /* Table Selection */
      .hsfa-table tbody tr.selected {
        background-color: rgba(96, 176, 204, 0.1);
      }

      /* Modal Body Scroll */
      body.modal-open {
        overflow: hidden;
      }

      /* Sidebar Toggle */
      .sidebar-toggle {
        margin-right: 16px;
      }

      @media (max-width: 1024px) {
        body.body-admin.sidebar-open::before {
          content: '';
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 1020;
        }
      }

      /* Reduced Motion */
      @media (prefers-reduced-motion: reduce) {
        .hsfa-tooltip,
        .hsfa-toast,
        .animate-fadeIn,
        .animate-slideUp,
        .animate-slideLeft {
          animation: none !important;
          transition: none !important;
        }

        .animate-hidden {
          opacity: 1;
          transform: none;
        }
      }
    `;

    const styleSheet = document.createElement('style');
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
  }

})();
