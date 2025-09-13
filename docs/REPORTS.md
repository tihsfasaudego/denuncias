# Sistema de Relatórios - Documentação Técnica

## Visão Geral

O sistema de relatórios permite gerar análises detalhadas das denúncias com filtros avançados e exportação para múltiplos formatos.

## Funcionalidades

### Filtros Disponíveis
- **Período**: `data_inicio` e `data_fim`
- **Status**: Pendente, Em Análise, Em Investigação, Concluída, Arquivada
- **Categoria**: Múltipla seleção
- **Protocolo**: Busca específica
- **Prioridade**: Baixa, Média, Alta, Urgente
- **Responsável**: Filtro por administrador

### Formatos de Exportação
- **HTML**: Visualização web com paginação
- **CSV**: Planilha para análise externa
- **PDF**: Documento formatado para impressão
- **Excel**: Formato nativo Microsoft Excel

---

## Endpoints da API

### GET /admin/relatorios
**Página principal de relatórios**
- **Autenticação**: Obrigatória
- **Permissões**: `reports.access`
- **Parâmetros**: Nenhum
- **Resposta**: Página HTML com formulário de filtros

### GET /admin/relatorios/gerar
**Geração de relatório filtrado**
- **Autenticação**: Obrigatória
- **Permissões**: `reports.generate`
- **Parâmetros**:
  - `data_inicio` (YYYY-MM-DD)
  - `data_fim` (YYYY-MM-DD)
  - `status` (string)
  - `categoria` (array)
  - `protocolo` (string)
  - `formato` (html|csv|pdf)
  - `pagina` (int, default: 1)
  - `limite` (int, default: 50)

### GET /admin/relatorios/estatistico
**Relatório estatístico**
- **Autenticação**: Obrigatória
- **Permissões**: `reports.generate`
- **Parâmetros**:
  - `data_inicio` (YYYY-MM-DD)
  - `data_fim` (YYYY-MM-DD)
- **Resposta**: JSON com estatísticas

---

## Consultas SQL Otimizadas

### Query Principal de Relatórios
```sql
SELECT
    d.id,
    d.protocolo,
    d.status,
    d.prioridade,
    d.data_criacao,
    d.data_atualizacao,
    d.data_conclusao,
    GROUP_CONCAT(DISTINCT c.nome) as categorias,
    a.nome as responsavel_nome,
    d.descricao,
    d.local_ocorrencia,
    d.pessoas_envolvidas
FROM denuncias d
LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
LEFT JOIN categorias c ON dc.categoria_id = c.id
LEFT JOIN admin a ON d.admin_responsavel_id = a.id
WHERE 1=1
    AND d.data_criacao >= ?     -- data_inicio
    AND d.data_criacao <= ?     -- data_fim
    AND (d.status = ? OR ? = '') -- status
    AND (d.protocolo LIKE ? OR ? = '') -- protocolo
GROUP BY d.id
ORDER BY d.data_criacao DESC
LIMIT ? OFFSET ?;               -- paginação
```

### Query de Estatísticas
```sql
SELECT
    status,
    COUNT(*) as total,
    AVG(TIMESTAMPDIFF(HOUR, data_criacao, data_conclusao)) as tempo_medio_horas,
    MIN(data_criacao) as primeira_denuncia,
    MAX(data_criacao) as ultima_denuncia
FROM denuncias
WHERE data_criacao >= ? AND data_criacao <= ?
GROUP BY status
ORDER BY total DESC;
```

### Query de Categorias Mais Frequentes
```sql
SELECT
    c.nome as categoria,
    COUNT(dc.denuncia_id) as frequencia,
    AVG(CASE WHEN d.status = 'Concluída' THEN 1 ELSE 0 END) as taxa_conclusao
FROM categorias c
LEFT JOIN denuncia_categoria dc ON c.id = dc.categoria_id
LEFT JOIN denuncias d ON dc.denuncia_id = d.id
WHERE d.data_criacao >= ? AND d.data_criacao <= ?
GROUP BY c.id, c.nome
ORDER BY frequencia DESC;
```

---

## Índices de Performance

### Índices Recomendados
```sql
-- Índice composto para filtros por data e status
CREATE INDEX idx_denuncias_data_status ON denuncias (data_criacao, status);

-- Índice para busca por protocolo
CREATE INDEX idx_denuncias_protocolo ON denuncias (protocolo);

-- Índice para filtro por responsável
CREATE INDEX idx_denuncias_responsavel ON denuncias (admin_responsavel_id);

-- Índice composto para JOIN otimizado
CREATE INDEX idx_denuncia_categoria_denuncia ON denuncia_categoria (denuncia_id);

-- Índice para filtro por categoria
CREATE INDEX idx_denuncia_categoria_categoria ON denuncia_categoria (categoria_id);
```

### Estratégia de Cache
```php
// Cache por 15 minutos para relatórios gerais
$cacheKey = "relatorio_" . md5(serialize($filtros));
$result = $cache->remember($cacheKey, function() use ($filtros) {
    return $this->gerarRelatorio($filtros);
}, 900);

// Cache por 5 minutos para estatísticas
$statsKey = "relatorio_stats_" . date('Y-m-d-H');
$stats = $cache->remember($statsKey, function() {
    return $this->calcularEstatisticas();
}, 300);
```

---

## Estrutura dos Dados de Saída

### Formato HTML
```html
<table class="table table-striped">
    <thead>
        <tr>
            <th>Protocolo</th>
            <th>Data</th>
            <th>Status</th>
            <th>Categoria</th>
            <th>Responsável</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <!-- Dados paginados -->
    </tbody>
</table>
```

### Formato CSV
```csv
Protocolo,Data,Status,Prioridade,Categorias,Responsavel,Descricao
DEN-2025-001,2025-09-05,Pendente,Média,"Assédio Moral","João Silva","Descrição da denúncia..."
DEN-2025-002,2025-09-05,Em Análise,Alta,"Violação de Normas","Maria Santos","Outra descrição..."
```

### Formato JSON (Estatísticas)
```json
{
  "success": true,
  "periodo": {
    "inicio": "2025-01-01",
    "fim": "2025-09-05"
  },
  "estatisticas": {
    "total_denuncias": 150,
    "por_status": {
      "Pendente": 45,
      "Em Análise": 30,
      "Concluída": 60,
      "Arquivada": 15
    },
    "por_categoria": {
      "Assédio Moral": 40,
      "Corrupção": 25,
      "Discriminação": 35
    },
    "tempo_medio_resolucao": 48.5
  }
}
```

---

## Validações e Segurança

### Validação de Parâmetros
```php
// Validação de datas
if ($dataInicio && $dataFim && strtotime($dataFim) < strtotime($dataInicio)) {
    throw new Exception("Data final não pode ser anterior à inicial");
}

// Validação de status
$statusValidos = ['Pendente', 'Em Análise', 'Em Investigação', 'Concluída', 'Arquivada'];
if ($status && !in_array($status, $statusValidos)) {
    throw new Exception("Status inválido");
}

// Validação de paginação
$pagina = max(1, (int)($pagina ?? 1));
$limite = min(100, max(10, (int)($limite ?? 50)));
```

### Sanitização de Dados
```php
// Escape de strings para SQL
$protocolo = $conn->real_escape_string($protocolo);

// Validação de IDs numéricos
$id = filter_var($id, FILTER_VALIDATE_INT);
if (!$id) {
    throw new Exception("ID inválido");
}
```

---

## Tratamento de Erros

### Erros Comuns e Soluções
```php
try {
    $relatorio = $this->gerarRelatorio($filtros);
} catch (Exception $e) {
    error_log("Erro ao gerar relatório: " . $e->getMessage());

    // Log detalhado para debug
    $this->logger->error('relatorio_geracao_falha', [
        'filtros' => $filtros,
        'erro' => $e->getMessage(),
        'usuario' => Auth::id()
    ]);

    // Resposta amigável
    throw new Exception("Erro ao gerar relatório. Tente novamente.");
}
```

### Timeouts e Limites
```php
// Timeout para queries longas
$conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);

// Limite de memória para exports grandes
ini_set('memory_limit', '256M');

// Limite de tempo de execução
set_time_limit(120);
```

---

## Monitoramento e Logs

### Métricas a Monitorar
- Tempo de resposta das queries
- Tamanho dos resultados
- Taxa de cache hit/miss
- Erros de geração
- Uso de memória durante exports

### Logs de Auditoria
```php
$this->logger->audit('relatorio_gerado', 'admin', Auth::id(), [
    'filtros' => $filtros,
    'formato' => $formato,
    'registros' => count($resultados),
    'tempo_geracao' => microtime(true) - $inicio
]);
```

---

## Otimizações de Performance

### Estratégias Implementadas
1. **Paginação**: Resultados limitados a 50 registros por página
2. **Cache**: Resultados cacheados por 15 minutos
3. **Índices**: Queries otimizadas com índices apropriados
4. **Lazy Loading**: Dados carregados sob demanda
5. **Streaming**: Exports grandes processados em chunks

### Benchmarks Esperados
- **Query simples**: < 100ms
- **Query com JOINs**: < 500ms
- **Export CSV (1000 registros)**: < 2s
- **Export PDF (500 registros)**: < 5s
- **Cache hit**: < 10ms

---

## Manutenção e Suporte

### Tarefas de Manutenção
- Limpeza periódica de cache antigo
- Monitoramento de performance das queries
- Atualização de índices baseada em uso
- Backup de relatórios críticos

### Suporte a Usuários
- Documentação clara dos filtros
- Exemplos de uso práticos
- FAQ sobre formatos de export
- Canal de suporte para dúvidas técnicas
