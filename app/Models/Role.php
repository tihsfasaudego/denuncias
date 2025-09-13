<?php
require_once __DIR__ . '/../Core/Database.php';

class Role {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAll() {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM roles
                ORDER BY nivel DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar papéis: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM roles
                WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao buscar papel por ID: " . $e->getMessage());
            return null;
        }
    }

    public function create($nome, $descricao, $nivel) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO roles (nome, descricao, nivel)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("ssi", $nome, $descricao, $nivel);
            $stmt->execute();
            return $this->conn->insert_id;
        } catch (Exception $e) {
            error_log("Erro ao criar papel: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $nome, $descricao, $nivel) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE roles
                SET nome = ?, descricao = ?, nivel = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssii", $nome, $descricao, $nivel, $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar papel: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // Verificar se existem usuários com este papel
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM user_role
                WHERE role_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception("Não é possível excluir um papel que está em uso por usuários");
            }
            
            // Remover as permissões associadas ao papel
            $stmt = $this->conn->prepare("
                DELETE FROM role_permission
                WHERE role_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Excluir o papel
            $stmt = $this->conn->prepare("
                DELETE FROM roles
                WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao excluir papel: " . $e->getMessage());
            return false;
        }
    }

    public function getRolePermissions($roleId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*
                FROM permissions p
                JOIN role_permission rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                ORDER BY p.nome
            ");
            $stmt->bind_param("i", $roleId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar permissões do papel: " . $e->getMessage());
            return [];
        }
    }

    public function updateRolePermissions($roleId, $permissionIds) {
        try {
            $this->conn->begin_transaction();
            
            // Remover permissões existentes
            $stmt = $this->conn->prepare("
                DELETE FROM role_permission
                WHERE role_id = ?
            ");
            $stmt->bind_param("i", $roleId);
            $stmt->execute();
            
            // Adicionar novas permissões
            if (!empty($permissionIds)) {
                $stmt = $this->conn->prepare("
                    INSERT INTO role_permission (role_id, permission_id)
                    VALUES (?, ?)
                ");
                
                foreach ($permissionIds as $permissionId) {
                    $stmt->bind_param("ii", $roleId, $permissionId);
                    $stmt->execute();
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Erro ao atualizar permissões do papel: " . $e->getMessage());
            return false;
        }
    }
}
?> 