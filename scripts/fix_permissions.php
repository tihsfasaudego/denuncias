<?php
/**
 * Script para corrigir permissões do admin
 */

define('BASE_PATH', __DIR__);
require_once 'config/config.php';

// Auto-loader
spl_autoload_register(function ($className) {
    $dirs = ['app/Controllers/', 'app/Models/', 'app/Core/', 'app/Config/'];
    foreach ($dirs as $dir) {
        $file = BASE_PATH . '/' . $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

try {
    echo "=== CORREÇÃO DE PERMISSÕES ADMIN ===\n\n";
    
    $db = Database::getInstance()->getConnection();
    
    echo "1. Verificando usuários admin...\n";
    
    // Verificar se existem admins
    $result = $db->query("SELECT * FROM admin");
    if ($result->num_rows === 0) {
        echo "Nenhum admin encontrado. Criando admin padrão...\n";
        
        $senha = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO admin (usuario, senha_hash, nome, email, nivel_acesso, ativo) 
            VALUES ('admin', ?, 'Administrador', 'admin@sistema.com', 'admin', 1)
        ");
        $stmt->bind_param("s", $senha);
        $stmt->execute();
        
        echo "✓ Admin criado: admin / admin123\n";
    }
    
    // Listar admins
    $result = $db->query("SELECT id, usuario, nome, nivel_acesso FROM admin");
    while ($row = $result->fetch_assoc()) {
        echo "- ID: {$row['id']} | Usuário: {$row['usuario']} | Nível: {$row['nivel_acesso']}\n";
    }
    
    echo "\n2. Verificando usuários na tabela users...\n";
    
    // Verificar se existem usuários
    $result = $db->query("SELECT * FROM users");
    if ($result->num_rows === 0) {
        echo "Nenhum usuário encontrado. Criando usuário admin padrão...\n";
        
        $senha = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (nome, email, usuario, senha_hash, ativo) 
            VALUES ('Administrador', 'admin@sistema.com', 'admin', ?, 1)
        ");
        $stmt->bind_param("s", $senha);
        $stmt->execute();
        $userId = $db->insert_id;
        
        // Atribuir papel de administrador
        $stmt = $db->prepare("INSERT INTO user_role (user_id, role_id) VALUES (?, 1)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        echo "✓ Usuário criado e papel de admin atribuído\n";
    }
    
    // Listar usuários
    $result = $db->query("
        SELECT u.id, u.usuario, u.nome, GROUP_CONCAT(r.nome) as roles
        FROM users u
        LEFT JOIN user_role ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        GROUP BY u.id
    ");
    while ($row = $result->fetch_assoc()) {
        echo "- ID: {$row['id']} | Usuário: {$row['usuario']} | Roles: " . ($row['roles'] ?? 'Nenhum') . "\n";
    }
    
    echo "\n3. Verificando e corrigindo permissões...\n";
    
    // Verificar se role de admin tem todas as permissões
    $result = $db->query("
        SELECT p.slug
        FROM permissions p
        LEFT JOIN role_permission rp ON p.id = rp.permission_id AND rp.role_id = 1
        WHERE rp.permission_id IS NULL
    ");
    
    $permissoesFaltando = [];
    while ($row = $result->fetch_assoc()) {
        $permissoesFaltando[] = $row['slug'];
    }
    
    if (!empty($permissoesFaltando)) {
        echo "Permissões faltando para admin: " . implode(', ', $permissoesFaltando) . "\n";
        echo "Corrigindo...\n";
        
        // Dar todas as permissões para o admin (role_id = 1)
        $result = $db->query("SELECT id FROM permissions");
        while ($row = $result->fetch_assoc()) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO role_permission (role_id, permission_id) 
                VALUES (1, ?)
            ");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
        }
        
        echo "✓ Permissões corrigidas\n";
    } else {
        echo "✓ Admin já tem todas as permissões\n";
    }
    
    echo "\n4. Testando autenticação...\n";
    
    // Simular sessão de admin
    session_start();
    $_SESSION['admin'] = [
        'id' => 1,
        'nome' => 'Administrador',
        'usuario' => 'admin',
        'nivel_acesso' => 'admin'
    ];
    $_SESSION['admin_last_activity'] = time();
    
    // Testar Auth
    $isAuthenticated = Auth::check();
    echo "Auth::check(): " . ($isAuthenticated ? "✓ OK" : "❌ FALHOU") . "\n";
    
    if ($isAuthenticated) {
        $auth = Auth::getInstance();
        
        // Testar permissões críticas
        $permissoes = [
            'denuncias.view.all',
            'denuncias.view.assigned',
            'denuncias.update.status',
            'denuncias.respond'
        ];
        
        foreach ($permissoes as $perm) {
            $has = $auth->can($perm);
            echo "- {$perm}: " . ($has ? "✓" : "❌") . "\n";
        }
    }
    
    echo "\n5. Testando Controller...\n";
    
    try {
        $controller = new AdminDenunciaController();
        echo "✓ AdminDenunciaController criado com sucesso\n";
        
        $middleware = new AuthMiddleware();
        echo "✓ AuthMiddleware criado com sucesso\n";
        
        // Testar hasPermission
        $hasPerm = $middleware->hasPermission(['denuncias.view.all', 'denuncias.view.assigned'], false);
        echo "hasPermission para view: " . ($hasPerm ? "✓" : "❌") . "\n";
        
    } catch (Exception $e) {
        echo "❌ Erro no controller: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== RESUMO ===\n";
    echo "✓ Admins verificados/criados\n";
    echo "✓ Usuários verificados/criados\n";  
    echo "✓ Permissões corrigidas\n";
    echo "✓ Autenticação testada\n";
    echo "✓ Controllers testados\n";
    
    echo "\n>>> Agora tente acessar:\n";
    echo ">>> https://192.168.2.20:8444/admin/login\n";
    echo ">>> Login: admin\n";
    echo ">>> Senha: admin123\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
