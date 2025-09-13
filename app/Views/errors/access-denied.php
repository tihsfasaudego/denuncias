<?php
/**
 * Página de acesso negado
 * 
 * Esta página é exibida quando o usuário tenta acessar um recurso ao qual não tem permissão.
 */
$code = 403;
$pageTitle = 'Acesso Negado';
$message = 'Você não tem permissão para acessar esta página ou recurso.';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="hsfa-card text-center">
                <div class="error-icon mb-4">
                    <i class="fas fa-lock"></i>
                </div>
                <h1>Acesso Negado</h1>
                <p class="lead">Desculpe, você não tem permissão para acessar esta página.</p>
                <div class="mt-4">
                    <?php if (isset($isAdminPage) && $isAdminPage): ?>
                        <a href="/admin/dashboard" class="hsfa-btn hsfa-btn-primary">Voltar ao Dashboard</a>
                    <?php else: ?>
                        <a href="/" class="hsfa-btn hsfa-btn-primary">Voltar para Página Inicial</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-icon {
    font-size: 6rem;
    color: var(--hsfa-alert);
}
</style> 