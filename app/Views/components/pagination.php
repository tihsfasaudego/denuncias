<?php
/**
 * Componente de paginação reutilizável
 * Usado para paginar listagens em todo o sistema
 */

if (!isset($paginacao) || empty($paginacao)) {
    return;
}

$total = $paginacao['total'];
$pages = $paginacao['pages'];
$currentPage = $paginacao['current_page'];
$limit = $paginacao['limit'];

if ($pages <= 1) {
    return; // Não mostrar paginação se há apenas uma página
}

// Construir URL base preservando parâmetros
$urlParams = $_GET;
unset($urlParams['page']); // Remove page para adicionar dinamicamente
$baseUrl = $_SERVER['REQUEST_URI'];
if (strpos($baseUrl, '?') !== false) {
    $baseUrl = substr($baseUrl, 0, strpos($baseUrl, '?'));
}
$queryString = http_build_query($urlParams);
$baseUrl .= $queryString ? '?' . $queryString . '&' : '?';

// Calcular range de páginas a mostrar
$range = 5; // Mostrar 5 páginas ao redor da atual
$start = max(1, $currentPage - floor($range / 2));
$end = min($pages, $start + $range - 1);

// Ajustar start se end está no limite
if ($end - $start < $range - 1) {
    $start = max(1, $end - $range + 1);
}
?>

<nav aria-label="Navegação de páginas" class="pagination-wrapper">
    <div class="pagination-info">
        <span class="pagination-text">
            Mostrando <?= min($limit, $total - (($currentPage - 1) * $limit)) ?> 
            de <?= number_format($total) ?> 
            <?= $total === 1 ? 'resultado' : 'resultados' ?>
        </span>
    </div>
    
    <ul class="pagination pagination-modern">
        <!-- Primeira página -->
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>page=1" aria-label="Primeira página">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Página anterior -->
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>page=<?= $currentPage - 1 ?>" aria-label="Página anterior">
                    <i class="fas fa-angle-left"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Páginas numeradas -->
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                <?php if ($i === $currentPage): ?>
                    <span class="page-link current" aria-current="page">
                        <?= $i ?>
                    </span>
                <?php else: ?>
                    <a class="page-link" href="<?= $baseUrl ?>page=<?= $i ?>">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>
        
        <!-- Próxima página -->
        <?php if ($currentPage < $pages): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>page=<?= $currentPage + 1 ?>" aria-label="Próxima página">
                    <i class="fas fa-angle-right"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Última página -->
        <?php if ($currentPage < $pages): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>page=<?= $pages ?>" aria-label="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <!-- Seletor de itens por página -->
    <div class="pagination-controls">
        <select class="form-select form-select-sm limit-selector" onchange="changePaginationLimit(this.value)">
            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 por página</option>
            <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20 por página</option>
            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 por página</option>
            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 por página</option>
        </select>
    </div>
</nav>

<style>
.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin: 1.5rem 0;
    padding: 1rem;
    background: var(--bg-card);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.pagination-info {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.pagination-modern {
    display: flex;
    gap: 0.25rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.pagination-modern .page-item {
    display: block;
}

.pagination-modern .page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0.5rem;
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-color);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.pagination-modern .page-link:hover {
    background: var(--hsfa-primary);
    color: white;
    border-color: var(--hsfa-primary);
    transform: translateY(-1px);
}

.pagination-modern .page-item.active .page-link,
.pagination-modern .page-link.current {
    background: var(--hsfa-primary);
    color: white;
    border-color: var(--hsfa-primary);
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.limit-selector {
    min-width: 140px;
}

/* Responsivo */
@media (max-width: 768px) {
    .pagination-wrapper {
        flex-direction: column;
        text-align: center;
    }
    
    .pagination-modern {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .pagination-modern .page-link {
        min-width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
}

/* Modo escuro */
[data-theme="dark"] .pagination-wrapper {
    background: var(--bg-card);
    border-color: var(--border-color);
}

[data-theme="dark"] .pagination-modern .page-link {
    background: var(--bg-card);
    border-color: var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .pagination-modern .page-link:hover {
    background: var(--hsfa-primary);
    border-color: var(--hsfa-primary);
}
</style>

<script>
function changePaginationLimit(newLimit) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('limit', newLimit);
    urlParams.set('page', '1'); // Reset para primeira página
    window.location.search = urlParams.toString();
}

// Adicionar indicador de carregamento ao clicar em links de paginação
document.addEventListener('DOMContentLoaded', function() {
    const paginationLinks = document.querySelectorAll('.pagination-modern .page-link');
    
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Apenas se não for a página atual
            if (!this.classList.contains('current')) {
                // Adicionar indicador de carregamento
                const icon = this.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-spinner fa-spin';
                } else {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
                
                // Desabilitar temporariamente o link
                this.style.pointerEvents = 'none';
            }
        });
    });
});
</script>
