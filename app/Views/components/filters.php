<?php
/**
 * Componente de filtros avançados para listagens
 * Permite filtrar denúncias por status, data, categoria, etc.
 */

// Parâmetros atuais
$currentFilters = [
    'status' => $_GET['status'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'categoria' => $_GET['categoria'] ?? '',
    'responsavel' => $_GET['responsavel'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Opções de status
$statusOptions = [
    '' => 'Todos os status',
    'Pendente' => 'Pendente',
    'Em Análise' => 'Em Análise',
    'Em Investigação' => 'Em Investigação',
    'Concluída' => 'Concluída',
    'Arquivada' => 'Arquivada'
];

// URL base para reset
$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
?>

<div class="filters-container">
    <div class="filters-header">
        <h5 class="filters-title">
            <i class="fas fa-filter me-2"></i>
            Filtros
        </h5>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleFilters()">
            <i class="fas fa-chevron-down" id="filter-toggle-icon"></i>
        </button>
    </div>
    
    <div class="filters-content" id="filters-content">
        <form method="GET" class="filters-form" id="filters-form">
            <div class="row g-3">
                <!-- Busca textual -->
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="search" 
                               name="search" 
                               value="<?= htmlspecialchars($currentFilters['search']) ?>"
                               placeholder="Protocolo, descrição..."
                               autocomplete="off">
                    </div>
                </div>
                
                <!-- Status -->
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $currentFilters['status'] === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Data início -->
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="date" 
                           class="form-control" 
                           id="data_inicio" 
                           name="data_inicio" 
                           value="<?= htmlspecialchars($currentFilters['data_inicio']) ?>">
                </div>
                
                <!-- Data fim -->
                <div class="col-md-2">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="date" 
                           class="form-control" 
                           id="data_fim" 
                           name="data_fim" 
                           value="<?= htmlspecialchars($currentFilters['data_fim']) ?>">
                </div>
                
                <!-- Botões de ação -->
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="<?= $baseUrl ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Filtros avançados (ocultos por padrão) -->
            <div class="advanced-filters mt-3" id="advanced-filters" style="display: none;">
                <div class="row g-3">
                    <!-- Categoria (se disponível) -->
                    <div class="col-md-3">
                        <label for="categoria" class="form-label">Categoria</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Todas as categorias</option>
                            <!-- Categorias serão carregadas via AJAX -->
                        </select>
                    </div>
                    
                    <!-- Responsável -->
                    <div class="col-md-3">
                        <label for="responsavel" class="form-label">Responsável</label>
                        <select class="form-select" id="responsavel" name="responsavel">
                            <option value="">Todos os responsáveis</option>
                            <!-- Responsáveis serão carregados via AJAX -->
                        </select>
                    </div>
                    
                    <!-- Período predefinido -->
                    <div class="col-md-3">
                        <label for="periodo" class="form-label">Período</label>
                        <select class="form-select" id="periodo" onchange="setPredefinedPeriod(this.value)">
                            <option value="">Personalizado</option>
                            <option value="hoje">Hoje</option>
                            <option value="semana">Esta semana</option>
                            <option value="mes">Este mês</option>
                            <option value="trimestre">Este trimestre</option>
                            <option value="ano">Este ano</option>
                        </select>
                    </div>
                    
                    <!-- Ordenação -->
                    <div class="col-md-3">
                        <label for="ordenacao" class="form-label">Ordenar por</label>
                        <select class="form-select" id="ordenacao" name="ordenacao">
                            <option value="data_desc">Data (mais recente)</option>
                            <option value="data_asc">Data (mais antiga)</option>
                            <option value="status_asc">Status (A-Z)</option>
                            <option value="protocolo_asc">Protocolo (A-Z)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-link btn-sm" onclick="toggleAdvancedFilters()">
                        <i class="fas fa-cog me-1"></i>
                        Filtros avançados
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Filtros ativos -->
    <?php if (array_filter($currentFilters)): ?>
    <div class="active-filters">
        <div class="active-filters-header">
            <span class="text-muted">Filtros ativos:</span>
        </div>
        <div class="active-filters-list">
            <?php foreach ($currentFilters as $key => $value): ?>
                <?php if ($value): ?>
                    <span class="filter-tag">
                        <?php
                        $label = match($key) {
                            'status' => "Status: {$value}",
                            'data_inicio' => "Início: " . date('d/m/Y', strtotime($value)),
                            'data_fim' => "Fim: " . date('d/m/Y', strtotime($value)),
                            'search' => "Busca: {$value}",
                            default => "{$key}: {$value}"
                        };
                        echo htmlspecialchars($label);
                        ?>
                        <button type="button" class="filter-remove" onclick="removeFilter('<?= $key ?>')">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.filters-container {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.filters-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: var(--bg-light);
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
}

.filters-title {
    margin: 0;
    color: var(--text-color);
    font-weight: 600;
}

.filters-content {
    padding: 1rem;
    transition: all 0.3s ease;
}

.filters-content.collapsed {
    display: none;
}

.filters-form .btn {
    height: 38px;
}

.advanced-filters {
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
}

.active-filters {
    padding: 1rem;
    background: var(--bg-light);
    border-top: 1px solid var(--border-color);
}

.active-filters-header {
    margin-bottom: 0.5rem;
}

.active-filters-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    background: var(--hsfa-primary);
    color: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.filter-remove {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.filter-remove:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Responsivo */
@media (max-width: 768px) {
    .filters-form .col-md-1 {
        text-align: center;
    }
    
    .filters-form .d-flex {
        flex-direction: row;
        justify-content: center;
    }
}
</style>

<script>
// Estado dos filtros
let filtersExpanded = true;
let advancedFiltersVisible = false;

function toggleFilters() {
    const content = document.getElementById('filters-content');
    const icon = document.getElementById('filter-toggle-icon');
    
    filtersExpanded = !filtersExpanded;
    
    if (filtersExpanded) {
        content.style.display = 'block';
        icon.className = 'fas fa-chevron-down';
    } else {
        content.style.display = 'none';
        icon.className = 'fas fa-chevron-right';
    }
}

function toggleAdvancedFilters() {
    const advanced = document.getElementById('advanced-filters');
    advancedFiltersVisible = !advancedFiltersVisible;
    
    if (advancedFiltersVisible) {
        advanced.style.display = 'block';
    } else {
        advanced.style.display = 'none';
    }
}

function removeFilter(filterName) {
    const url = new URL(window.location);
    url.searchParams.delete(filterName);
    url.searchParams.set('page', '1'); // Reset para primeira página
    window.location.href = url.toString();
}

function setPredefinedPeriod(period) {
    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');
    const hoje = new Date();
    
    switch(period) {
        case 'hoje':
            const hojeFmt = hoje.toISOString().split('T')[0];
            dataInicio.value = hojeFmt;
            dataFim.value = hojeFmt;
            break;
            
        case 'semana':
            const inicioSemana = new Date(hoje);
            inicioSemana.setDate(hoje.getDate() - hoje.getDay());
            dataInicio.value = inicioSemana.toISOString().split('T')[0];
            dataFim.value = hoje.toISOString().split('T')[0];
            break;
            
        case 'mes':
            const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
            dataInicio.value = inicioMes.toISOString().split('T')[0];
            dataFim.value = hoje.toISOString().split('T')[0];
            break;
            
        case 'trimestre':
            const trimestre = Math.floor(hoje.getMonth() / 3);
            const inicioTrimestre = new Date(hoje.getFullYear(), trimestre * 3, 1);
            dataInicio.value = inicioTrimestre.toISOString().split('T')[0];
            dataFim.value = hoje.toISOString().split('T')[0];
            break;
            
        case 'ano':
            const inicioAno = new Date(hoje.getFullYear(), 0, 1);
            dataInicio.value = inicioAno.toISOString().split('T')[0];
            dataFim.value = hoje.toISOString().split('T')[0];
            break;
    }
}

// Auto-submit do formulário com debounce
let searchTimeout;
document.getElementById('search').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filters-form').submit();
    }, 500);
});

// Carregar opções via AJAX (implementar conforme necessário)
document.addEventListener('DOMContentLoaded', function() {
    // Carregar categorias
    loadFilterOptions('categoria', '/api/categorias');
    
    // Carregar responsáveis
    loadFilterOptions('responsavel', '/api/responsaveis');
});

function loadFilterOptions(selectId, endpoint) {
    // Implementar carregamento via AJAX
    // Por enquanto, deixar vazio
}
</script>
