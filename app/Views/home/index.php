<?php
$pageTitle = 'Canal de Denúncias - Hospital São Francisco de Assis';
?>

<div class="denuncia-section">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h1>Canal de Denúncias</h1>
                <p class="lead">O Hospital São Francisco de Assis disponibiliza este canal para recebimento de denúncias sobre práticas inadequadas ou violações de conduta ética, garantindo total sigilo e confidencialidade.</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="denuncia-card">
                    <div class="denuncia-card-header">
                        <i class="fas fa-bullhorn"></i>
                        <h2>Nova Denúncia</h2>
                    </div>
                    <div class="denuncia-card-body">
                        <p>Se você presenciou ou tomou conhecimento de alguma conduta inadequada que viole os princípios éticos ou legais do Hospital São Francisco de Assis, utilize este canal para reportar de forma anônima.</p>
                        <ul>
                            <li>Seu anonimato é garantido</li>
                            <li>Todas as informações são criptografadas</li>
                            <li>Você receberá um protocolo para acompanhamento</li>
                        </ul>
                    </div>
                    <div class="denuncia-card-footer">
                        <a href="/denuncia/criar" class="hsfa-btn hsfa-btn-alert">Fazer uma Denúncia</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="denuncia-card">
                    <div class="denuncia-card-header">
                        <i class="fas fa-search"></i>
                        <h2>Consultar Denúncia</h2>
                    </div>
                    <div class="denuncia-card-body">
                        <p>Se você já registrou uma denúncia e deseja verificar o seu andamento, utilize a opção de consulta informando o número de protocolo que foi fornecido no momento do registro.</p>
                        <div class="mt-3">
                            <form action="/denuncia/consultar" method="get" class="d-flex">
                                <input type="text" name="protocolo" placeholder="Digite o número do protocolo" class="hsfa-form-control me-2" required>
                                <button type="submit" class="hsfa-btn hsfa-btn-primary">Consultar</button>
                            </form>
                        </div>
                    </div>
                    <div class="denuncia-card-footer">
                        <a href="/denuncia/consultar" class="hsfa-btn hsfa-btn-outline">Ir para página de consulta</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-lg-12">
                <div class="hsfa-card">
                    <h3><i class="fas fa-shield-alt me-2"></i> Compromisso com a Transparência</h3>
                    <p>O Hospital São Francisco de Assis tem o compromisso de manter um ambiente de trabalho ético e transparente. Todas as denúncias recebidas são tratadas com seriedade e confidencialidade, garantindo que nenhuma retaliação ocorra contra os denunciantes.</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="feature-box text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-user-secret fa-3x" style="color: var(--hsfa-primary);"></i>
                                </div>
                                <h4>Anonimato</h4>
                                <p>Sua identidade é totalmente preservada durante todo o processo.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-box text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-lock fa-3x" style="color: var(--hsfa-primary);"></i>
                                </div>
                                <h4>Segurança</h4>
                                <p>Informações criptografadas e acesso restrito aos dados.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-box text-center">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-check-circle fa-3x" style="color: var(--hsfa-primary);"></i>
                                </div>
                                <h4>Eficiência</h4>
                                <p>Análise rápida e encaminhamento adequado de cada caso.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="bg-accent py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>Saiba mais sobre nossa Política de Conduta</h2>
                <p>Conheça os princípios e valores que orientam as ações e decisões em nossa instituição.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="#" class="hsfa-btn hsfa-btn-secondary">Ver documento</a>
            </div>
        </div>
    </div>
</section>

<style>
.bg-accent {
    background-color: var(--hsfa-accent);
}

.feature-box {
    padding: 1.5rem;
    transition: transform 0.3s ease;
}

.feature-box:hover {
    transform: translateY(-5px);
}

.feature-icon {
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style> 