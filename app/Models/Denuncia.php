<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Cache.php';
require_once __DIR__ . '/../Models/User.php';

class Denuncia
{
    private $conn;
    private $emailService;
    private $userModel;
    private $cache;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->emailService = null; // Carrega apenas quando necessário
        $this->userModel = new User();
        $this->cache = Cache::getInstance();
    }

    // Gera um código único para cada denúncia
    private function gerarProtocolo($length = 8) {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
    }

    public function salvar($descricao, $anexo = null) {
        $protocolo = $this->gerarProtocolo();
        $stmt = $this->conn->prepare("INSERT INTO denuncias (protocolo, descricao, anexo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $protocolo, $descricao, $anexo);
        $stmt->execute();
        return $protocolo;
    }



    public function listarTodas($useCache = true) {
        $cacheKey = 'denuncias_lista_todas';
        
        if ($useCache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        try {
            // Usar a view vw_denuncias_completas que existe no banco
            $result = $this->conn->query("
                SELECT * FROM vw_denuncias_completas
                ORDER BY data_criacao DESC
            ");
            
            $denuncias = [];
            if ($result) {
                $denuncias = $result->fetch_all(MYSQLI_ASSOC);
            }
            
            // Cache por 5 minutos
            if ($useCache) {
                $this->cache->set($cacheKey, $denuncias, 300);
            }
            
            return $denuncias;
            
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias: " . $e->getMessage());
            return [];
        }
    }

    public function listarPorStatus($status, $useCache = true) {
        $cacheKey = "denuncias_status_{$status}";
        
        if ($useCache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM vw_denuncias_completas
                WHERE status = ?
                ORDER BY data_criacao DESC
            ");
            
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $denuncias = [];
            if ($result) {
                $denuncias = $result->fetch_all(MYSQLI_ASSOC);
            }
            
            // Cache por 3 minutos
            if ($useCache) {
                $this->cache->set($cacheKey, $denuncias, 180);
            }
            
            return $denuncias;
            
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias por status: " . $e->getMessage());
            return [];
        }
    }

    public function atualizarStatus($protocolo, $status, $resposta = '') {
        try {
            // Verificar se a denúncia existe e pegar o ID
            $stmt = $this->conn->prepare("SELECT id FROM denuncias WHERE protocolo = ?");
            $stmt->bind_param("s", $protocolo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("Denúncia não encontrada para o protocolo: $protocolo");
                return false;
            }
            
            $denuncia = $result->fetch_assoc();
            $denuncia_id = $denuncia['id'];
            
            // Pegar o ID do admin da sessão
            $admin_id = $_SESSION['admin']['id'] ?? null;
            
            // Iniciar transação
            $this->conn->begin_transaction();
            
            try {
                // Atualizar a denúncia com os campos corretos
                $stmt = $this->conn->prepare("
                    UPDATE denuncias 
                    SET status = ?,
                        conclusao_descricao = ?,
                        admin_responsavel_id = ?,
                        data_conclusao = CASE 
                            WHEN ? = 'Concluída' THEN CURRENT_TIMESTAMP
                            ELSE NULL
                        END
                    WHERE protocolo = ?
                ");
                
                $stmt->bind_param("ssiss", $status, $resposta, $admin_id, $status, $protocolo);
                $result = $stmt->execute();
                
                if (!$result) {
                    throw new Exception("Erro ao executar update: " . $stmt->error);
                }
                
                // Chamar a stored procedure para registrar o histórico
                $stmt = $this->conn->prepare("CALL sp_atualizar_status(?, ?, ?, ?)");
                $stmt->bind_param("isis", $denuncia_id, $status, $admin_id, $resposta);
                $stmt->execute();
                
                $this->conn->commit();
                
                // Invalidar cache relacionado
                $this->invalidateCache($protocolo);
                
                return true;
                
            } catch (Exception $e) {
                $this->conn->rollback();
                error_log("Erro na transação: " . $e->getMessage());
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar status: " . $e->getMessage());
            return false;
        }
    }

    public function store($dados, $categorias) {
        try {
            $this->conn->begin_transaction();

            $protocolo = $this->gerarProtocolo();
            $stmt = $this->conn->prepare("
                INSERT INTO denuncias (
                    protocolo, 
                    descricao, 
                    anexo, 
                    ip_denunciante,
                    user_agent,
                    data_ocorrencia,
                    local_ocorrencia,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendente')
            ");
            
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipDenunciante = $_SERVER['REMOTE_ADDR'] ?? '';
            
            $stmt->bind_param("sssssss", 
                $protocolo, 
                $dados['descricao'], 
                $dados['anexo'], 
                $ipDenunciante,
                $userAgent,
                $dados['data_ocorrencia'],
                $dados['local_ocorrencia']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir denúncia: " . $stmt->error);
            }
            
            $denuncia_id = $this->conn->insert_id;

            if (!empty($categorias)) {
                $stmt = $this->conn->prepare("
                    INSERT INTO denuncia_categoria (denuncia_id, categoria_id) 
                    VALUES (?, ?)
                ");
                
                foreach ($categorias as $categoria_id) {
                    $stmt->bind_param("ii", $denuncia_id, $categoria_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao vincular categoria: " . $stmt->error);
                    }
                }
            }

            $this->conn->commit();
            
            // Após salvar a denúncia com sucesso, notificar os analistas e gestores
            $this->notificarNovaDenuncia($denuncia_id);
            
            return $protocolo;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao salvar denúncia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Carrega o EmailService apenas quando necessário
     */
    private function getEmailService() {
        if ($this->emailService === null) {
            try {
                require_once __DIR__ . '/../Core/EmailService.php';
                $this->emailService = EmailService::getInstance();
            } catch (Exception $e) {
                error_log("Erro ao carregar EmailService: " . $e->getMessage());
                return null;
            }
        }
        return $this->emailService;
    }

    /**
     * Notifica os analistas e gestores sobre uma nova denúncia
     * 
     * @param int $denunciaId ID da denúncia
     * @return bool True se e-mail enviado, false caso contrário
     */
    private function notificarNovaDenuncia($denunciaId) {
        try {
            // Buscar detalhes da denúncia
            $denuncia = $this->buscarPorId($denunciaId);
            if (!$denuncia) {
                error_log("Denúncia não encontrada para notificação: ID " . $denunciaId);
                return false;
            }
            
            // Buscar analistas e gestores para notificação
            $destinatarios = $this->userModel->buscarAnalistasGestores();
            if (empty($destinatarios)) {
                error_log("Nenhum analista ou gestor encontrado para notificação");
                return false;
            }
            
            // Carregar EmailService apenas quando necessário
            $emailService = $this->getEmailService();
            if (!$emailService) {
                error_log("EmailService não pôde ser carregado");
                return false;
            }
            
            // Formatar destinatários para o serviço de e-mail
            $formattedDestinatarios = array_map(function($user) {
                return [
                    'email' => $user['email'],
                    'nome' => $user['nome']
                ];
            }, $destinatarios);
            
            // Enviar notificação
            return $emailService->enviarNotificacaoNovaDenuncia($denuncia, $formattedDestinatarios);
            
        } catch (Exception $e) {
            error_log("Erro ao notificar sobre nova denúncia: " . $e->getMessage());
            return false;
        }
    }

    public function buscarPorId($id) {
        try {
            // Usar a view existente vw_denuncias_completas
            $stmt = $this->conn->prepare("
                SELECT * FROM vw_denuncias_completas 
                WHERE id = ?
            ");
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $denuncia = $result->fetch_assoc();
            
            if (!$denuncia) {
                error_log("Denúncia não encontrada com ID: " . $id);
                return false;
            }
            
            // Buscar campos adicionais que não estão na view
            $stmt2 = $this->conn->prepare("
                SELECT anexo, ip_denunciante, user_agent, pessoas_envolvidas, 
                       data_ocorrencia, local_ocorrencia, conclusao_descricao,
                       data_atualizacao
                FROM denuncias 
                WHERE id = ?
            ");
            
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $dadosAdicionais = $result2->fetch_assoc();
            
            if ($dadosAdicionais) {
                $denuncia = array_merge($denuncia, $dadosAdicionais);
            }
            
            return $denuncia;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar denúncia por ID: " . $e->getMessage());
            return false;
        }
    }

    public function getDenunciaCompleta($id) {
        $db = Database::getInstance()->getConnection();
        
        // Buscar a denúncia
        $stmt = $db->prepare("
            SELECT d.*, 
                   GROUP_CONCAT(DISTINCT c.nome) as categorias,
                   a.nome as responsavel
            FROM denuncias d
            LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
            LEFT JOIN categorias c ON dc.categoria_id = c.id
            LEFT JOIN admin a ON d.admin_responsavel_id = a.id
            WHERE d.id = ?
            GROUP BY d.id
        ");
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $denuncia = $stmt->get_result()->fetch_assoc();
        
        if (!$denuncia) {
            return null;
        }
        
        // Buscar as respostas
        $stmt = $db->prepare("
            SELECT r.*, a.usuario
            FROM respostas r
            JOIN admin a ON r.admin_id = a.id
            WHERE r.denuncia_id = ?
            ORDER BY r.data_criacao DESC
        ");
        
        $stmt->bind_param("i", $denuncia['id']);
        $stmt->execute();
        $denuncia['respostas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $denuncia;
    }

    public function excluir($protocolo) {
        try {
            $this->conn->begin_transaction();
            
            // Primeiro, buscar todos os dados da denúncia para auditoria
            $stmt = $this->conn->prepare("
                SELECT d.*, 
                       GROUP_CONCAT(c.nome) as categorias,
                       COUNT(h.id) as historico_count
                FROM denuncias d
                LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
                LEFT JOIN categorias c ON dc.categoria_id = c.id
                LEFT JOIN historico_status h ON d.id = h.denuncia_id
                WHERE d.protocolo = ?
                GROUP BY d.id
            ");
            
            $stmt->bind_param("s", $protocolo);
            $stmt->execute();
            $result = $stmt->get_result();
            $denunciaCompleta = $result->fetch_assoc();
            
            if (!$denunciaCompleta) {
                throw new Exception("Denúncia não encontrada");
            }
            
            $denuncia_id = $denunciaCompleta['id'];
            
            // Log de auditoria ANTES da exclusão
            require_once __DIR__ . '/../Core/AuditLogger.php';
            $auditLogger = AuditLogger::getInstance();
            $auditLogger->logDelete(
                'denuncias', 
                $protocolo, 
                $denunciaCompleta,
                "Denúncia excluída permanentemente - Protocolo: {$protocolo}"
            );
            
            // Verificar e excluir registros relacionados
            $tabelas = [
                'historico_status' => "DELETE FROM historico_status WHERE denuncia_id = ?",
                'denuncia_categoria' => "DELETE FROM denuncia_categoria WHERE denuncia_id = ?",
                'respostas' => "DELETE FROM respostas WHERE denuncia_id = ?"
            ];
            
            // Verificar cada tabela antes de tentar excluir
            foreach ($tabelas as $tabela => $sql) {
                // Verificar se a tabela existe
                $result = $this->conn->query("SHOW TABLES LIKE '$tabela'");
                if ($result->num_rows > 0) {
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("i", $denuncia_id);
                    $stmt->execute();
                }
            }
            
            // Finalmente, excluir a denúncia
            $stmt = $this->conn->prepare("DELETE FROM denuncias WHERE id = ?");
            $stmt->bind_param("i", $denuncia_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao excluir denúncia: " . $stmt->error);
            }
            
            // Se houver anexo, excluir o arquivo
            if (!empty($denuncia['anexo'])) {
                $caminhoAnexo = UPLOAD_DIR . '/' . $denuncia['anexo'];
                if (file_exists($caminhoAnexo)) {
                    unlink($caminhoAnexo);
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao excluir denúncia: " . $e->getMessage());
            throw new Exception("Erro ao excluir denúncia: " . $e->getMessage());
        }
    }

    public function buscarDenunciasRelatorio($dataInicio = null, $dataFim = null, $status = null) {
        try {
            // Vamos evitar a subconsulta com JSON_OBJECT para eliminar o problema
            $sql = "SELECT d.*, 
                    GROUP_CONCAT(c.nome) as categorias
                    FROM denuncias d
                    LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
                    LEFT JOIN categorias c ON dc.categoria_id = c.id
                    WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if ($dataInicio) {
                $sql .= " AND DATE(d.data_criacao) >= ?";
                $params[] = $dataInicio;
                $types .= "s";
            }
            
            if ($dataFim) {
                $sql .= " AND DATE(d.data_criacao) <= ?";
                $params[] = $dataFim;
                $types .= "s";
            }
            
            if ($status) {
                $sql .= " AND d.status = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            $sql .= " GROUP BY d.id ORDER BY d.data_criacao DESC";
            
            $stmt = $this->conn->prepare($sql);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $denuncias = $result->fetch_all(MYSQLI_ASSOC);
            
            // Para cada denúncia, buscar o histórico de status separadamente
            foreach ($denuncias as &$denuncia) {
                // Buscar histórico de status
                $sqlHistorico = "
                    SELECT h.*, 
                           DATE_FORMAT(h.data_alteracao, '%d/%m/%Y %H:%i') as data_formatada
                    FROM historico_status h
                    WHERE h.denuncia_id = ?
                    ORDER BY h.data_alteracao ASC
                ";
                
                $stmtHistorico = $this->conn->prepare($sqlHistorico);
                $stmtHistorico->bind_param("i", $denuncia['id']);
                $stmtHistorico->execute();
                $resultHistorico = $stmtHistorico->get_result();
                $historico = $resultHistorico->fetch_all(MYSQLI_ASSOC);
                
                // Formatar o histórico para o formato esperado
                $evolucoes = [];
                foreach ($historico as $item) {
                    // Aqui não nos preocupamos com o nome exato da coluna
                    // Vamos usar todas as colunas disponíveis
                    $evolucao = [
                        'data' => $item['data_formatada'] ?? date('d/m/Y H:i', strtotime($item['data_alteracao'])),
                        'observacao' => $item['observacao'] ?? ''
                    ];
                    
                    // Tentar diferentes nomes de coluna possíveis para o status
                    if (isset($item['novo_status'])) {
                        $evolucao['status'] = $item['novo_status'];
                    } elseif (isset($item['status_novo'])) {
                        $evolucao['status'] = $item['status_novo'];
                    } elseif (isset($item['status'])) {
                        $evolucao['status'] = $item['status'];
                    } else {
                        $evolucao['status'] = 'Atualização'; // Valor padrão
                    }
                    
                    $evolucoes[] = $evolucao;
                }
                
                $denuncia['evolucoes'] = $evolucoes;
            }
            
            return $denuncias;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar denúncias para relatório: " . $e->getMessage());
            throw new Exception("Erro ao buscar denúncias para relatório: " . $e->getMessage());
        }
    }

    public function listarPorResponsavel($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT d.*, 
                       GROUP_CONCAT(c.nome) as categorias,
                       a.nome as responsavel_nome
                FROM denuncias d
                LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
                LEFT JOIN categorias c ON dc.categoria_id = c.id
                LEFT JOIN admin a ON d.admin_responsavel_id = a.id
                WHERE d.admin_responsavel_id = ?
                GROUP BY d.id
                ORDER BY d.data_criacao DESC
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            if ($stmt->error) {
                throw new Exception("Erro ao listar denúncias: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao listar denúncias por responsável: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Invalida cache relacionado às denúncias
     */
    private function invalidateCache($protocolo = null) {
        // Invalidar cache geral de listagem
        $this->cache->delete('denuncias_lista_todas');
        
        // Se protocolo específico, invalidar cache da denúncia
        if ($protocolo) {
            $this->cache->delete("denuncia_{$protocolo}");
        }
        
        // Invalidar cache de estatísticas
        $this->cache->delete('denuncias_stats');
        $this->cache->delete('dashboard_stats');
    }
    
    /**
     * Busca denúncia com cache
     */
    public function consultar($protocolo, $useCache = true) {
        $cacheKey = "denuncia_{$protocolo}";
        
        if ($useCache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        try {
            error_log("Consultando denúncia com protocolo: " . $protocolo);
            
            // Consulta principal da denúncia
            $stmt = $this->conn->prepare("
                SELECT 
                    d.*,
                    GROUP_CONCAT(DISTINCT c.nome) as categorias,
                    a.nome as responsavel_nome,
                    DATE_FORMAT(d.data_criacao, '%d/%m/%Y %H:%i') as data_criacao_formatada,
                    DATE_FORMAT(d.data_atualizacao, '%d/%m/%Y %H:%i') as data_atualizacao_formatada,
                    DATE_FORMAT(d.data_conclusao, '%d/%m/%Y %H:%i') as data_conclusao_formatada,
                    d.status as situacao
                FROM denuncias d
                LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
                LEFT JOIN categorias c ON dc.categoria_id = c.id
                LEFT JOIN admin a ON d.admin_responsavel_id = a.id
                WHERE d.protocolo = ?
                GROUP BY d.id
            ");
            
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $this->conn->error);
            }
            
            $stmt->bind_param("s", $protocolo);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta: " . $stmt->error);
            }
            
            $denuncia = $stmt->get_result()->fetch_assoc();

            if (!$denuncia) {
                error_log("Nenhuma denúncia encontrada para o protocolo: " . $protocolo);
                throw new Exception("Denúncia não encontrada");
            }

            error_log("Denúncia encontrada, buscando histórico...");

            // Garantir que todos os campos obrigatórios existam
            $denuncia['status'] = $denuncia['status'] ?? 'Pendente';
            $denuncia['situacao'] = $denuncia['situacao'] ?? $denuncia['status'];
            $denuncia['data_criacao'] = $denuncia['data_criacao_formatada'] ?? $denuncia['data_criacao'];
            $denuncia['data_atualizacao'] = $denuncia['data_atualizacao_formatada'] ?? $denuncia['data_atualizacao'];
            $denuncia['parecer'] = $denuncia['conclusao_descricao'] ?? '';
            $denuncia['resposta'] = $denuncia['parecer'];

            // Buscar histórico de status
            $stmt = $this->conn->prepare("
                SELECT 
                    h.*,
                    a.nome as admin_nome,
                    DATE_FORMAT(h.data_alteracao, '%d/%m/%Y %H:%i') as data_formatada
                FROM historico_status h
                LEFT JOIN admin a ON h.admin_id = a.id
                WHERE h.denuncia_id = ?
                ORDER BY h.data_alteracao ASC
            ");
            
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta de histórico: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $denuncia['id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar consulta de histórico: " . $stmt->error);
            }
            
            $historico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Formatar histórico para o formato esperado pelo JavaScript
            $historicoFormatado = [];
            foreach ($historico as $item) {
                $historicoFormatado[] = [
                    'data' => $item['data_formatada'] ?? date('d/m/Y H:i', strtotime($item['data_alteracao'])),
                    'status' => $item['status'] ?? 'Atualização',
                    'admin' => $item['admin_nome'] ?? null,
                    'observacao' => $item['observacao'] ?? null
                ];
            }
            
            $denuncia['historico'] = $historicoFormatado;
            
            // Cache por 10 minutos para denúncias individuais
            if ($useCache) {
                $this->cache->set($cacheKey, $denuncia, 600);
            }
            
            error_log("Consulta concluída com sucesso. Status: " . $denuncia['status'] . ", Conclusão: " . ($denuncia['conclusao_descricao'] ?? 'Não disponível'));
            return $denuncia;
            
        } catch (Exception $e) {
            error_log("Erro ao consultar denúncia: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtém estatísticas do dashboard com cache
     */
    public function getStatsForDashboard() {
        return $this->cache->remember('dashboard_stats', function() {
            try {
                $stats = [];
                
                // Total de denúncias
                $result = $this->conn->query("SELECT COUNT(*) as total FROM denuncias");
                $stats['total'] = $result ? $result->fetch_assoc()['total'] : 0;
                
                // Denúncias por status
                $result = $this->conn->query("
                    SELECT status, COUNT(*) as count 
                    FROM denuncias 
                    GROUP BY status
                ");
                
                $stats['por_status'] = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $stats['por_status'][$row['status']] = $row['count'];
                    }
                }
                
                // Denúncias do mês atual
                $result = $this->conn->query("
                    SELECT COUNT(*) as total 
                    FROM denuncias 
                    WHERE MONTH(data_criacao) = MONTH(CURRENT_DATE())
                    AND YEAR(data_criacao) = YEAR(CURRENT_DATE())
                ");
                $stats['mes_atual'] = $result ? $result->fetch_assoc()['total'] : 0;
                
                // Tempo médio de resolução
                $result = $this->conn->query("
                    SELECT AVG(TIMESTAMPDIFF(HOUR, data_criacao, data_conclusao)) as avg_hours
                    FROM denuncias 
                    WHERE status = 'Concluída' 
                    AND data_conclusao IS NOT NULL
                ");
                $avgResult = $result ? $result->fetch_assoc() : null;
                $stats['tempo_medio_resolucao'] = $avgResult ? round($avgResult['avg_hours'], 1) : 0;
                
                return $stats;
                
            } catch (Exception $e) {
                error_log("Erro ao buscar estatísticas: " . $e->getMessage());
                return [];
            }
        }, 900); // Cache por 15 minutos
    }
    
    /**
     * Verifica se uma view existe no banco
     */
    private function checkIfViewExists($viewName) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM information_schema.views 
                WHERE table_schema = ? AND table_name = ?
            ");
            $stmt->bind_param("ss", DB_NAME, $viewName);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtém estatísticas otimizadas usando view
     */
    public function getOptimizedStats() {
        return $this->cache->remember('optimized_stats', function() {
            try {
                // Tentar usar view otimizada primeiro
                if ($this->checkIfViewExists('view_estatisticas_dashboard')) {
                    $result = $this->conn->query("SELECT * FROM view_estatisticas_dashboard");
                    if ($result) {
                        return $result->fetch_assoc();
                    }
                }
                
                // Fallback para consulta manual
                return $this->getStatsForDashboard();
                
            } catch (Exception $e) {
                error_log("Erro ao buscar estatísticas otimizadas: " . $e->getMessage());
                return [];
            }
        }, 600); // Cache por 10 minutos
    }
    
    /**
     * Lista denúncias com paginação otimizada
     */
    public function listarComPaginacao($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        $cacheKey = "denuncias_paginacao_" . md5(serialize([$page, $limit, $filters]));
        
        return $this->cache->remember($cacheKey, function() use ($offset, $limit, $filters) {
            try {
                // Construir WHERE clause baseado nos filtros
                $whereConditions = [];
                $params = [];
                $types = "";
                
                if (!empty($filters['status'])) {
                    $whereConditions[] = "d.status = ?";
                    $params[] = $filters['status'];
                    $types .= "s";
                }
                
                if (!empty($filters['data_inicio'])) {
                    $whereConditions[] = "DATE(d.data_criacao) >= ?";
                    $params[] = $filters['data_inicio'];
                    $types .= "s";
                }
                
                if (!empty($filters['data_fim'])) {
                    $whereConditions[] = "DATE(d.data_criacao) <= ?";
                    $params[] = $filters['data_fim'];
                    $types .= "s";
                }
                
                $whereClause = empty($whereConditions) ? "1=1" : implode(" AND ", $whereConditions);
                
                // Query otimizada com LIMIT
                $sql = "
                    SELECT d.id, d.protocolo, d.status, d.data_criacao, 
                           d.admin_responsavel_id, a.nome as responsavel_nome,
                           GROUP_CONCAT(c.nome) as categorias
                    FROM denuncias d
                    LEFT JOIN admin a ON d.admin_responsavel_id = a.id
                    LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
                    LEFT JOIN categorias c ON dc.categoria_id = c.id
                    WHERE {$whereClause}
                    GROUP BY d.id
                    ORDER BY d.data_criacao DESC
                    LIMIT ? OFFSET ?
                ";
                
                $stmt = $this->conn->prepare($sql);
                
                // Adicionar parâmetros de LIMIT e OFFSET
                $params[] = $limit;
                $params[] = $offset;
                $types .= "ii";
                
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $denuncias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Contar total para paginação
                $countSql = "
                    SELECT COUNT(DISTINCT d.id) as total
                    FROM denuncias d
                    WHERE {$whereClause}
                ";
                
                $countStmt = $this->conn->prepare($countSql);
                if (!empty($whereConditions)) {
                    // Usar apenas os parâmetros do WHERE (sem LIMIT/OFFSET)
                    $filterParams = array_slice($params, 0, -2);
                    $filterTypes = substr($types, 0, -2);
                    if (!empty($filterParams)) {
                        $countStmt->bind_param($filterTypes, ...$filterParams);
                    }
                }
                
                $countStmt->execute();
                $total = $countStmt->get_result()->fetch_assoc()['total'];
                
                return [
                    'data' => $denuncias,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                    'current_page' => $page
                ];
                
            } catch (Exception $e) {
                error_log("Erro na paginação: " . $e->getMessage());
                return [
                    'data' => [],
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1
                ];
            }
        }, 300); // Cache por 5 minutos
    }
}
?> 