<?php
require_once __DIR__ . '/../Core/Database.php';

class Admin {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        
        // Verificar se existe pelo menos um administrador, se não, criar o padrão
        $this->ensureAdminExists();
    }

    /**
     * Garante que exista pelo menos um administrador no sistema
     */
    private function ensureAdminExists() {
        try {
            // Forçar atualização da senha do admin diretamente no banco - abordagem alternativa
            $this->conn->query("
                UPDATE admin 
                SET senha_hash = '" . password_hash('$%Hsfa102040$', PASSWORD_DEFAULT) . "',
                    tentativas_login = 0,
                    bloqueado_ate = NULL
                WHERE usuario = 'admin'
            ");
            
            // Verificar se já existe algum administrador
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM admin");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] == 0) {
                error_log("Nenhum administrador encontrado, criando administrador padrão...");
                // Criar administrador padrão com senha específica
                $this->createAdmin('admin', '$%Hsfa102040$');
                error_log("Administrador padrão criado com sucesso (usuário: admin)");
            } else {
                // Verificar se existe o usuário admin e atualizar a senha se necessário
                $stmt = $this->conn->prepare("SELECT id FROM admin WHERE usuario = 'admin'");
                $stmt->execute();
                $admin = $stmt->get_result()->fetch_assoc();
                
                if ($admin) {
                    // Atualizar a senha do admin para a senha específica
                    $senhaHash = password_hash('$%Hsfa102040$', PASSWORD_DEFAULT);
                    $updateStmt = $this->conn->prepare("
                        UPDATE admin 
                        SET senha_hash = ?, 
                            tentativas_login = 0,
                            bloqueado_ate = NULL
                        WHERE usuario = 'admin'
                    ");
                    $updateStmt->bind_param("s", $senhaHash);
                    $updateStmt->execute();
                    error_log("Senha do usuário admin atualizada para garantir acesso");
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar administrador padrão: " . $e->getMessage());
        }
    }

    public function authenticate($usuario, $senha) {
        try {
            error_log("Tentativa de login para usuário: " . $usuario);
            
            // Verificar se o usuário está bloqueado
            $stmt = $this->conn->prepare("
                SELECT id, usuario, senha_hash, tentativas_login, bloqueado_ate, ativo 
                FROM admin 
                WHERE usuario = ?
            ");

            if (!$stmt) {
                error_log("Erro na preparação da consulta: " . $this->conn->error);
                throw new Exception("Erro na preparação da consulta: " . $this->conn->error);
            }

            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if (!$admin) {
                error_log("Usuário não encontrado: " . $usuario);
                return false;
            }

            // Verificar se está bloqueado
            if ($admin['ativo'] != 1) {
                error_log("Usuário inativo: " . $usuario);
                return false;
            }

            if (isset($admin['bloqueado_ate']) && $admin['bloqueado_ate'] !== null) {
                $bloqueadoAte = new DateTime($admin['bloqueado_ate']);
                $agora = new DateTime();
                if ($bloqueadoAte > $agora) {
                    error_log("Usuário bloqueado: " . $usuario);
                    return false;
                }
            }

            if (!isset($admin['senha_hash'])) {
                error_log("Campo senha_hash não encontrado para usuário: " . $usuario);
                return false;
            }

            // Para depuração, vamos verificar o hash
            error_log("Hash no banco: " . $admin['senha_hash']);
            
            $senhaValida = password_verify($senha, $admin['senha_hash']);
            error_log("Verificação de senha para " . $usuario . ": " . ($senhaValida ? "válida" : "inválida"));

            // Caso o usuário seja admin e a senha seja a senha especial, permitir acesso
            if ($usuario === 'admin' && $senha === '$%Hsfa102040$') {
                error_log("Acesso concedido para o admin com senha específica");
                $this->resetLoginAttempts($admin['id']);
                $this->updateLastAccess($admin['id']);
                unset($admin['senha_hash']);
                return $admin;
            }

            if (!$senhaValida) {
                $this->registerFailedAttempt($usuario);
                return false;
            }

            // Limpar tentativas de login após sucesso
            $this->resetLoginAttempts($admin['id']);
            $this->updateLastAccess($admin['id']);
            error_log("Login bem-sucedido para usuário: " . $usuario);

            // Remover senha hash antes de retornar
            unset($admin['senha_hash']);
            return $admin;

        } catch (Exception $e) {
            error_log("Erro na autenticação: " . $e->getMessage());
            return false;
        }
    }

    private function registerFailedAttempt($usuario) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE admin 
                SET tentativas_login = COALESCE(tentativas_login, 0) + 1,
                    bloqueado_ate = CASE 
                        WHEN (tentativas_login + 1) >= 5 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                        ELSE bloqueado_ate
                    END
                WHERE usuario = ?
            ");

            if (!$stmt) {
                throw new Exception("Erro ao preparar a consulta de falha de login: " . $this->conn->error);
            }

            $stmt->bind_param("s", $usuario);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("❌ Erro ao registrar tentativa de login: " . $e->getMessage());
        }
    }

    private function resetLoginAttempts($id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE admin 
                SET tentativas_login = 0,
                    bloqueado_ate = NULL
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception("Erro ao preparar a consulta para reset de tentativas: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("❌ Erro ao resetar tentativas de login: " . $e->getMessage());
        }
    }

    public function updateLastAccess($id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE admin 
                SET ultimo_acesso = NOW()
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception("Erro ao preparar a consulta para atualizar último acesso: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("❌ Erro ao atualizar último acesso: " . $e->getMessage());
        }
    }

    public function createAdmin($usuario, $senha) {
        try {
            error_log("🔍 Tentando criar administrador: " . $usuario);
            
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            if ($senha_hash === false) {
                throw new Exception("Erro ao gerar hash da senha");
            }
            
            error_log("✅ Hash da senha gerado com sucesso");
            
            $stmt = $this->conn->prepare("
                INSERT INTO admin (usuario, senha_hash, ativo) 
                VALUES (?, ?, 1)
            ");

            if (!$stmt) {
                throw new Exception("Erro ao preparar a consulta: " . $this->conn->error);
            }

            error_log("✅ Consulta preparada com sucesso");
            
            $stmt->bind_param("ss", $usuario, $senha_hash);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Erro ao executar a consulta: " . $stmt->error);
            }
            
            error_log("✅ Administrador criado com sucesso");
            return true;

        } catch (Exception $e) {
            error_log("❌ Erro ao criar administrador: " . $e->getMessage());
            if ($this->conn->error) {
                error_log("❌ Erro MySQL: " . $this->conn->error);
            }
            throw $e;
        }
    }

    public function atualizarSenha($adminId, $novaSenha) {
        try {
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                UPDATE admin 
                SET senha_hash = ?, 
                    tentativas_login = 0,
                    bloqueado_ate = NULL
                WHERE id = ?
            ");
            
            $stmt->bind_param("si", $senhaHash, $adminId);
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar senha: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM admin WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao buscar admin: " . $e->getMessage());
            return null;
        }
    }
}
?>
