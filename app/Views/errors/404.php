<?php
$pageTitle = 'Página não encontrada';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="error-page mt-5">
                <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                <h1 class="display-1">404</h1>
                <h2 class="mb-4">Página não encontrada</h2>
                <p class="lead mb-4">
                    A página que você está procurando não existe ou foi movida.
                </p>
                <div class="d-grid gap-2 col-md-8 mx-auto">
                    <a href="/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Voltar para o início
                    </a>
                    <a href="/denuncia/criar" class="btn btn-outline-primary">
                        <i class="fas fa-pen me-2"></i>Fazer uma denúncia
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 40px 0;
}

.error-page .display-1 {
    color: var(--action-color);
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.error-page .fas {
    color: var(--action-color);
}

.error-page h2 {
    color: var(--text-color);
}

.error-page .lead {
    color: var(--text-color);
    opacity: 0.8;
}
</style> 