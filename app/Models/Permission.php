<?php
require_once __DIR__ . '/../Core/Database.php';

class Permission {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAll() {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM permissions
                ORDER BY nome
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar permissões: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM permissions
                WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao buscar permissão por ID: " . $e->getMessage());
            return null;
        }
    }

    public function create($nome, $descricao, $slug) {
        try {
            // Normalizar o slug
            $slug = $this->normalizarSlug($slug);
            
            $stmt = $this->conn->prepare("
                INSERT INTO permissions (nome, descricao, slug)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $nome, $descricao, $slug);
            $stmt->execute();
            return $this->conn->insert_id;
        } catch (Exception $e) {
            error_log("Erro ao criar permissão: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $nome, $descricao, $slug) {
        try {
            // Normalizar o slug
            $slug = $this->normalizarSlug($slug);
            
            $stmt = $this->conn->prepare("
                UPDATE permissions
                SET nome = ?, descricao = ?, slug = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $nome, $descricao, $slug, $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar permissão: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // Verificar se a permissão está em uso
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM role_permission
                WHERE permission_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception("Não é possível excluir uma permissão que está em uso");
            }
            
            $stmt = $this->conn->prepare("
                DELETE FROM permissions
                WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao excluir permissão: " . $e->getMessage());
            return false;
        }
    }

    public function getRolesWithPermission($permissionId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT r.*
                FROM roles r
                JOIN role_permission rp ON r.id = rp.role_id
                WHERE rp.permission_id = ?
                ORDER BY r.nome
            ");
            $stmt->bind_param("i", $permissionId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar papéis com a permissão: " . $e->getMessage());
            return [];
        }
    }

    private function normalizarSlug($slug) {
        // Converter para minúsculas
        $slug = strtolower($slug);
        // Remover caracteres especiais e substituir espaços por pontos
        $slug = preg_replace('/[^a-z0-9\.]/', '', str_replace(' ', '.', $slug));
        return $slug;
    }
}
?> 