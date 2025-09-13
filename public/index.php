<?php
/**
 * Arquivo de entrada principal da aplicação
 * 
 * Sistema de rotas centralizado e organizado
 */

// Definir constantes
define('BASE_PATH', dirname(__DIR__));

// Configuração de erros baseada no ambiente
if (defined('APP_ENV') && APP_ENV === 'production') {
    // Produção: Log erros mas não exibir
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Desenvolvimento: Exibir erros se debug habilitado
    if (defined('APP_DEBUG') && APP_DEBUG) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    }
    ini_set('log_errors', 1);
}
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Verificar e criar diretório de logs se não existir
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0777, true);
}

// Carregar configuração
require_once BASE_PATH . '/config/config.php';

// Auto-loader para controllers, models e classes Core
spl_autoload_register(function ($className) {
    // Verificar se é um controller
    if (strpos($className, 'Controller') !== false) {
        $file = BASE_PATH . '/app/Controllers/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Verificar se é um model
    $file = BASE_PATH . '/app/Models/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Verificar se é uma classe Core
    $file = BASE_PATH . '/app/Core/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Classe Config
    $file = BASE_PATH . '/app/Config/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

try {
    // Verificar se é uma rota de dados de denúncia
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\/admin\/denuncia\/(\d+)\/dados/', $requestUri, $matches)) {
        $denunciaId = $matches[1];
        
        // Processar diretamente sem router
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT * FROM denuncias WHERE id = ?");
        $stmt->bind_param("i", $denunciaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $denuncia = $result->fetch_assoc();
        
        $conn->close();
        
        if (!$denuncia) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Denúncia não encontrada']);
        } else {
            echo json_encode(['success' => true, 'denuncia' => $denuncia]);
        }
        exit;
    }
    
    // Verificar se é uma rota de atualização de status
    if (preg_match('/\/admin\/denuncia\/(\d+)\/status/', $requestUri, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $denunciaId = $matches[1];
        
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $status = $_POST['status'] ?? '';
        if (empty($status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Status não informado']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE denuncias SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $denunciaId);
        $success = $stmt->execute();
        
        $conn->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Status atualizado']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
        }
        exit;
    }
    
    // Verificar se é uma rota de alteração de prioridade
    if (preg_match('/\/admin\/denuncia\/(\d+)\/prioridade/', $requestUri, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $denunciaId = $matches[1];
        
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $prioridade = $_POST['prioridade'] ?? '';
        $justificativa = $_POST['justificativa'] ?? '';
        
        if (empty($prioridade)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Prioridade não informada']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE denuncias SET prioridade = ? WHERE id = ?");
        $stmt->bind_param("si", $prioridade, $denunciaId);
        $success = $stmt->execute();
        
        $conn->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Prioridade alterada com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar prioridade']);
        }
        exit;
    }
    
    // Verificar se é uma rota de atribuição de responsável
    if (preg_match('/\/admin\/denuncia\/(\d+)\/atribuir/', $requestUri, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $denunciaId = $matches[1];
        
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $adminId = $_POST['admin_id'] ?? '';
        $observacao = $_POST['observacao'] ?? '';
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        // Se admin_id estiver vazio, remover atribuição
        if (empty($adminId)) {
            $stmt = $conn->prepare("UPDATE denuncias SET admin_responsavel_id = NULL WHERE id = ?");
            $stmt->bind_param("i", $denunciaId);
        } else {
            $stmt = $conn->prepare("UPDATE denuncias SET admin_responsavel_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $adminId, $denunciaId);
        }
        
        $success = $stmt->execute();
        $conn->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Responsável atribuído com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atribuir responsável']);
        }
        exit;
    }
    
    // Verificar se é uma rota para listar admins
    if (preg_match('/\/admin\/usuarios\/listar-admins/', $requestUri) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        $result = $conn->query("SELECT id, nome, email, nivel_acesso FROM admin WHERE ativo = 1 ORDER BY nome");
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
        
        $conn->close();
        
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
        exit;
    }
    
    // Verificar se é uma rota para criar usuário
    if (preg_match('/\/admin\/usuarios\/criar/', $requestUri) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $nome = $_POST['nome'] ?? '';
        $usuario = $_POST['usuario'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $nivelAcesso = $_POST['nivel_acesso'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        if (empty($nome) || empty($usuario) || empty($email) || empty($senha) || empty($nivelAcesso)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
            exit;
        }
        
        if (strlen($senha) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        // Verificar se usuário ou email já existem
        $stmt = $conn->prepare("SELECT id FROM admin WHERE usuario = ? OR email = ?");
        $stmt->bind_param("ss", $usuario, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conn->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Usuário ou email já existem']);
            exit;
        }
        
        // Criar hash da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Inserir novo usuário
        $stmt = $conn->prepare("
            INSERT INTO admin (usuario, senha_hash, nome, email, nivel_acesso, ativo) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssi", $usuario, $senhaHash, $nome, $email, $nivelAcesso, $ativo);
        $success = $stmt->execute();
        
        $conn->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário']);
        }
        exit;
    }
    
    // Verificar se é uma rota para obter dados de usuário
    if (preg_match('/\/admin\/usuarios\/(\d+)\/dados/', $requestUri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $usuarioId = $matches[1];
        
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT id, usuario, nome, email, nivel_acesso, ativo FROM admin WHERE id = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        
        $conn->close();
        
        if ($usuario) {
            echo json_encode(['success' => true, 'usuario' => $usuario]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        }
        exit;
    }
    
    // Verificar se é uma rota para atualizar usuário
    if (preg_match('/\/admin\/usuarios\/(\d+)\/atualizar/', $requestUri, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuarioId = $matches[1];
        
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $nome = $_POST['nome'] ?? '';
        $usuario = $_POST['usuario'] ?? '';
        $email = $_POST['email'] ?? '';
        $nivelAcesso = $_POST['nivel_acesso'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        if (empty($nome) || empty($usuario) || empty($email) || empty($nivelAcesso)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        // Verificar se usuário ou email já existem (exceto o próprio)
        $stmt = $conn->prepare("SELECT id FROM admin WHERE (usuario = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $usuario, $email, $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conn->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Usuário ou email já existem']);
            exit;
        }
        
        // Atualizar usuário
        $stmt = $conn->prepare("
            UPDATE admin 
            SET usuario = ?, nome = ?, email = ?, nivel_acesso = ?, ativo = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssssii", $usuario, $nome, $email, $nivelAcesso, $ativo, $usuarioId);
        $success = $stmt->execute();
        
        $conn->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário']);
        }
        exit;
    }
    
    // Verificar se é uma rota para alterar status de usuário
    if (preg_match('/\/admin\/usuarios\/(\d+)\/status/', $requestUri, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuarioId = $matches[1];
        
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $novoStatus = $input['status'] ?? '';
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE admin SET ativo = ? WHERE id = ?");
        $stmt->bind_param("ii", $novoStatus, $usuarioId);
        $success = $stmt->execute();
        
        $conn->close();
        
        if ($success) {
            $acao = $novoStatus == 1 ? 'ativado' : 'bloqueado';
            echo json_encode(['success' => true, 'message' => "Usuário $acao com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar status']);
        }
        exit;
    }
    
    // Verificar se é uma rota para excluir usuário
    if (preg_match('/\/admin\/usuarios\/(\d+)\/excluir/', $requestUri, $matches) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $usuarioId = $matches[1];
        
        session_start();
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        // Não permitir excluir a si mesmo
        if ($usuarioId == $_SESSION['admin']['id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Não é possível excluir seu próprio usuário']);
            exit;
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->bind_param("i", $usuarioId);
        $success = $stmt->execute();
        
        $conn->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir usuário']);
        }
        exit;
    }
    
    // Verificar se é uma rota de geração de relatório
    if (preg_match('/\/admin\/relatorios\/gerar/', $requestUri) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        session_start();
        
        if (!isset($_SESSION['admin'])) {
            http_response_code(401);
            echo '<h1>Acesso Negado</h1><p>Você precisa estar logado como administrador.</p>';
            exit;
        }
        
        // Administrador tem acesso total - gerar relatório
        try {
            $dataInicio = $_GET['data_inicio'] ?? null;
            $dataFim = $_GET['data_fim'] ?? null;
            $status = $_GET['status'] ?? null;
            $formato = $_GET['formato'] ?? 'html';
            
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception('Erro de conexão: ' . $conn->connect_error);
            }
            
            // Construir query
            $sql = "SELECT d.*, 
                           GROUP_CONCAT(c.nome) as categorias,
                           a.nome as responsavel
                    FROM denuncias d
                    LEFT JOIN denuncia_categoria dc ON d.id = dc.denuncia_id
                    LEFT JOIN categorias c ON dc.categoria_id = c.id
                    LEFT JOIN admin a ON d.admin_responsavel_id = a.id
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
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $denuncias = $result->fetch_all(MYSQLI_ASSOC);
            
            $conn->close();
            
            // Gerar relatório baseado no formato
            if ($formato === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="relatorio-denuncias.csv"');
                
                echo "\xEF\xBB\xBF"; // BOM para UTF-8
                echo "Protocolo,Data,Status,Prioridade,Categoria,Responsavel,Descricao\n";
                
                foreach ($denuncias as $denuncia) {
                    echo '"' . $denuncia['protocolo'] . '",';
                    echo '"' . date('d/m/Y', strtotime($denuncia['data_criacao'])) . '",';
                    echo '"' . $denuncia['status'] . '",';
                    echo '"' . ($denuncia['prioridade'] ?? '') . '",';
                    echo '"' . ($denuncia['categorias'] ?? '') . '",';
                    echo '"' . ($denuncia['responsavel'] ?? '') . '",';
                    echo '"' . str_replace('"', '""', $denuncia['descricao']) . '"' . "\n";
                }
            } else {
                // HTML
                echo '<h1>Relatório de Denúncias</h1>';
                echo '<p>Período: ' . ($dataInicio ? date('d/m/Y', strtotime($dataInicio)) : 'Início') . 
                     ' até ' . ($dataFim ? date('d/m/Y', strtotime($dataFim)) : 'Hoje') . '</p>';
                echo '<p>Total: ' . count($denuncias) . ' denúncia(s)</p>';
                
                if (!empty($denuncias)) {
                    echo '<table border="1" cellpadding="5" cellspacing="0">';
                    echo '<tr><th>Protocolo</th><th>Data</th><th>Status</th><th>Descrição</th></tr>';
                    
                    foreach ($denuncias as $denuncia) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($denuncia['protocolo']) . '</td>';
                        echo '<td>' . date('d/m/Y', strtotime($denuncia['data_criacao'])) . '</td>';
                        echo '<td>' . htmlspecialchars($denuncia['status']) . '</td>';
                        echo '<td>' . htmlspecialchars(substr($denuncia['descricao'], 0, 100)) . '...</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p>Nenhuma denúncia encontrada para os critérios selecionados.</p>';
                }
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo '<h1>Erro</h1><p>Erro ao gerar relatório: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        exit;
    }
    
    // Carregar e executar o gerenciador de rotas
    require_once BASE_PATH . '/app/Core/RouteManager.php';
    
    $routeManager = new RouteManager();
    $routeManager->registerRoutes();
    $routeManager->run();
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro crítico na aplicação: " . $e->getMessage());
    
    // Exibir erro amigável
    http_response_code(500);
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Erro na Aplicação</h1>";
        echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "<h1>Erro interno do servidor</h1>";
        echo "<p>Ocorreu um erro interno. Por favor, tente novamente mais tarde.</p>";
    }
    exit;
}
?>
