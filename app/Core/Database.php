<?php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new mysqli(
                DB_HOST, 
                DB_USER, 
                DB_PASS, 
                DB_NAME
            );

            if ($this->conn->connect_error) {
                throw new Exception("Erro de conexão: " . $this->conn->connect_error);
            }

            $this->conn->set_charset(DB_CHARSET);
            
            // Garantir que as tabelas essenciais existam
            $this->ensureTablesExist();
            
        } catch (Exception $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            throw new Exception("Erro ao conectar ao banco de dados");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    /**
     * Garante que as tabelas essenciais existam
     */
    private function ensureTablesExist() {
        try {
            // Verificar e criar tabela admin se não existir
            $this->conn->query("
                CREATE TABLE IF NOT EXISTS admin (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario VARCHAR(50) NOT NULL UNIQUE,
                    senha_hash VARCHAR(255) NOT NULL,
                    ativo TINYINT DEFAULT 1,
                    tentativas_login INT DEFAULT 0,
                    bloqueado_ate DATETIME NULL,
                    ultimo_acesso DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Verificar e criar tabela de roles se não existir
            $this->conn->query("
                CREATE TABLE IF NOT EXISTS roles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(50) NOT NULL UNIQUE,
                    descricao TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Verificar e criar tabela de permissões se não existir
            $this->conn->query("
                CREATE TABLE IF NOT EXISTS permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    descricao TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Verificar e criar tabela de relação role-permissão
            $this->conn->query("
                CREATE TABLE IF NOT EXISTS role_permission (
                    role_id INT,
                    permission_id INT,
                    PRIMARY KEY (role_id, permission_id),
                    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
                )
            ");
            
            // Verificar e criar tabela de relação user-role
            $this->conn->query("
                CREATE TABLE IF NOT EXISTS user_role (
                    user_id INT,
                    role_id INT,
                    PRIMARY KEY (user_id, role_id),
                    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
                )
            ");
            
        } catch (Exception $e) {
            error_log("Erro ao criar tabelas: " . $e->getMessage());
        }
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
