<?php
// Garantir que o arquivo Auth.php foi incluído
require_once __DIR__ . '/../../Core/Auth.php';
$auth = Auth::getInstance();

// Definir a página atual
$currentPage = $currentPage ?? '';
?>

<!-- Navigation sections com novo design -->
<div class="nav-section">
    <h3 class="nav-section-title">Principal</h3>
    
    <div class="nav-item">
        <a href="/admin/dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" data-tooltip="Visão geral do sistema">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <h3 class="nav-section-title">Denúncias</h3>
    
    <div class="nav-item">
        <a href="/admin/denuncias" class="nav-link <?= $currentPage === 'denuncias' ? 'active' : '' ?>" data-tooltip="Gerenciar todas as denúncias">
            <i class="fas fa-list"></i>
            <span class="nav-link-text">Todas</span>
        </a>
    </div>
    
    <div class="nav-item">
        <a href="/admin/denuncias/pendentes" class="nav-link" data-tooltip="Denúncias aguardando análise">
            <i class="fas fa-clock"></i>
            <span class="nav-link-text">Pendentes</span>
            <span class="nav-badge">5</span>
        </a>
    </div>
    
    <div class="nav-item">
        <a href="/admin/denuncias/em-analise" class="nav-link" data-tooltip="Denúncias em análise">
            <i class="fas fa-search"></i>
            <span class="nav-link-text">Em Análise</span>
            <span class="nav-badge">3</span>
        </a>
    </div>
    
    <div class="nav-item">
        <a href="/admin/denuncias/em-investigacao" class="nav-link" data-tooltip="Denúncias em investigação">
            <i class="fas fa-search-plus"></i>
            <span class="nav-link-text">Investigação</span>
            <span class="nav-badge">2</span>
        </a>
    </div>
    
    <div class="nav-item">
        <a href="/admin/denuncias/concluidas" class="nav-link" data-tooltip="Denúncias finalizadas">
            <i class="fas fa-check-circle"></i>
            <span class="nav-link-text">Concluídas</span>
        </a>
    </div>
    
    <div class="nav-item">
        <a href="/admin/denuncias/arquivadas" class="nav-link" data-tooltip="Denúncias arquivadas">
            <i class="fas fa-archive"></i>
            <span class="nav-link-text">Arquivadas</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <h3 class="nav-section-title">Análise</h3>
    
    <div class="nav-item">
        <a href="/admin/relatorios" class="nav-link <?= $currentPage === 'relatorios' ? 'active' : '' ?>" data-tooltip="Relatórios e estatísticas">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-link-text">Relatórios</span>
        </a>
    </div>
</div>

<div class="nav-section">
    <h3 class="nav-section-title">Sistema</h3>
    
    <div class="nav-item">
        <a href="/admin/usuarios" class="nav-link <?= $currentPage === 'usuarios' ? 'active' : '' ?>" data-tooltip="Gerenciar usuários do sistema">
            <i class="fas fa-users"></i>
            <span class="nav-link-text">Usuários</span>
        </a>
    </div>
    
    <div class="nav-item">
        <a href="/admin/configuracoes" class="nav-link <?= $currentPage === 'configuracoes' ? 'active' : '' ?>" data-tooltip="Configurações do sistema">
            <i class="fas fa-cogs"></i>
            <span class="nav-link-text">Configurações</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar dropdowns Bootstrap
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.forEach(function(element) {
        element.addEventListener('click', function(event) {
            event.preventDefault();
            var targetId = this.getAttribute('href');
            var targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.classList.toggle('show');
            }
        });
    });
});
</script> 