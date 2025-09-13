<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" style="color-scheme: light !important;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#01717B">
    <meta name="color-scheme" content="light">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Canal de Denúncias - Hospital São Francisco de Assis'); ?></title>
    
    <!-- Preload logo for better performance -->
    <link rel="preload" href="/css/images/logo1.png" as="image" type="image/png">
    
    <?php 
    // Carregar helpers de assets
    require_once __DIR__ . '/../../Helpers/AssetHelpers.php';
    
    // CSS Crítico inline
    echo critical_css();
    ?>
    
    <!-- Pré-carregar recursos importantes -->
    <?= preload_asset('/css/images/logo1.png', 'image') ?>
    <?= preload_asset('https://fonts.gstatic.com/s/inter/v12/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiA.woff2', 'font') ?>
    
    <!-- CSS Externa (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts HSFA - Montserrat + Open Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <?php
    // CSS do sistema - TEMA CLARO HSFA FORÇADO
    $systemCSS = [
        'css/hsfa-light-theme.css',  // NOVO: Tema claro forçado HSFA
        'css/hsfa-theme.css',
        'css/design-system.css',     // NOVO: Sistema de design consistente
        'css/styles.css',
        'css/mobile-responsive.css', // NOVO: Responsividade mobile
        'css/hsfa-override.css'      // NOVO: Override completo do dark mode
    ];
    
    if (isset($isAdminPage) && $isAdminPage === true) {
        $systemCSS[] = 'css/admin.css';
        $systemCSS[] = 'css/admin-theme.css'; // NOVO: Tema administrativo unificado
    }
    
    if (isset($currentPage) && $currentPage === 'relatorios') {
        $systemCSS[] = 'css/print.css';
    }
    
    // Escolher entre crítico inline + defer ou combinado
    if (Environment::isProduction()) {
        echo defer_css($systemCSS);
    } else {
        echo css($systemCSS, 'system');
    }
    ?>
    
    <!-- Choices.js (defer para não bloquear) -->
    <?= defer_css(['https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css']) ?>
</head>
<body class="<?= isset($bodyClass) ? $bodyClass : '' ?><?= isset($isAdminPage) && $isAdminPage === true ? ' body-admin' : '' ?>">
    <!-- Theme toggle button -->
    <button id="theme-toggle" class="theme-toggle" aria-label="Alternar tema">
        <i class="fas fa-moon dark-icon"></i>
        <i class="fas fa-sun light-icon"></i>
    </button>
    
    <?php if (isset($isAdminPage) && $isAdminPage === true): ?>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-logo">
                <img src="/css/images/logo1.png" alt="HSFA Admin" width="32" height="32">
                <h1>HSFA Admin</h1>
            </div>
            
            <nav class="sidebar-nav">
                <?php 
                $currentPage = $currentPage ?? ''; 
                require_once __DIR__ . '/../admin/menu.php'; 
                ?>
            </nav>
        </aside>
        
        <!-- Conteúdo Principal -->
        <div class="admin-main">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <h1 class="topbar-title"><?= $pageTitle ?? 'Painel Administrativo' ?></h1>
                </div>
                
                <div class="topbar-right">
                    <!-- Notificações -->
                    <button class="hsfa-btn hsfa-btn-ghost" data-tooltip="Notificações">
                        <i class="fas fa-bell"></i>
                        <span class="nav-badge">3</span>
                    </button>
                    
                    <!-- Menu do usuário -->
                    <div class="user-menu hsfa-dropdown">
                        <button class="user-menu-trigger" data-dropdown-trigger>
                            <div class="user-avatar">
                                <?= strtoupper(substr($_SESSION['admin_nome'] ?? 'A', 0, 1)) ?>
                            </div>
                            <span class="font-medium"><?= $_SESSION['admin_nome'] ?? 'Administrador' ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <div class="hsfa-dropdown-menu">
                            <a href="/admin/perfil" class="hsfa-dropdown-item">
                                <i class="fas fa-user me-2"></i>Meu Perfil
                            </a>
                            <a href="/admin/configuracoes" class="hsfa-dropdown-item">
                                <i class="fas fa-cog me-2"></i>Configurações
                            </a>
                            <div class="border-t"></div>
                            <a href="/admin/logout" class="hsfa-dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Alertas -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="hsfa-alert hsfa-alert-success" role="alert" data-animate="slideDown">
                <i class="hsfa-alert-icon fas fa-check-circle"></i>
                <div class="hsfa-alert-content">
                    <p class="hsfa-alert-message"><?= $_SESSION['success'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="hsfa-alert hsfa-alert-danger" role="alert" data-animate="slideDown">
                <i class="hsfa-alert-icon fas fa-exclamation-circle"></i>
                <div class="hsfa-alert-content">
                    <p class="hsfa-alert-message"><?= $_SESSION['error'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Área de Conteúdo -->
            <div class="admin-content">
                <?= $content ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <header class="hsfa-header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo-link">
                    <img src="/css/images/logo1.png" alt="Hospital São Francisco de Assis" class="logo" width="180" height="60">
                </a>
                <nav class="main-nav">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="nav-list">
                            <li class="nav-item">
                                <a class="nav-link" href="/">Início</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/denuncia/criar">Nova Denúncia</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/denuncia/consultar">Consultar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/login">Área Administrativa</a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
    </header>
    
    <main>
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert hsfa-alert-success alert-dismissible fade show container mt-3" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert hsfa-alert-danger alert-dismissible fade show container mt-3" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?= $content ?>
    </main>
    
    <footer class="hsfa-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Links Principais</h3>
                    <ul>
                        <li><a href="/">Início</a></li>
                        <li><a href="/denuncia/criar">Nova Denúncia</a></li>
                        <li><a href="/denuncia/consultar">Consultar</a></li>
                        <li><a href="/admin/login">Área Administrativa</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contato</h3>
                    <ul>
                        <li><i class="fas fa-phone"></i> (xx) xxxx-xxxx</li>
                        <li><i class="fas fa-envelope"></i> contato@hsfasaude.com.br</li>
                        <li><i class="fas fa-map-marker-alt"></i> Endereço do Hospital</li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Redes Sociais</h3>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Horário de Atendimento</h3>
                    <p>Segunda a Sexta: 08h às 18h</p>
                    <p>Sábado: 08h às 12h</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Hospital São Francisco de Assis - Todos os direitos reservados</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- JavaScript HSFA - Tema Claro Forçado -->
    <?php
    // Script crítico para forçar tema claro imediatamente
    $hsfaLightScript = "
        (function() {
            // Força tema claro imediatamente
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.style.colorScheme = 'light';
            document.documentElement.classList.remove('dark');
            
            // Remove preferências antigas
            try {
                localStorage.removeItem('theme');
                localStorage.removeItem('hsfa-theme');
            } catch(e) {}
        })();
    ";
    echo inline_js($hsfaLightScript);
    ?>
    
    <!-- Scripts externos essenciais -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <?php
    // Scripts do sistema - HSFA TEMA CLARO
    $systemJS = [
        'js/hsfa-light-theme.js',  // NOVO: Neutralização completa do dark mode
        'js/hsfa-scripts.js',      // NOVO: Scripts principais sem dark mode
        'js/notification-system.js', // NOVO: Sistema de notificações
        'js/scripts.js'            // Scripts principais com melhorias de acessibilidade
    ];
    
    if (isset($isAdminPage) && $isAdminPage === true) {
        $systemJS[] = 'js/admin.js';
        $systemJS[] = 'js/ui-micro.js'; // NOVO: Microinterações administrativas
    }
    
    if (isset($currentPage) && $currentPage === 'relatorios') {
        // Chart.js carregado apenas quando necessário
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>';
    }
    
    // Carregar scripts do sistema
    echo js($systemJS, 'system');
    ?>
    
    <!-- Choices.js carregado de forma assíncrona -->
    <?= async_js(['https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js']) ?>
    
    <!-- Script de tema (depois que elementos existem) -->
    <?php
    $themeToggleScript = "
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('theme-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                    
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            }
        });
    ";
    echo inline_js($themeToggleScript);
    ?>
    
    <!-- Scripts adicionais -->
    <?php if (isset($scripts)): ?>
        <?= $scripts ?>
    <?php endif; ?>
    
    <!-- Service Worker para cache -->
    <?php if (Environment::isProduction()): ?>
        <?= register_service_worker() ?>
    <?php endif; ?>
</body>
</html> 