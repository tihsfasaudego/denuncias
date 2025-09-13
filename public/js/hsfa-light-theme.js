/**
 * HSFA - Sistema de Tema Claro Forçado
 * Remove completamente o suporte ao dark mode e força tema claro
 */

(function() {
    'use strict';
    
    // ===== NEUTRALIZAÇÃO IMEDIATA DO DARK MODE =====
    function forceLightTheme() {
        // Remove qualquer classe ou atributo de dark mode
        document.documentElement.classList.remove('dark', 'theme-dark');
        document.documentElement.setAttribute('data-theme', 'light');
        document.body.classList.remove('dark', 'theme-dark');
        
        // Define color-scheme como light
        document.documentElement.style.colorScheme = 'light';
        
        // Remove preferências antigas de localStorage
        try {
            localStorage.removeItem('theme');
            localStorage.removeItem('hsfa-theme');
            localStorage.removeItem('darkMode');
            localStorage.removeItem('colorScheme');
        } catch (e) {
            // Ignorar erros de localStorage (modo privado, etc.)
        }
        
        // Atualizar meta theme-color para dispositivos móveis
        updateThemeColor();
    }
    
    // ===== ATUALIZAÇÃO DO META THEME-COLOR =====
    function updateThemeColor() {
        let metaTheme = document.querySelector('meta[name="theme-color"]');
        if (!metaTheme) {
            metaTheme = document.createElement('meta');
            metaTheme.name = 'theme-color';
            document.head.appendChild(metaTheme);
        }
        
        // Sempre usar a cor primária HSFA
        metaTheme.content = '#01717B';
    }
    
    // ===== NEUTRALIZAÇÃO DE LISTENERS DE TEMA =====
    function neutralizeThemeListeners() {
        // Remover listeners de mudança de color-scheme do sistema
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Sobrescrever qualquer listener existente
        darkModeQuery.onchange = null;
        
        // Adicionar listener que força tema claro
        darkModeQuery.addListener = function() {};
        darkModeQuery.addEventListener = function() {};
        
        // Neutralizar qualquer função de toggle de tema
        if (window.toggleTheme) {
            window.toggleTheme = function() {
                console.warn('HSFA: Alternância de tema desabilitada - usando apenas tema claro');
            };
        }
        
        if (window.setTheme) {
            window.setTheme = function(theme) {
                console.warn('HSFA: Forçando tema claro, ignorando:', theme);
                forceLightTheme();
            };
        }
    }
    
    // ===== REMOÇÃO DE BOTÕES DE ALTERNÂNCIA =====
    function removeThemeToggles() {
        // Remove botões de alternância de tema existentes
        const themeToggles = document.querySelectorAll(
            '#theme-toggle, .theme-toggle, .dark-mode-toggle, [data-theme-toggle]'
        );
        
        themeToggles.forEach(toggle => {
            toggle.remove();
        });
        
        // Observar e remover novos botões que possam ser criados dinamicamente
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const newToggles = node.querySelectorAll 
                                ? node.querySelectorAll('#theme-toggle, .theme-toggle, .dark-mode-toggle')
                                : [];
                            
                            newToggles.forEach(toggle => toggle.remove());
                            
                            if (node.id === 'theme-toggle' || 
                                node.classList.contains('theme-toggle') ||
                                node.classList.contains('dark-mode-toggle')) {
                                node.remove();
                            }
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // ===== INTERCEPTAÇÃO DE MUDANÇAS DE TEMA =====
    function interceptThemeChanges() {
        // Interceptar tentativas de mudança via setAttribute
        const originalSetAttribute = Element.prototype.setAttribute;
        Element.prototype.setAttribute = function(name, value) {
            if (name === 'data-theme' && value === 'dark') {
                console.warn('HSFA: Bloqueando mudança para tema escuro');
                return originalSetAttribute.call(this, name, 'light');
            }
            return originalSetAttribute.call(this, name, value);
        };
        
        // Interceptar tentativas de mudança via classList
        const originalAdd = DOMTokenList.prototype.add;
        DOMTokenList.prototype.add = function(...tokens) {
            const filteredTokens = tokens.filter(token => 
                !['dark', 'theme-dark', 'dark-mode'].includes(token)
            );
            if (filteredTokens.length !== tokens.length) {
                console.warn('HSFA: Bloqueando classes de tema escuro:', tokens);
            }
            return originalAdd.apply(this, filteredTokens);
        };
    }
    
    // ===== INICIALIZAÇÃO =====
    function initializeLightTheme() {
        // Forçar tema claro imediatamente
        forceLightTheme();
        
        // Neutralizar listeners e funções de tema
        neutralizeThemeListeners();
        
        // Interceptar mudanças futuras
        interceptThemeChanges();
        
        // Aguardar DOM ready para remover toggles
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', removeThemeToggles);
        } else {
            removeThemeToggles();
        }
        
        // Forçar tema claro novamente após um pequeno delay
        // (caso algum script tente modificar após inicialização)
        setTimeout(forceLightTheme, 100);
        setTimeout(forceLightTheme, 500);
        setTimeout(forceLightTheme, 1000);
        
        console.log('HSFA: Tema claro forçado - Dark mode completamente desabilitado');
    }
    
    // ===== EXECUÇÃO IMEDIATA =====
    
    // Executar imediatamente (antes mesmo do DOM estar pronto)
    forceLightTheme();
    
    // Executar novamente quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLightTheme);
    } else {
        initializeLightTheme();
    }
    
    // Executar quando a janela carregar completamente
    window.addEventListener('load', forceLightTheme);
    
    // ===== MONITORAMENTO CONTÍNUO =====
    
    // Verificar e corrigir tema a cada 2 segundos (failsafe)
    setInterval(function() {
        if (document.documentElement.getAttribute('data-theme') !== 'light' ||
            document.documentElement.classList.contains('dark') ||
            document.body.classList.contains('dark')) {
            console.warn('HSFA: Detectada tentativa de tema escuro - corrigindo...');
            forceLightTheme();
        }
    }, 2000);
    
    // ===== API PÚBLICA =====
    
    // Expor apenas função de força tema claro
    window.HSFA = window.HSFA || {};
    window.HSFA.forceLightTheme = forceLightTheme;
    window.HSFA.updateThemeColor = updateThemeColor;
    
})();
