<?php
require_once __DIR__ . '/../Core/Database.php';

class User {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function authenticate($usuario, $senha) {
        try {
            error_log("🔍 Tentativa de login para usuário: " . $usuario);
            
            // Verificar se o usuário está bloqueado
            $stmt = $this->conn->prepare("
                SELECT u.*, GROUP_CONCAT(r.nome) as roles
                FROM users u
                LEFT JOIN user_role ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.usuario = ? 
                AND u.ativo = 1 
                AND (u.bloqueado_ate IS NULL OR u.bloqueado_ate < NOW())
                GROUP BY u.id
            ");

            if (!$stmt) {
                throw new Exception("Erro na preparação da consulta: " . $this->conn->error);
            }

            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                error_log("❌ Usuário não encontrado ou inativo/bloqueado: " . $usuario);
                $this->registerFailedAttempt($usuario);
                return false;
            }

            if (!isset($user['senha_hash'])) {
                error_log("❌ Campo senha_hash não encontrado para usuário: " . $usuario);
                $this->registerFailedAttempt($usuario);
                return false;
            }

            $senhaValida = password_verify($senha, $user['senha_hash']);
            error_log("🔐 Verificação de senha para " . $usuario . ": " . ($senhaValida ? "válida" : "inválida"));

            if (!$senhaValida) {
                $this->registerFailedAttempt($usuario);
                return false;
            }

            // Limpar tentativas de login após sucesso
            $this->resetLoginAttempts($user['id']);
            // Atualizar último acesso
            $this->updateLastAccess($user['id']);
            error_log("✅ Login bem-sucedido para usuário: " . $usuario);

            // Remover senha hash antes de retornar
            unset($user['senha_hash']);
            return $user;

        } catch (Exception $e) {
            error_log("❌ Erro na autenticação: " . $e->getMessage());
            return false;
        }
    }

    private function registerFailedAttempt($usuario) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
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
                UPDATE users 
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
                UPDATE users 
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

    public function createUser($nome, $email, $usuario, $senha, $roleIds = [1]) {
        try {
            error_log("🔍 Tentando criar usuário: " . $usuario);
            
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            if ($senha_hash === false) {
                throw new Exception("Erro ao gerar hash da senha");
            }
            
            error_log("✅ Hash da senha gerado com sucesso");
            
            $this->conn->begin_transaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO users (nome, email, usuario, senha_hash, ativo) 
                VALUES (?, ?, ?, ?, 1)
            ");

            if (!$stmt) {
                throw new Exception("Erro ao preparar a consulta: " . $this->conn->error);
            }

            error_log("✅ Consulta preparada com sucesso");
            
            $stmt->bind_param("ssss", $nome, $email, $usuario, $senha_hash);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Erro ao executar a consulta: " . $stmt->error);
            }
            
            $userId = $this->conn->insert_id;
            
            // Atribuir papéis ao usuário
            if (!empty($roleIds)) {
                $stmt = $this->conn->prepare("
                    INSERT INTO user_role (user_id, role_id) 
                    VALUES (?, ?)
                ");
                
                foreach ($roleIds as $roleId) {
                    $stmt->bind_param("ii", $userId, $roleId);
                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao atribuir papel: " . $stmt->error);
                    }
                }
            }
            
            $this->conn->commit();
            error_log("✅ Usuário criado com sucesso");
            return $userId;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("❌ Erro ao criar usuário: " . $e->getMessage());
            if ($this->conn->error) {
                error_log("❌ Erro MySQL: " . $this->conn->error);
            }
            throw $e;
        }
    }

    public function atualizarSenha($userId, $novaSenha) {
        try {
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET senha_hash = ?, 
                    tentativas_login = 0,
                    bloqueado_ate = NULL,
                    force_password_change = 0
                WHERE id = ?
            ");
            
            $stmt->bind_param("si", $senhaHash, $userId);
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar senha: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*, GROUP_CONCAT(r.nome) as roles
                FROM users u
                LEFT JOIN user_role ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.id = ?
                GROUP BY u.id
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            return null;
        }
    }

    public function hasPermission($userId, $permissionSlug) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM user_role ur
                JOIN role_permission rp ON ur.role_id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ? AND p.slug = ?
            ");
            
            $stmt->bind_param("is", $userId, $permissionSlug);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão: " . $e->getMessage());
            return false;
        }
    }

    public function getUserRoles($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT r.* 
                FROM roles r
                JOIN user_role ur ON r.id = ur.role_id
                WHERE ur.user_id = ?
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar papéis do usuário: " . $e->getMessage());
            return [];
        }
    }

    public function getUserPermissions($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT p.* 
                FROM permissions p
                JOIN role_permission rp ON p.id = rp.permission_id
                JOIN user_role ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ?
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar permissões do usuário: " . $e->getMessage());
            return [];
        }
    }

    public function listarTodos() {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.nome, u.email, u.usuario, u.ativo, u.ultimo_acesso,
                       GROUP_CONCAT(r.nome) as roles
                FROM users u
                LEFT JOIN user_role ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                GROUP BY u.id
                ORDER BY u.nome
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            return [];
        }
    }
    
    public function atualizarUsuario($id, $nome, $email, $usuario, $ativo = 1, $roles = []) {
        try {
            $this->conn->begin_transaction();
            
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET nome = ?, 
                    email = ?, 
                    usuario = ?, 
                    ativo = ?
                WHERE id = ?
            ");
            
            $stmt->bind_param("sssii", $nome, $email, $usuario, $ativo, $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar usuário: " . $stmt->error);
            }
            
            // Atualizar papéis
            if (!empty($roles)) {
                // Remover papéis existentes
                $stmt = $this->conn->prepare("DELETE FROM user_role WHERE user_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // Adicionar novos papéis
                $stmt = $this->conn->prepare("INSERT INTO user_role (user_id, role_id) VALUES (?, ?)");
                foreach ($roles as $roleId) {
                    $stmt->bind_param("ii", $id, $roleId);
                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao atribuir papel: " . $stmt->error);
                    }
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            return false;
        }
    }
    
    public function excluirUsuario($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao excluir usuário: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca usuários com perfil de analista ou gestor para notificações
     * 
     * @return array Lista de usuários com perfil de analista ou gestor
     */
    public function buscarAnalistasGestores() {
        try {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT u.id, u.nome, u.email, u.usuario, u.ativo, 
                       GROUP_CONCAT(r.nome) as roles
                FROM users u
                JOIN user_role ur ON u.id = ur.user_id
                JOIN roles r ON ur.role_id = r.id
                WHERE r.nome IN ('Analista', 'Gestor') 
                  AND u.ativo = 1
                  AND u.email IS NOT NULL
                  AND u.email != ''
                GROUP BY u.id
                ORDER BY u.nome
            ");
            
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar analistas/gestores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica se a senha fornecida corresponde à senha armazenada para o usuário
     * 
     * @param int $userId ID do usuário
     * @param string $senha Senha a ser verificada
     * @return bool True se a senha corresponde, false caso contrário
     */
    public function verificarSenha($userId, $senha) {
        try {
            $stmt = $this->conn->prepare("SELECT senha_hash FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                return false;
            }
            
            return password_verify($senha, $user['senha_hash']);
        } catch (Exception $e) {
            error_log("Erro ao verificar senha: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status (ativo/inativo) de um usuário
     * 
     * @param int $id ID do usuário
     * @param bool $ativo Status a ser definido (true para ativo, false para inativo)
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function atualizarStatus($id, $ativo) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET ativo = ? WHERE id = ?");
            $ativo = $ativo ? 1 : 0;
            $stmt->bind_param("ii", $ativo, $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar status do usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca um usuário pelo nome de usuário
     * 
     * @param string $username Nome de usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function getByUsername($username) {
        try {
            $stmt = $this->conn->prepare("
                SELECT u.*, GROUP_CONCAT(r.nome) as roles
                FROM users u
                LEFT JOIN user_role ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.usuario = ?
                GROUP BY u.id
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao buscar usuário por username: " . $e->getMessage());
            return null;
        }
    }
}
?> 