<?php
/**
 * Relatórios e Análises - Layout Atualizado
 */

// Buscar dados reais do banco para estatísticas
require_once __DIR__ . '/../../Core/Database.php';
$db = Database::getInstance()->getConnection();

// Estatísticas reais
$stats = [];
try {
    // Total de denúncias
    $result = $db->query("SELECT COUNT(*) as total FROM denuncias");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // Por status
    $result = $db->query("SELECT status, COUNT(*) as count FROM denuncias GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
    
    // Estatísticas adicionais
    $result = $db->query("SELECT COUNT(*) as total FROM denuncias WHERE MONTH(data_criacao) = MONTH(CURRENT_DATE()) AND YEAR(data_criacao) = YEAR(CURRENT_DATE())");
    $stats['mes_atual'] = $result->fetch_assoc()['total'];
    
} catch (Exception $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $stats = ['total' => 0, 'Pendente' => 0, 'Em Análise' => 0, 'Concluída' => 0, 'Arquivada' => 0, 'mes_atual' => 0];
}

// Buscar categorias para filtros
$categorias = [];
try {
    $result = $db->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome");
    $categorias = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
}

// Buscar administradores para filtros
$administradores = [];
try {
    $result = $db->query("SELECT id, nome FROM admin WHERE ativo = 1 ORDER BY nome");
    $administradores = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Erro ao buscar administradores: " . $e->getMessage());
}
?>

<!-- Cabeçalho da Página -->
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="hsfa-title text-primary">
                    <i class="fas fa-chart-bar me-2"></i>
                    Relatórios e Análises
                </h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-info" onclick="exportarRelatorio('excel')">
                        <i class="fas fa-file-excel me-1"></i>
                        Excel
                    </button>
                    <button class="btn btn-outline-danger" onclick="exportarRelatorio('pdf')">
                        <i class="fas fa-file-pdf me-1"></i>
                        PDF
                    </button>
                    <button class="btn btn-success" onclick="gerarRelatorioCompleto()">
                        <i class="fas fa-download me-1"></i>
                        Relatório Completo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards KPI com dados reais -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card--kpi card--kpi-primary">
                <div class="card-body text-center">
                    <div class="kpi-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="kpi-number"><?= $stats['total'] ?? 0 ?></div>
                    <div class="kpi-label">Total de Denúncias</div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card--kpi card--kpi-success">
                <div class="card-body text-center">
                    <div class="kpi-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="kpi-number"><?= $stats['Concluída'] ?? 0 ?></div>
                    <div class="kpi-label">Concluídas</div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card--kpi card--kpi-warning">
                <div class="card-body text-center">
                    <div class="kpi-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="kpi-number"><?= $stats['Pendente'] ?? 0 ?></div>
                    <div class="kpi-label">Pendentes</div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card--kpi card--kpi-info">
                <div class="card-body text-center">
                    <div class="kpi-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="kpi-number"><?= $stats['Em Análise'] ?? 0 ?></div>
                    <div class="kpi-label">Em Análise</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Abas -->
    <div class="card hsfa-card">
        <div class="tabbar">
            <button class="tab tab--active" data-tab="relatorio-detalhado">
                <i class="fas fa-list-alt me-2"></i>
                Relatório Detalhado
            </button>
            <button class="tab" data-tab="estatisticas">
                <i class="fas fa-chart-pie me-2"></i>
                Estatísticas
        </button>
        <button class="tab" data-tab="relatorio-tendencia">
            <i class="fas fa-chart-line"></i>
            Tendências
        </button>
        <button class="tab" data-tab="relatorio-categoria">
            <i class="fas fa-tags"></i>
            Por Categoria
        </button>
        <button class="tab" data-tab="relatorio-responsavel">
            <i class="fas fa-user-tie"></i>
            Por Responsável
        </button>
    </div>
                    
    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Relatório Detalhado -->
        <div class="tab-panel tab-panel--active" id="relatorio-detalhado">
            <div class="card">
                <form id="formRelatorioDetalhado" class="hsfa-form">
                    <!-- Grid Responsivo do Formulário -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="data_inicio" class="form-label">
                                <i class="fas fa-calendar-alt"></i>
                                Data Inicial
                            </label>
                            <input type="date" class="input" id="data_inicio" name="data_inicio" required>
                            <div class="field-hint">Selecione a data inicial do período</div>
                        </div>

                        <div class="form-group">
                            <label for="data_fim" class="form-label">
                                <i class="fas fa-calendar-alt"></i>
                                Data Final
                            </label>
                            <input type="date" class="input" id="data_fim" name="data_fim" required>
                            <div class="field-hint">Selecione a data final do período</div>
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">
                                <i class="fas fa-flag"></i>
                                Status
                            </label>
                            <select class="select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="Pendente">Pendente</option>
                                <option value="Em Análise">Em Análise</option>
                                <option value="Em Investigação">Em Investigação</option>
                                <option value="Concluída">Concluída</option>
                                <option value="Arquivada">Arquivada</option>
                            </select>
                            <div class="field-hint">Filtrar por status específico</div>
                        </div>

                        <div class="form-group">
                            <label for="prioridade" class="form-label">
                                <i class="fas fa-exclamation-triangle"></i>
                                Prioridade
                            </label>
                            <select class="select" id="prioridade" name="prioridade">
                                <option value="">Todas</option>
                                <option value="Baixa">Baixa</option>
                                <option value="Média">Média</option>
                                <option value="Alta">Alta</option>
                                <option value="Urgente">Urgente</option>
                            </select>
                            <div class="field-hint">Filtrar por nível de prioridade</div>
                        </div>

                        <div class="form-group">
                            <label for="categoria" class="form-label">
                                <i class="fas fa-tag"></i>
                                Categoria
                            </label>
                            <select class="select" id="categoria" name="categoria">
                                <option value="">Todas</option>
                                <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-hint">Filtrar por categoria da denúncia</div>
                        </div>

                        <div class="form-group">
                            <label for="responsavel" class="form-label">
                                <i class="fas fa-user-tie"></i>
                                Responsável
                            </label>
                            <select class="select" id="responsavel" name="responsavel">
                                <option value="">Todos</option>
                                <?php foreach($administradores as $admin): ?>
                                <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-hint">Filtrar por responsável</div>
                        </div>

                        <div class="form-group">
                            <label for="formato" class="form-label">
                                <i class="fas fa-file"></i>
                                Formato
                            </label>
                            <select class="select" id="formato" name="formato" required>
                                <option value="html">HTML</option>
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                            </select>
                            <div class="field-hint">Escolha o formato de saída</div>
                        </div>

                        <div class="form-group form-group--right">
                            <label for="ordenacao" class="form-label">
                                <i class="fas fa-sort"></i>
                                Ordenação
                            </label>
                            <select class="select" id="ordenacao" name="ordenacao">
                                <option value="data_criacao DESC">Mais Recentes</option>
                                <option value="data_criacao ASC">Mais Antigas</option>
                                <option value="protocolo ASC">Por Protocolo</option>
                                <option value="status ASC">Por Status</option>
                                <option value="prioridade DESC">Por Prioridade</option>
                            </select>
                            <div class="field-hint">Como ordenar os resultados</div>
                        </div>

                        <div class="form-group form-group--right">
                            <label for="limite" class="form-label">
                                <i class="fas fa-list"></i>
                                Limite
                            </label>
                            <select class="select" id="limite" name="limite">
                                <option value="50">50 registros</option>
                                <option value="100">100 registros</option>
                                <option value="500">500 registros</option>
                                <option value="1000">1000 registros</option>
                                <option value="">Todos</option>
                            </select>
                            <div class="field-hint">Máximo de registros</div>
                        </div>
                    </div>
                    
                    <!-- Opções Adicionais -->
                    <div class="form-section">
                        <h3 class="form-section-title">Opções Adicionais</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="incluir_anexos" name="incluir_anexos" class="checkbox">
                                <label for="incluir_anexos" class="checkbox-label">
                                    Incluir informações de anexos
                                </label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="incluir_historico" name="incluir_historico" class="checkbox">
                                <label for="incluir_historico" class="checkbox-label">
                                    Incluir histórico de alterações
                                </label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="incluir_respostas" name="incluir_respostas" class="checkbox">
                                <label for="incluir_respostas" class="checkbox-label">
                                    Incluir respostas e pareceres
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões -->
                    <div class="form-actions">
                        <button type="button" class="btn btn--ghost" onclick="limparFiltros()">
                            <i class="fas fa-eraser"></i>
                            Limpar
                        </button>
                        <button type="button" class="btn btn--primary" onclick="previewRelatorio()" data-loading-text="Gerando...">
                            <i class="fas fa-eye"></i>
                            Visualizar
                        </button>
                        <button type="submit" class="btn btn--primary" data-loading-text="Gerando...">
                            <i class="fas fa-download"></i>
                            Gerar Relatório
                        </button>
                    </div>
                </form>
            </div>
        </div>
                        
                        <!-- Relatório Estatístico -->
                        <div class="tab-panel" id="relatorio-estatistico">
                            <div class="card">
                                <form id="formRelatorioEstatistico" class="hsfa-form">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="data_inicio_estat" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Inicial
                                            </label>
                                            <input type="date" class="input" id="data_inicio_estat" name="data_inicio" required>
                                            <div class="field-hint">Selecione a data inicial do período</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="data_fim_estat" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Final
                                            </label>
                                            <input type="date" class="input" id="data_fim_estat" name="data_fim" required>
                                            <div class="field-hint">Selecione a data final do período</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="tipo_estatistica" class="form-label">
                                                <i class="fas fa-chart-bar"></i>
                                                Tipo de Estatística
                                            </label>
                                            <select class="select" id="tipo_estatistica" name="tipo_estatistica">
                                                <option value="status">Por Status</option>
                                                <option value="categoria">Por Categoria</option>
                                                <option value="prioridade">Por Prioridade</option>
                                                <option value="responsavel">Por Responsável</option>
                                                <option value="mes">Por Mês</option>
                                                <option value="semana">Por Semana</option>
                                            </select>
                                            <div class="field-hint">Como agrupar os dados estatísticos</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="formato_grafico" class="form-label">
                                                <i class="fas fa-chart-pie"></i>
                                                Formato do Gráfico
                                            </label>
                                            <select class="select" id="formato_grafico" name="formato_grafico">
                                                <option value="pizza">Pizza</option>
                                                <option value="barras">Barras</option>
                                                <option value="linhas">Linhas</option>
                                                <option value="ambos">Pizza + Barras</option>
                                            </select>
                                            <div class="field-hint">Tipo de visualização gráfica</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn btn--ghost" onclick="limparFormEstatisticas()">
                                            <i class="fas fa-eraser"></i>
                                            Limpar
                                        </button>
                                        <button type="button" class="btn btn--primary" id="btnGerarEstatisticas" onclick="gerarEstatisticas()" data-loading-text="Gerando...">
                                            <i class="fas fa-chart-pie"></i>
                                            Gerar Estatísticas
                                        </button>
                                        <button type="button" class="btn btn--primary" onclick="exportarGrafico()" data-loading-text="Exportando...">
                                            <i class="fas fa-download"></i>
                                            Exportar Gráfico
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div id="estatisticasContainer" class="mt-6" style="display: none;">
                                <div class="grid grid-cols-3 gap-6 mb-6">
                                    <div class="col-span-2">
                                        <div class="hsfa-card">
                                            <div class="hsfa-card-header">
                                                <h3 class="hsfa-card-title">
                                                    <i class="fas fa-chart-pie"></i>
                                                    Gráfico de Distribuição
                                                </h3>
                                            </div>
                                            <div class="hsfa-card-body">
                                                <div id="graficoStatus" style="height: 400px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <div class="hsfa-card">
                                            <div class="hsfa-card-header">
                                                <h3 class="hsfa-card-title">
                                                    <i class="fas fa-table"></i>
                                                    Dados Numéricos
                                                </h3>
                                            </div>
                                            <div class="hsfa-card-body">
                                                <div class="hsfa-table-container">
                                                    <table class="hsfa-table" id="tabelaEstatisticas">
                                                        <thead>
                                                            <tr>
                                                                <th>Item</th>
                                                                <th>Quantidade</th>
                                                                <th>Percentual</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Dados serão inseridos via JavaScript -->
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th>Total</th>
                                                                <th id="totalDenuncias">0</th>
                                                                <th>100%</th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="hsfa-card">
                                    <div class="hsfa-card-header">
                                        <h3 class="hsfa-card-title">
                                            <i class="fas fa-chart-bar"></i>
                                            Gráfico de Barras
                                        </h3>
                                    </div>
                                    <div class="hsfa-card-body">
                                        <div id="graficoBarras" style="height: 300px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        
                        <!-- Relatório de Tendências -->
                        <div class="tab-panel" id="relatorio-tendencia">
                            <div class="card">
                                <form id="formRelatorioTendencia" class="hsfa-form">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="data_inicio_tendencia" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Inicial
                                            </label>
                                            <input type="date" class="input" id="data_inicio_tendencia" name="data_inicio" required>
                                            <div class="field-hint">Selecione a data inicial para análise de tendências</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="data_fim_tendencia" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Final
                                            </label>
                                            <input type="date" class="input" id="data_fim_tendencia" name="data_fim" required>
                                            <div class="field-hint">Selecione a data final para análise de tendências</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="periodo_tendencia" class="form-label">
                                                <i class="fas fa-clock"></i>
                                                Período de Agrupamento
                                            </label>
                                            <select class="select" id="periodo_tendencia" name="periodo">
                                                <option value="diario">Diário</option>
                                                <option value="semanal">Semanal</option>
                                                <option value="mensal" selected>Mensal</option>
                                                <option value="trimestral">Trimestral</option>
                                            </select>
                                            <div class="field-hint">Como agrupar os dados no tempo</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="metrica_tendencia" class="form-label">
                                                <i class="fas fa-chart-line"></i>
                                                Métrica a Analisar
                                            </label>
                                            <select class="select" id="metrica_tendencia" name="metrica">
                                                <option value="total">Total de Denúncias</option>
                                                <option value="status">Por Status</option>
                                                <option value="categoria">Por Categoria</option>
                                                <option value="prioridade">Por Prioridade</option>
                                            </select>
                                            <div class="field-hint">Qual métrica será analisada nas tendências</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn btn--ghost" onclick="limparFormTendencias()">
                                            <i class="fas fa-eraser"></i>
                                            Limpar
                                        </button>
                                        <button type="button" class="btn btn--primary" id="btnGerarTendencias" onclick="gerarTendencias()" data-loading-text="Analisando...">
                                            <i class="fas fa-chart-line"></i>
                                            Gerar Análise de Tendências
                                        </button>
                                        <button type="button" class="btn btn--primary" onclick="exportarTendencias()" data-loading-text="Exportando...">
                                            <i class="fas fa-download"></i>
                                            Exportar
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div id="tendenciasContainer" class="mt-6" style="display: none;">
                                <div class="hsfa-card mb-6">
                                    <div class="hsfa-card-header">
                                        <h3 class="hsfa-card-title">
                                            <i class="fas fa-chart-line"></i>
                                            Gráfico de Tendências
                                        </h3>
                                    </div>
                                    <div class="hsfa-card-body">
                                        <div id="graficoTendencias" style="height: 400px;"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="hsfa-card">
                                        <div class="hsfa-card-header">
                                            <h3 class="hsfa-card-title">
                                                <i class="fas fa-trending-up"></i>
                                                Indicadores de Crescimento
                                            </h3>
                                        </div>
                                        <div class="hsfa-card-body">
                                            <div id="indicadoresCrescimento">
                                                <!-- Dados serão inseridos via JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hsfa-card">
                                        <div class="hsfa-card-header">
                                            <h3 class="hsfa-card-title">
                                                <i class="fas fa-calendar-check"></i>
                                                Previsões
                                            </h3>
                                        </div>
                                        <div class="hsfa-card-body">
                                            <div id="previsoes">
                                                <!-- Dados serão inseridos via JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Relatório por Categoria -->
                        <div class="tab-panel" id="relatorio-categoria">
                            <div class="card">
                                <form id="formRelatorioCategoria" class="hsfa-form">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="data_inicio_categoria" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Inicial
                                            </label>
                                            <input type="date" class="input" id="data_inicio_categoria" name="data_inicio" required>
                                            <div class="field-hint">Selecione a data inicial do período</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="data_fim_categoria" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Final
                                            </label>
                                            <input type="date" class="input" id="data_fim_categoria" name="data_fim" required>
                                            <div class="field-hint">Selecione a data final do período</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="categoria_especifica" class="form-label">
                                                <i class="fas fa-tag"></i>
                                                Categoria Específica
                                            </label>
                                            <select class="select" id="categoria_especifica" name="categoria">
                                                <option value="">Todas as Categorias</option>
                                                <?php foreach($categorias as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="field-hint">Filtrar por categoria específica ou mostrar todas</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="ordenacao_categoria" class="form-label">
                                                <i class="fas fa-sort"></i>
                                                Ordenação
                                            </label>
                                            <select class="select" id="ordenacao_categoria" name="ordenacao">
                                                <option value="quantidade_desc">Maior Quantidade</option>
                                                <option value="quantidade_asc">Menor Quantidade</option>
                                                <option value="nome_asc">Nome A-Z</option>
                                                <option value="nome_desc">Nome Z-A</option>
                                            </select>
                                            <div class="field-hint">Como ordenar as categorias</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn btn--ghost" onclick="limparFormCategoria()">
                                            <i class="fas fa-eraser"></i>
                                            Limpar
                                        </button>
                                        <button type="button" class="btn btn--primary" id="btnGerarCategoria" onclick="gerarRelatorioCategoria()" data-loading-text="Gerando...">
                                            <i class="fas fa-tags"></i>
                                            Gerar Relatório por Categoria
                                        </button>
                                        <button type="button" class="btn btn--primary" onclick="exportarCategoria()" data-loading-text="Exportando...">
                                            <i class="fas fa-download"></i>
                                            Exportar
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div id="categoriaContainer" class="mt-6" style="display: none;">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="hsfa-card">
                                        <div class="hsfa-card-header">
                                            <h3 class="hsfa-card-title">
                                                <i class="fas fa-chart-donut"></i>
                                                Distribuição por Categoria
                                            </h3>
                                        </div>
                                        <div class="hsfa-card-body">
                                            <div id="graficoCategoria" style="height: 350px;"></div>
                                        </div>
                                    </div>
                                    <div class="hsfa-card">
                                        <div class="hsfa-card-header">
                                            <h3 class="hsfa-card-title">
                                                <i class="fas fa-table"></i>
                                                Dados Detalhados
                                            </h3>
                                        </div>
                                        <div class="hsfa-card-body">
                                            <div class="hsfa-table-container">
                                                <table class="hsfa-table" id="tabelaCategoria">
                                                    <thead>
                                                        <tr>
                                                            <th>Categoria</th>
                                                            <th>Total</th>
                                                            <th>Pendentes</th>
                                                            <th>Concluídas</th>
                                                            <th>% Conclusão</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Dados serão inseridos via JavaScript -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Relatório por Responsável -->
                        <div class="tab-panel" id="relatorio-responsavel">
                            <div class="card">
                                <form id="formRelatorioResponsavel" class="hsfa-form">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="data_inicio_responsavel" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Inicial
                                            </label>
                                            <input type="date" class="input" id="data_inicio_responsavel" name="data_inicio" required>
                                            <div class="field-hint">Selecione a data inicial do período</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="data_fim_responsavel" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Data Final
                                            </label>
                                            <input type="date" class="input" id="data_fim_responsavel" name="data_fim" required>
                                            <div class="field-hint">Selecione a data final do período</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="responsavel_especifico" class="form-label">
                                                <i class="fas fa-user-tie"></i>
                                                Responsável Específico
                                            </label>
                                            <select class="select" id="responsavel_especifico" name="responsavel">
                                                <option value="">Todos os Responsáveis</option>
                                                <?php foreach($administradores as $admin): ?>
                                                <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="field-hint">Filtrar por responsável específico ou mostrar todos</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="metrica_responsavel" class="form-label">
                                                <i class="fas fa-chart-bar"></i>
                                                Métrica de Performance
                                            </label>
                                            <select class="select" id="metrica_responsavel" name="metrica">
                                                <option value="quantidade">Quantidade de Denúncias</option>
                                                <option value="tempo_resolucao">Tempo Médio de Resolução</option>
                                                <option value="taxa_conclusao">Taxa de Conclusão</option>
                                                <option value="eficiencia">Eficiência Geral</option>
                                            </select>
                                            <div class="field-hint">Como medir a performance dos responsáveis</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn btn--ghost" onclick="limparFormResponsavel()">
                                            <i class="fas fa-eraser"></i>
                                            Limpar
                                        </button>
                                        <button type="button" class="btn btn--primary" id="btnGerarResponsavel" onclick="gerarRelatorioResponsavel()" data-loading-text="Gerando...">
                                            <i class="fas fa-user-tie"></i>
                                            Gerar Relatório por Responsável
                                        </button>
                                        <button type="button" class="btn btn--primary" onclick="exportarResponsavel()" data-loading-text="Exportando...">
                                            <i class="fas fa-download"></i>
                                            Exportar
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div id="responsavelContainer" class="mt-6" style="display: none;">
                                <div class="grid grid-cols-3 gap-6">
                                    <div class="col-span-2">
                                        <div class="hsfa-card">
                                            <div class="hsfa-card-header">
                                                <h3 class="hsfa-card-title">
                                                    <i class="fas fa-chart-bar"></i>
                                                    Performance por Responsável
                                                </h3>
                                            </div>
                                            <div class="hsfa-card-body">
                                                <div id="graficoResponsavel" style="height: 400px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <div class="hsfa-card">
                                            <div class="hsfa-card-header">
                                                <h3 class="hsfa-card-title">
                                                    <i class="fas fa-trophy"></i>
                                                    Ranking
                                                </h3>
                                            </div>
                                            <div class="hsfa-card-body">
                                                <div id="rankingResponsaveis">
                                                    <!-- Dados serão inseridos via JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="hsfa-card mt-6">
                                    <div class="hsfa-card-header">
                                        <h3 class="hsfa-card-title">
                                            <i class="fas fa-table"></i>
                                            Detalhamento por Responsável
                                        </h3>
                                    </div>
                                    <div class="hsfa-card-body">
                                        <div class="hsfa-table-container">
                                            <table class="hsfa-table" id="tabelaResponsavel">
                                                <thead>
                                                    <tr>
                                                        <th>Responsável</th>
                                                        <th>Total Atribuídas</th>
                                                        <th>Concluídas</th>
                                                        <th>Em Andamento</th>
                                                        <th>% Conclusão</th>
                                                        <th>Tempo Médio</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Dados serão inseridos via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye me-2"></i>Preview do Relatório
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <!-- Conteúdo do preview será inserido aqui -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="gerarRelatorioFinal()">
                    <i class="fas fa-download me-1"></i>Gerar Relatório
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.text-xs {
    font-size: 0.7rem;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.text-uppercase {
    text-transform: uppercase !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df, #224abe) !important;
}

.card-header-tabs .nav-link {
    border: none;
    color: rgba(255, 255, 255, 0.8);
    background: transparent;
}

.card-header-tabs .nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.375rem;
}

.card-header-tabs .nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.chart-container {
    position: relative;
    height: 400px;
}

.loading-spinner {
    display: none;
    text-align: center;
    padding: 2rem;
}

.loading-spinner.show {
    display: block;
}

.ranking-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #e3e6f0;
}

.ranking-position {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 1rem;
}

.ranking-position.position-1 {
    background: #ffd700;
    color: #000;
}

.ranking-position.position-2 {
    background: #c0c0c0;
    color: #000;
}

.ranking-position.position-3 {
    background: #cd7f32;
    color: #fff;
}

.ranking-position.position-other {
    background: #e3e6f0;
    color: #5a5c69;
}

.indicador-crescimento {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.indicador-crescimento.positivo {
    background: #d4edda;
    border-left: 4px solid #28a745;
}

.indicador-crescimento.negativo {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
}

.indicador-crescimento.neutro {
    background: #d1ecf1;
    border-left: 4px solid #17a2b8;
}
</style>

<script>
// Variáveis globais para gráficos
let graficoStatus = null;
let graficoBarras = null;
let graficoTendencias = null;
let graficoCategoria = null;
let graficoResponsavel = null;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar datas padrão
    const hoje = new Date();
    const primeiroDiaMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    
    // Definir datas padrão para todos os campos
    const camposData = [
        'data_inicio', 'data_fim', 'data_inicio_estat', 'data_fim_estat',
        'data_inicio_tendencia', 'data_fim_tendencia', 'data_inicio_categoria',
        'data_fim_categoria', 'data_inicio_responsavel', 'data_fim_responsavel'
    ];
    
    camposData.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            if (campo.includes('inicio')) {
                elemento.value = primeiroDiaMes.toISOString().split('T')[0];
            } else {
                elemento.value = hoje.toISOString().split('T')[0];
            }
        }
    });
    
    // Carregar estatísticas iniciais
    carregarEstatisticasIniciais();
    
    // Event listeners
    document.getElementById('formRelatorioDetalhado').addEventListener('submit', function(e) {
        e.preventDefault();
        gerarRelatorioDetalhado();
    });
    
    document.getElementById('btnGerarEstatisticas').addEventListener('click', gerarEstatisticas);
    document.getElementById('btnGerarTendencias').addEventListener('click', gerarTendencias);
    document.getElementById('btnGerarCategoria').addEventListener('click', gerarRelatorioCategoria);
    document.getElementById('btnGerarResponsavel').addEventListener('click', gerarRelatorioResponsavel);
});

// Carregar estatísticas iniciais dos cards
function carregarEstatisticasIniciais() {
    fetch('/admin/relatorios/estatisticas-rapidas')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalDenunciasCard').textContent = data.total || 0;
                document.getElementById('denunciasConcluidasCard').textContent = data.concluidas || 0;
                document.getElementById('denunciasPendentesCard').textContent = data.pendentes || 0;
                document.getElementById('denunciasEmAnaliseCard').textContent = data.em_analise || 0;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar estatísticas:', error);
        });
}

// Gerar relatório detalhado
function gerarRelatorioDetalhado() {
    const form = document.getElementById('formRelatorioDetalhado');
    const formData = new FormData(form);
    
    // Validação
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
        const queryParams = new URLSearchParams(formData).toString();
    const formato = formData.get('formato');
    
    let url = '';
    switch(formato) {
        case 'pdf':
            url = `/admin/relatorios/exportar-pdf?${queryParams}`;
            break;
        case 'excel':
            url = `/admin/relatorios/exportar-excel?${queryParams}`;
            break;
        case 'csv':
            url = `/admin/relatorios/exportar-csv?${queryParams}`;
            break;
        default:
            url = `/admin/relatorios/gerar-html?${queryParams}`;
    }
    
    window.open(url, '_blank');
}

// Preview do relatório
function previewRelatorio() {
    const form = document.getElementById('formRelatorioDetalhado');
    const formData = new FormData(form);
    const queryParams = new URLSearchParams(formData).toString();
    
    fetch(`/admin/relatorios/preview?${queryParams}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('previewContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        })
        .catch(error => {
            console.error('Erro ao gerar preview:', error);
            alert('Erro ao gerar preview do relatório');
        });
}

// Gerar relatório final após preview
function gerarRelatorioFinal() {
    const form = document.getElementById('formRelatorioDetalhado');
    const formData = new FormData(form);
    const queryParams = new URLSearchParams(formData).toString();
    
    const formato = formData.get('formato');
    let url = '';
    
    switch(formato) {
        case 'pdf':
            url = `/admin/relatorios/exportar-pdf?${queryParams}`;
            break;
        case 'excel':
            url = `/admin/relatorios/exportar-excel?${queryParams}`;
            break;
        case 'csv':
            url = `/admin/relatorios/exportar-csv?${queryParams}`;
            break;
        default:
            url = `/admin/relatorios/gerar-html?${queryParams}`;
    }
    
    window.open(url, '_blank');
    bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();
}
    
    // Gerar estatísticas
function gerarEstatisticas() {
    const form = document.getElementById('formRelatorioEstatistico');
    const formData = new FormData(form);
        const queryParams = new URLSearchParams(formData).toString();
        
    mostrarLoading('estatisticasContainer');
    
    fetch(`/admin/relatorios/estatistico?${queryParams}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderizarEstatisticas(data.data);
                    document.getElementById('estatisticasContainer').style.display = 'block';
                } else {
                    alert('Erro ao gerar estatísticas: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar estatísticas');
        })
        .finally(() => {
            esconderLoading('estatisticasContainer');
            });
}
    
// Renderizar estatísticas
    function renderizarEstatisticas(dados) {
        // Preencher tabela
        const tbody = document.querySelector('#tabelaEstatisticas tbody');
        tbody.innerHTML = '';
        
        let total = 0;
        dados.forEach(item => {
            total += parseInt(item.total);
        });
        
        document.getElementById('totalDenuncias').textContent = total;
        
        dados.forEach(item => {
            const percentual = (item.total / total * 100).toFixed(2);
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${item.item || 'Não definido'}</td>
                <td>${item.total}</td>
                <td>${percentual}%</td>
            `;
            tbody.appendChild(tr);
        });
        
    // Renderizar gráfico de pizza
    renderizarGraficoPizza(dados, total);
    
    // Renderizar gráfico de barras
    renderizarGraficoBarras(dados);
}

// Renderizar gráfico de pizza
function renderizarGraficoPizza(dados, total) {
        const ctx = document.getElementById('graficoStatus');
        
        if (graficoStatus) {
            graficoStatus.destroy();
        }
        
        const coresPadrao = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
        '#e74a3b', '#5a5c69', '#6f42c1', '#fd7e14'
    ];
    
        graficoStatus = new Chart(ctx, {
            type: 'pie',
            data: {
            labels: dados.map(item => item.item || 'Não definido'),
                datasets: [{
                    data: dados.map(item => item.total),
                    backgroundColor: coresPadrao.slice(0, dados.length),
                    hoverBackgroundColor: coresPadrao.slice(0, dados.length),
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function(context) {
                                const percentual = (context.raw / total * 100).toFixed(2);
                                return `${context.label}: ${context.raw} (${percentual}%)`;
                            }
                        }
                    }
                }
            }
        });
}

// Renderizar gráfico de barras
function renderizarGraficoBarras(dados) {
    const ctx = document.getElementById('graficoBarras');
    
    if (graficoBarras) {
        graficoBarras.destroy();
    }
    
    const coresPadrao = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
        '#e74a3b', '#5a5c69', '#6f42c1', '#fd7e14'
    ];
    
    graficoBarras = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dados.map(item => item.item || 'Não definido'),
            datasets: [{
                label: 'Quantidade',
                data: dados.map(item => item.total),
                backgroundColor: coresPadrao.slice(0, dados.length),
                borderColor: coresPadrao.slice(0, dados.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Funções utilitárias
function mostrarLoading(containerId) {
    const container = document.getElementById(containerId);
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-spinner show';
    loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando dados...</p>';
    container.appendChild(loadingDiv);
}

function esconderLoading(containerId) {
    const container = document.getElementById(containerId);
    const loadingDiv = container.querySelector('.loading-spinner');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

function limparFiltros() {
    const form = document.getElementById('formRelatorioDetalhado');
    form.reset();
    form.classList.remove('was-validated');
    
    // Redefinir datas padrão
    const hoje = new Date();
    const primeiroDiaMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    
    document.getElementById('data_inicio').value = primeiroDiaMes.toISOString().split('T')[0];
    document.getElementById('data_fim').value = hoje.toISOString().split('T')[0];
}

function exportarRelatorio(formato) {
    const form = document.getElementById('formRelatorioDetalhado');
    const formData = new FormData(form);
    formData.set('formato', formato);
    
    const queryParams = new URLSearchParams(formData).toString();
    let url = '';
    
    switch(formato) {
        case 'excel':
            url = `/admin/relatorios/exportar-excel?${queryParams}`;
            break;
        case 'pdf':
            url = `/admin/relatorios/exportar-pdf?${queryParams}`;
            break;
        default:
            url = `/admin/relatorios/gerar-html?${queryParams}`;
    }
    
    window.open(url, '_blank');
}

function gerarRelatorioCompleto() {
    const form = document.getElementById('formRelatorioDetalhado');
    const formData = new FormData(form);
    formData.set('formato', 'pdf');
    formData.set('incluir_anexos', 'on');
    formData.set('incluir_historico', 'on');
    formData.set('incluir_respostas', 'on');
    
    const queryParams = new URLSearchParams(formData).toString();
    window.open(`/admin/relatorios/relatorio-completo?${queryParams}`, '_blank');
}

function exportarGrafico() {
    if (graficoStatus) {
        const url = graficoStatus.toBase64Image();
        const a = document.createElement('a');
        a.href = url;
        a.download = 'grafico-denuncias.png';
        a.click();
    }
}

// Gerar tendências
function gerarTendencias() {
    const form = document.getElementById('formRelatorioTendencias');
    const formData = new FormData(form);
    const queryParams = new URLSearchParams(formData).toString();
    
    mostrarLoading('tendenciasContainer');
    
    fetch(`/admin/relatorios/tendencias?${queryParams}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarTendencias(data.data);
                document.getElementById('tendenciasContainer').style.display = 'block';
            } else {
                alert('Erro ao gerar tendências: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar tendências');
        })
        .finally(() => {
            esconderLoading('tendenciasContainer');
        });
}

// Renderizar tendências
function renderizarTendencias(dados) {
    const ctx = document.getElementById('graficoTendencias');
    
    if (graficoTendencias) {
        graficoTendencias.destroy();
    }
    
    graficoTendencias = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dados.labels || [],
            datasets: dados.datasets || []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Renderizar indicadores de crescimento
    renderizarIndicadoresCrescimento(dados.indicadores || []);
}

// Renderizar indicadores de crescimento
function renderizarIndicadoresCrescimento(indicadores) {
    const container = document.getElementById('indicadoresCrescimento');
    container.innerHTML = '';
    
    indicadores.forEach(indicator => {
        const div = document.createElement('div');
        div.className = `indicador-crescimento ${indicator.tipo}`;
        div.innerHTML = `
            <h6>${indicator.titulo}</h6>
            <p class="mb-0">${indicator.valor} ${indicator.unidade}</p>
            <small>${indicator.descricao}</small>
        `;
        container.appendChild(div);
    });
}

// Gerar relatório por categoria
function gerarRelatorioCategoria() {
    const form = document.getElementById('formRelatorioCategoria');
    const formData = new FormData(form);
    const queryParams = new URLSearchParams(formData).toString();
    
    mostrarLoading('categoriaContainer');
    
    fetch(`/admin/relatorios/categoria?${queryParams}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarRelatorioCategoria(data.data);
                document.getElementById('categoriaContainer').style.display = 'block';
            } else {
                alert('Erro ao gerar relatório por categoria: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados por categoria');
        })
        .finally(() => {
            esconderLoading('categoriaContainer');
        });
}

// Renderizar relatório por categoria
function renderizarRelatorioCategoria(dados) {
    // Preencher tabela
    const tbody = document.querySelector('#tabelaCategoria tbody');
    tbody.innerHTML = '';
    
    dados.forEach(item => {
        const percentualConclusao = item.total > 0 ? ((item.concluidas / item.total) * 100).toFixed(1) : 0;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.categoria}</td>
            <td>${item.total}</td>
            <td>${item.pendentes}</td>
            <td>${item.concluidas}</td>
            <td>${percentualConclusao}%</td>
        `;
        tbody.appendChild(tr);
    });
    
    // Renderizar gráfico de categoria
    renderizarGraficoCategoria(dados);
}

// Renderizar gráfico de categoria
function renderizarGraficoCategoria(dados) {
    const ctx = document.getElementById('graficoCategoria');
    
    if (graficoCategoria) {
        graficoCategoria.destroy();
    }
    
    const coresPadrao = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
        '#e74a3b', '#5a5c69', '#6f42c1', '#fd7e14'
    ];
    
    graficoCategoria = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dados.map(item => item.categoria),
            datasets: [{
                data: dados.map(item => item.total),
                backgroundColor: coresPadrao.slice(0, dados.length),
                hoverBackgroundColor: coresPadrao.slice(0, dados.length),
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
}

// Gerar relatório por responsável
function gerarRelatorioResponsavel() {
    const form = document.getElementById('formRelatorioResponsavel');
    const formData = new FormData(form);
    const queryParams = new URLSearchParams(formData).toString();
    
    mostrarLoading('responsavelContainer');
    
    fetch(`/admin/relatorios/responsavel?${queryParams}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarRelatorioResponsavel(data.data);
                document.getElementById('responsavelContainer').style.display = 'block';
            } else {
                alert('Erro ao gerar relatório por responsável: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados por responsável');
        })
        .finally(() => {
            esconderLoading('responsavelContainer');
        });
}

// Renderizar relatório por responsável
function renderizarRelatorioResponsavel(dados) {
    // Preencher tabela
    const tbody = document.querySelector('#tabelaResponsavel tbody');
    tbody.innerHTML = '';
    
    dados.forEach(item => {
        const percentualConclusao = item.total_atribuidas > 0 ? ((item.concluidas / item.total_atribuidas) * 100).toFixed(1) : 0;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.responsavel}</td>
            <td>${item.total_atribuidas}</td>
            <td>${item.concluidas}</td>
            <td>${item.em_andamento}</td>
            <td>${percentualConclusao}%</td>
            <td>${item.tempo_medio || 'N/A'}</td>
        `;
        tbody.appendChild(tr);
    });
    
    // Renderizar gráfico de responsável
    renderizarGraficoResponsavel(dados);
    
    // Renderizar ranking
    renderizarRankingResponsaveis(dados);
}

// Renderizar gráfico de responsável
function renderizarGraficoResponsavel(dados) {
    const ctx = document.getElementById('graficoResponsavel');
    
    if (graficoResponsavel) {
        graficoResponsavel.destroy();
    }
    
    const coresPadrao = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
        '#e74a3b', '#5a5c69', '#6f42c1', '#fd7e14'
    ];
    
    graficoResponsavel = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dados.map(item => item.responsavel),
            datasets: [
                {
                    label: 'Concluídas',
                    data: dados.map(item => item.concluidas),
                    backgroundColor: '#1cc88a',
                },
                {
                    label: 'Em Andamento',
                    data: dados.map(item => item.em_andamento),
                    backgroundColor: '#f6c23e',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Renderizar ranking de responsáveis
function renderizarRankingResponsaveis(dados) {
    const container = document.getElementById('rankingResponsaveis');
    container.innerHTML = '';
    
    // Ordenar por percentual de conclusão
    const ranking = dados.sort((a, b) => {
        const percentualA = a.total_atribuidas > 0 ? (a.concluidas / a.total_atribuidas) : 0;
        const percentualB = b.total_atribuidas > 0 ? (b.concluidas / b.total_atribuidas) : 0;
        return percentualB - percentualA;
    });
    
    ranking.forEach((item, index) => {
        const percentualConclusao = item.total_atribuidas > 0 ? ((item.concluidas / item.total_atribuidas) * 100).toFixed(1) : 0;
        
        const div = document.createElement('div');
        div.className = 'ranking-item';
        
        let positionClass = 'position-other';
        if (index === 0) positionClass = 'position-1';
        else if (index === 1) positionClass = 'position-2';
        else if (index === 2) positionClass = 'position-3';
        
        div.innerHTML = `
            <div class="ranking-position ${positionClass}">${index + 1}</div>
            <div class="flex-grow-1">
                <div class="fw-bold">${item.responsavel}</div>
                <small class="text-muted">${percentualConclusao}% de conclusão</small>
            </div>
        `;
        container.appendChild(div);
    });
}

// Funções auxiliares para os novos formulários
function limparFormEstatisticas() {
    document.getElementById('formRelatorioEstatistico').reset();
}

function limparFormTendencias() {
    document.getElementById('formRelatorioTendencia').reset();
}

function limparFormCategoria() {
    document.getElementById('formRelatorioCategoria').reset();
}

function limparFormResponsavel() {
    document.getElementById('formRelatorioResponsavel').reset();
}

// Função para gerenciar abas
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.getAttribute('data-tab');
            
            // Remove active class from all tabs and panels
            tabs.forEach(t => t.classList.remove('tab--active'));
            tabPanels.forEach(panel => panel.classList.remove('tab-panel--active'));
            
            // Add active class to clicked tab and corresponding panel
            tab.classList.add('tab--active');
            document.getElementById(targetId).classList.add('tab-panel--active');
        });
    });
}

// Integração com as microinterações
function integrarMicrointeracoes() {
    // Aplicar loading states nos botões
    const buttons = document.querySelectorAll('[data-loading-text]');
    buttons.forEach(button => {
        button.addEventListener('click', () => {
            if (window.HSFA && window.HSFA.loading) {
                window.HSFA.loading.setButtonLoading(button, button.getAttribute('data-loading-text'));
                
                // Simular reset após 3 segundos (em produção, seria após a resposta)
                setTimeout(() => {
                    window.HSFA.loading.resetButton(button, button.textContent);
                }, 3000);
            }
        });
    });
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    integrarMicrointeracoes();
    carregarEstatisticasIniciais();
});
</script> 