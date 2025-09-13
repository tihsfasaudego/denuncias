/**
 * JavaScript para o painel administrativo - HSFA
 */
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para visibilidade da senha
    const togglePassword = document.getElementById('togglePassword');
    const senhaInput = document.getElementById('senha');

    if (togglePassword && senhaInput) {
        togglePassword.addEventListener('click', function() {
            const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            senhaInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    }

    // Validação de formulários
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Configuração de dropdowns do Bootstrap
    const dropdownItems = document.querySelectorAll('.dropdown-toggle');
    Array.from(dropdownItems).forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('show');
            
            const submenu = document.getElementById(this.getAttribute('href').replace('#', ''));
            if (submenu) {
                submenu.classList.toggle('show');
            }
        });
    });

    // Menu de navegação lateral toggle para desktop e mobile
    const toggleMenu = document.getElementById('toggleMenu');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    
    // Verificar preferência salva no localStorage
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    
    // Aplicar estado inicial com base na preferência salva
    if (sidebarState === 'true') {
        sidebar.classList.add('collapsed');
        content.classList.add('expanded');
    }
    
    // Configurar toggle do menu
    if (toggleMenu && sidebar && content) {
        toggleMenu.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
            
            // Salvar estado no localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Ajustar ícone do botão com animação
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transition = 'transform 0.3s ease';
                
                if (isCollapsed) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-angles-right');
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    icon.classList.remove('fa-angles-right');
                    icon.classList.add('fa-bars');
                    icon.style.transform = 'rotate(0)';
                }
            }
        });
        
        // Ajustar ícone do botão no carregamento inicial
        const icon = toggleMenu.querySelector('i');
        if (icon && sidebar.classList.contains('collapsed')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-angles-right');
            icon.style.transform = 'rotate(180deg)';
        }
    }
    
    // Adicionar data-title para itens do menu (tooltips personalizados)
    const menuItems = document.querySelectorAll('.sidebar .list-unstyled li a');
    
    menuItems.forEach(item => {
        const span = item.querySelector('span');
        if (span) {
            const text = span.textContent.trim();
            item.setAttribute('data-title', text);
        }
    });
    
    // Inicializar componentes Choices.js para selects melhorados
    const choicesElements = document.querySelectorAll('.choices-select');
    if (choicesElements.length > 0 && typeof Choices !== 'undefined') {
        choicesElements.forEach(element => {
            new Choices(element, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                allowHTML: false,
                classNames: {
                    containerOuter: 'choices choices-custom',
                }
            });
        });
    }

    // Inicializar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined') {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Auto-fechar alertas após 5 segundos
    const autoCloseAlerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    autoCloseAlerts.forEach(alert => {
        setTimeout(() => {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            } else {
                alert.classList.remove('show');
                setTimeout(() => {
                    alert.remove();
                }, 150);
            }
        }, 5000);
    });
    
    // Adicionar efeito de hover nos cards do dashboard
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    dashboardCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = '0 12px 28px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });
    
    // Melhorar a visualização de tabelas
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        // Adicionar classe para tabelas responsivas
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.classList.add('table-responsive');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        
        // Adicionar efeito de hover nas linhas
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--hsfa-bg-soft)';
                this.style.transition = 'background-color 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    });

    // Adicionar scrollbar customizada para elementos com overflow
    const scrollableElements = document.querySelectorAll('.sidebar, .admin-main, .modal-body');
    scrollableElements.forEach(element => {
        if (element.scrollHeight > element.clientHeight) {
            element.classList.add('custom-scrollbar');
        }
    });
    
    // Animar elementos da página ao carregar
    const fadeInElements = document.querySelectorAll('.dashboard-card, .card');
    fadeInElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 100 + (index * 50));
    });
}); 