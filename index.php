<?php
/**
 * Redirecionamento para public/index.php
 * Este arquivo garante que as rotas funcionem corretamente
 */

// Verificar se estamos acessando diretamente o arquivo raiz
if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') {
    // Redirecionar para a pasta public
    header('Location: /public/');
    exit;
}

// Para todas as outras rotas, incluir o index.php da pasta public
require_once __DIR__ . '/public/index.php';
?>
