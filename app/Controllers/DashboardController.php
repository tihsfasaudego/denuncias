<?php
/**
 * Controlador do Dashboard Analítico
 * Gera dados e visualizações para análise avançada
 */

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Cache.php';
require_once __DIR__ . '/../Models/Denuncia.php';
require_once __DIR__ . '/../Core/Logger.php';

class DashboardController {
    private $conn;
    private $cache;
    private $denunciaModel;
    private $logger;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->cache = Cache::getInstance();
        $this->denunciaModel = new Denuncia();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Obtém dados completos do dashboard
     */
    public function getDashboardData() {
        return $this->cache->remember('dashboard_complete', function() {
            $startTime = microtime(true);
            
            $data = [
                'overview' => $this->getOverviewStats(),
                'trends' => $this->getTrendData(),
                'categories' => $this->getCategoryAnalysis(),
                'performance' => $this->getPerformanceMetrics(),
                'geographic' => $this->getGeographicData(),
                'timeline' => $this->getTimelineData(),
                'alerts' => $this->getSystemAlerts(),
                'recent_activity' => $this->getRecentActivity()
            ];
            
            $endTime = microtime(true);
            $this->logger->performance('dashboard_data_generation', $endTime - $startTime);
            
            return $data;
        }, 300); // Cache por 5 minutos
    }
    
    /**
     * Estatísticas de visão geral
     */
    private function getOverviewStats() {
        try {
            $result = $this->conn->query("
                SELECT 
                    COUNT(*) as total_denuncias,
                    COUNT(CASE WHEN status = 'Pendente' THEN 1 END) as pendentes,
                    COUNT(CASE WHEN status = 'Em Análise' THEN 1 END) as em_analise,
                    COUNT(CASE WHEN status = 'Em Investigação' THEN 1 END) as em_investigacao,
                    COUNT(CASE WHEN status = 'Concluída' THEN 1 END) as concluidas,
                    COUNT(CASE WHEN status = 'Arquivada' THEN 1 END) as arquivadas,
                    COUNT(CASE WHEN DATE(data_criacao) = CURDATE() THEN 1 END) as hoje,
                    COUNT(CASE WHEN WEEK(data_criacao) = WEEK(NOW()) AND YEAR(data_criacao) = YEAR(NOW()) THEN 1 END) as esta_semana,
                    COUNT(CASE WHEN MONTH(data_criacao) = MONTH(NOW()) AND YEAR(data_criacao) = YEAR(NOW()) THEN 1 END) as este_mes,
                    AVG(CASE 
                        WHEN status = 'Concluída' AND data_conclusao IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, data_criacao, data_conclusao) 
                    END) as tempo_medio_resolucao
                FROM denuncias
            ");
            
            $stats = $result->fetch_assoc();
            
            // Calcular percentuais e tendências
            $stats['taxa_resolucao'] = $stats['total_denuncias'] > 0 ? 
                round(($stats['concluidas'] / $stats['total_denuncias']) * 100, 1) : 0;
            
            $stats['tempo_medio_resolucao'] = round($stats['tempo_medio_resolucao'] ?? 0, 1);
            
            // Comparar com período anterior
            $previousWeek = $this->conn->query("
                SELECT COUNT(*) as count 
                FROM denuncias 
                WHERE WEEK(data_criacao) = WEEK(NOW()) - 1 
                AND YEAR(data_criacao) = YEAR(NOW())
            ")->fetch_assoc()['count'];
            
            $stats['tendencia_semanal'] = $previousWeek > 0 ? 
                round((($stats['esta_semana'] - $previousWeek) / $previousWeek) * 100, 1) : 0;
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter estatísticas de overview: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Dados de tendência temporal
     */
    private function getTrendData() {
        try {
            // Dados dos últimos 30 dias
            $result = $this->conn->query("
                SELECT 
                    DATE(data_criacao) as data,
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'Concluída' THEN 1 END) as concluidas,
                    COUNT(CASE WHEN status = 'Pendente' THEN 1 END) as pendentes
                FROM denuncias 
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(data_criacao)
                ORDER BY data
            ");
            
            $trends = [];
            while ($row = $result->fetch_assoc()) {
                $trends[] = [
                    'date' => $row['data'],
                    'total' => (int)$row['total'],
                    'concluidas' => (int)$row['concluidas'],
                    'pendentes' => (int)$row['pendentes'],
                    'taxa_conclusao' => $row['total'] > 0 ? 
                        round(($row['concluidas'] / $row['total']) * 100, 1) : 0
                ];
            }
            
            // Dados por hora do dia (últimos 7 dias)
            $hourlyResult = $this->conn->query("
                SELECT 
                    HOUR(data_criacao) as hora,
                    COUNT(*) as total
                FROM denuncias 
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY HOUR(data_criacao)
                ORDER BY hora
            ");
            
            $hourlyData = array_fill(0, 24, 0);
            while ($row = $hourlyResult->fetch_assoc()) {
                $hourlyData[(int)$row['hora']] = (int)$row['total'];
            }
            
            return [
                'daily' => $trends,
                'hourly' => $hourlyData
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter dados de tendência: ' . $e->getMessage());
            return ['daily' => [], 'hourly' => []];
        }
    }
    
    /**
     * Análise por categorias
     */
    private function getCategoryAnalysis() {
        try {
            $result = $this->conn->query("
                SELECT 
                    c.nome as categoria,
                    COUNT(dc.denuncia_id) as total,
                    COUNT(CASE WHEN d.status = 'Concluída' THEN 1 END) as concluidas,
                    AVG(CASE 
                        WHEN d.status = 'Concluída' AND d.data_conclusao IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, d.data_criacao, d.data_conclusao) 
                    END) as tempo_medio
                FROM categorias c
                LEFT JOIN denuncia_categoria dc ON c.id = dc.categoria_id
                LEFT JOIN denuncias d ON dc.denuncia_id = d.id
                GROUP BY c.id, c.nome
                HAVING total > 0
                ORDER BY total DESC
            ");
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = [
                    'name' => $row['categoria'],
                    'total' => (int)$row['total'],
                    'concluidas' => (int)$row['concluidas'],
                    'taxa_conclusao' => $row['total'] > 0 ? 
                        round(($row['concluidas'] / $row['total']) * 100, 1) : 0,
                    'tempo_medio' => round($row['tempo_medio'] ?? 0, 1)
                ];
            }
            
            return $categories;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter análise de categorias: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Métricas de performance
     */
    private function getPerformanceMetrics() {
        try {
            // SLA - Service Level Agreement
            $slaResult = $this->conn->query("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE 
                        WHEN status = 'Concluída' 
                        AND TIMESTAMPDIFF(HOUR, data_criacao, data_conclusao) <= 72 
                        THEN 1 
                    END) as dentro_sla,
                    COUNT(CASE 
                        WHEN status IN ('Pendente', 'Em Análise', 'Em Investigação')
                        AND TIMESTAMPDIFF(HOUR, data_criacao, NOW()) > 72 
                        THEN 1 
                    END) as fora_sla_ativo
                FROM denuncias 
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            $sla = $slaResult->fetch_assoc();
            
            // Distribuição por responsável
            $responsaveisResult = $this->conn->query("
                SELECT 
                    COALESCE(a.nome, 'Não Atribuído') as responsavel,
                    COUNT(*) as total,
                    COUNT(CASE WHEN d.status = 'Concluída' THEN 1 END) as concluidas,
                    AVG(CASE 
                        WHEN d.status = 'Concluída' AND d.data_conclusao IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, d.data_criacao, d.data_conclusao) 
                    END) as tempo_medio
                FROM denuncias d
                LEFT JOIN admin a ON d.admin_responsavel_id = a.id
                WHERE d.data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY d.admin_responsavel_id, a.nome
                ORDER BY total DESC
                LIMIT 10
            ");
            
            $responsaveis = [];
            while ($row = $responsaveisResult->fetch_assoc()) {
                $responsaveis[] = [
                    'nome' => $row['responsavel'],
                    'total' => (int)$row['total'],
                    'concluidas' => (int)$row['concluidas'],
                    'eficiencia' => $row['total'] > 0 ? 
                        round(($row['concluidas'] / $row['total']) * 100, 1) : 0,
                    'tempo_medio' => round($row['tempo_medio'] ?? 0, 1)
                ];
            }
            
            return [
                'sla' => [
                    'total' => (int)$sla['total'],
                    'dentro_sla' => (int)$sla['dentro_sla'],
                    'fora_sla_ativo' => (int)$sla['fora_sla_ativo'],
                    'percentual_sla' => $sla['total'] > 0 ? 
                        round(($sla['dentro_sla'] / $sla['total']) * 100, 1) : 0
                ],
                'responsaveis' => $responsaveis
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter métricas de performance: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Dados geográficos (simulado - pode ser expandido)
     */
    private function getGeographicData() {
        try {
            // Por enquanto, simulamos dados por setor/departamento
            $result = $this->conn->query("
                SELECT 
                    CASE 
                        WHEN RAND() < 0.3 THEN 'Emergência'
                        WHEN RAND() < 0.5 THEN 'UTI'
                        WHEN RAND() < 0.7 THEN 'Internação'
                        WHEN RAND() < 0.85 THEN 'Ambulatório'
                        ELSE 'Administrativo'
                    END as setor,
                    COUNT(*) as total
                FROM denuncias
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY setor
                ORDER BY total DESC
            ");
            
            $setores = [];
            while ($row = $result->fetch_assoc()) {
                $setores[] = [
                    'name' => $row['setor'],
                    'value' => (int)$row['total']
                ];
            }
            
            return $setores;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter dados geográficos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Timeline de eventos importantes
     */
    private function getTimelineData() {
        try {
            $result = $this->conn->query("
                SELECT 
                    d.protocolo,
                    d.status,
                    d.data_criacao,
                    d.data_atualizacao,
                    a.nome as responsavel
                FROM denuncias d
                LEFT JOIN admin a ON d.admin_responsavel_id = a.id
                WHERE d.data_atualizacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                OR (d.status IN ('Concluída', 'Arquivada') AND d.data_atualizacao >= DATE_SUB(NOW(), INTERVAL 7 DAY))
                ORDER BY d.data_atualizacao DESC
                LIMIT 20
            ");
            
            $timeline = [];
            while ($row = $result->fetch_assoc()) {
                $timeline[] = [
                    'protocolo' => $row['protocolo'],
                    'status' => $row['status'],
                    'timestamp' => $row['data_atualizacao'] ?: $row['data_criacao'],
                    'responsavel' => $row['responsavel'],
                    'type' => in_array($row['status'], ['Concluída', 'Arquivada']) ? 'success' : 'info'
                ];
            }
            
            return $timeline;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter timeline: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Alertas do sistema
     */
    private function getSystemAlerts() {
        $alerts = [];
        
        try {
            // Denúncias pendentes há mais de 72h
            $pendentesResult = $this->conn->query("
                SELECT COUNT(*) as count 
                FROM denuncias 
                WHERE status = 'Pendente' 
                AND TIMESTAMPDIFF(HOUR, data_criacao, NOW()) > 72
            ");
            
            $pendentes = $pendentesResult->fetch_assoc()['count'];
            
            if ($pendentes > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Denúncias Pendentes',
                    'message' => "{$pendentes} denúncia(s) pendente(s) há mais de 72 horas",
                    'count' => $pendentes
                ];
            }
            
            // Denúncias sem responsável
            $semResponsavelResult = $this->conn->query("
                SELECT COUNT(*) as count 
                FROM denuncias 
                WHERE admin_responsavel_id IS NULL 
                AND status != 'Arquivada'
            ");
            
            $semResponsavel = $semResponsavelResult->fetch_assoc()['count'];
            
            if ($semResponsavel > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Sem Responsável',
                    'message' => "{$semResponsavel} denúncia(s) sem responsável atribuído",
                    'count' => $semResponsavel
                ];
            }
            
            // Verificar alertas do sistema de logs
            $systemAlerts = $this->cache->get('system_alerts', []);
            foreach (array_slice($systemAlerts, -3) as $alert) {
                if ($alert['timestamp'] > (time() - 86400)) { // Últimas 24h
                    $alerts[] = [
                        'type' => strtolower($alert['severity']),
                        'title' => 'Sistema',
                        'message' => $alert['message'],
                        'timestamp' => $alert['timestamp']
                    ];
                }
            }
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter alertas: ' . $e->getMessage());
        }
        
        return $alerts;
    }
    
    /**
     * Atividade recente
     */
    private function getRecentActivity() {
        try {
            $result = $this->conn->query("
                SELECT 
                    h.data_alteracao,
                    h.status,
                    h.observacao,
                    d.protocolo,
                    a.nome as admin_nome
                FROM historico_status h
                JOIN denuncias d ON h.denuncia_id = d.id
                LEFT JOIN admin a ON h.admin_id = a.id
                ORDER BY h.data_alteracao DESC
                LIMIT 10
            ");
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = [
                    'timestamp' => $row['data_alteracao'],
                    'protocol' => $row['protocolo'],
                    'status' => $row['status'],
                    'admin' => $row['admin_nome'] ?: 'Sistema',
                    'observation' => $row['observacao']
                ];
            }
            
            return $activities;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter atividade recente: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Dados para gráfico específico via AJAX
     */
    public function getChartData($type, $params = []) {
        switch ($type) {
            case 'status-distribution':
                return $this->getStatusDistribution($params);
                
            case 'monthly-trend':
                return $this->getMonthlyTrend($params);
                
            case 'category-performance':
                return $this->getCategoryPerformance($params);
                
            case 'resolution-time':
                return $this->getResolutionTimeDistribution($params);
                
            default:
                throw new Exception('Tipo de gráfico não encontrado');
        }
    }
    
    /**
     * Distribuição por status
     */
    private function getStatusDistribution($params) {
        $period = $params['period'] ?? '30';
        
        $result = $this->conn->query("
            SELECT 
                status,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM denuncias WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL {$period} DAY))), 1) as percentage
            FROM denuncias 
            WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
            GROUP BY status
            ORDER BY count DESC
        ");
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'label' => $row['status'],
                'value' => (int)$row['count'],
                'percentage' => (float)$row['percentage']
            ];
        }
        
        return $data;
    }
    
    /**
     * Tendência mensal
     */
    private function getMonthlyTrend($params) {
        $months = $params['months'] ?? 12;
        
        $result = $this->conn->query("
            SELECT 
                DATE_FORMAT(data_criacao, '%Y-%m') as month,
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'Concluída' THEN 1 END) as concluded
            FROM denuncias 
            WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL {$months} MONTH)
            GROUP BY DATE_FORMAT(data_criacao, '%Y-%m')
            ORDER BY month
        ");
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'month' => $row['month'],
                'total' => (int)$row['total'],
                'concluded' => (int)$row['concluded'],
                'rate' => $row['total'] > 0 ? round(($row['concluded'] / $row['total']) * 100, 1) : 0
            ];
        }
        
        return $data;
    }
}
