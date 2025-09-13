<?php
/**
 * Página de erro genérica
 * 
 * Esta página é usada para exibir erros como 404, 500, acesso negado, etc.
 */
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="hsfa-card text-center">
                <div class="error-icon mb-4">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h1>Ocorreu um erro</h1>
                <p class="lead"><?= htmlspecialchars($error) ?></p>
                <div class="mt-4">
                    <a href="/" class="hsfa-btn hsfa-btn-primary">Voltar para Página Inicial</a>
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