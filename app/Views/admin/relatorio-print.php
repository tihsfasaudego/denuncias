<?php
// Definir que esta é uma página de administração
$isAdminPage = true;
$currentPage = 'relatorios';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
        <meta charset="UTF-8">    <title>Relatório de Denúncias - HSFA</title>    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">    <link rel="stylesheet" href="/css/print.css">    <style>
        :root {
            --hsfa-primary: #01717B;
            --hsfa-primary-dark: #015c64;
            --hsfa-secondary: #2E3A55;
            --hsfa-secondary-dark: #222d42;
            --hsfa-text: #060606;
            --hsfa-text-light: #ffffff;
            --hsfa-bg: #CBE9E7;
            --hsfa-bg-soft: #e5f4f3;
            --hsfa-meta: #EBE84C;
            --hsfa-alert: #F05973;
            --hsfa-success: #52B788;
            --hsfa-neutral: #6c757d;
            --border-radius: 8px;
        }

        /* Estilos para impressão */
        @media print {
            @page {
                size: A4;
                margin: 1.5cm;
            }
            
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .header, .footer {
                position: fixed;
                width: 100%;
                left: 0;
            }
            
            .header {
                top: 0;
            }
            
            .footer {
                bottom: 0;
            }
            
            .content {
                margin-top: 3cm;
                margin-bottom: 2.5cm;
            }
        }
        
        /* Estilos gerais */
        body {
            font-family: 'Roboto', 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: var(--hsfa-text);
            background-color: var(--hsfa-bg-soft);
        }
        
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Cabeçalho */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 3px solid var(--hsfa-primary);
            background-color: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        
        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        
        .titulo-relatorio {
            font-size: 28px;
            margin: 20px 0 10px;
            color: var(--hsfa-primary);
            font-weight: 700;
        }
        
        .subtitulo-relatorio {
            font-size: 18px;
            margin: 0 0 20px;
            color: var(--hsfa-secondary);
            font-weight: 500;
        }
        
        /* Conteúdo */
        .content {
            padding: 25px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin: 20px 0;
        }
        
        /* Controles de impressão */
        .print-controls {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .btn-print {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background-color: var(--hsfa-primary);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-print:hover {
            background-color: var(--hsfa-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .btn-print i {
            margin-right: 10px;
        }
        
        /* Tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background-color: #fff;
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        th, td {
            border: 1px solid #e9ecef;
            padding: 14px;
            text-align: left;
        }
        
        th {
            background-color: var(--hsfa-primary);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        tr:nth-child(even) {
            background-color: var(--hsfa-bg-soft);
        }
        
        tr:hover {
            background-color: rgba(203, 233, 231, 0.4);
        }
        
        /* Detalhes da denúncia */
        .denuncia-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            background-color: white;
        }

        .denuncia-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-3px);
        }
        
        .denuncia-header {
            background-color: var(--hsfa-primary);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .denuncia-detalhes {
            padding: 20px;
            background-color: #fff;
        }

        .denuncia-info {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            gap: 30px;
            border-bottom: 1px solid var(--hsfa-bg);
            padding-bottom: 15px;
        }

        .denuncia-info-item {
            display: flex;
            flex-direction: column;
        }

        .denuncia-info-label {
            font-weight: 600;
            color: var(--hsfa-secondary);
            margin-bottom: 5px;
            font-size: 14px;
        }

        .denuncia-info-value {
            font-size: 16px;
        }
        
        .evolucao {
            margin: 15px 0;
            padding: 15px;
            background-color: var(--hsfa-bg-soft);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .evolucao-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(1, 113, 123, 0.2);
            font-weight: 600;
        }
        
        .evolucao-content {
            margin-top: 10px;
            white-space: pre-line;
            line-height: 1.7;
        }
        
        /* Rodapé */
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            border-top: 3px solid var(--hsfa-primary);
            font-size: 14px;
            background-color: white;
            box-shadow: 0 -2px 15px rgba(0,0,0,0.05);
        }
        
        /* Resumo */
        .resumo {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 25px;
            margin: 30px 0;
            border-top: 5px solid var(--hsfa-primary);
        }
        
        .resumo h3 {
            margin-top: 0;
            color: var(--hsfa-primary);
            font-size: 22px;
            border-bottom: 2px solid var(--hsfa-bg);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .resumo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .status-card {
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .status-card-count {
            font-size: 30px;
            font-weight: 700;
            margin: 10px 0;
        }

        .status-card-label {
            font-weight: 600;
            font-size: 16px;
        }
        
        /* Status badges */
        .status {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .status i {
            margin-right: 5px;
        }
        
        .status-pendente {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-analise, .status-em-analise, .status-investigação, .status-em-investigacao {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-concluida, .status-concluída {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-arquivada {
            background-color: #E2E3E5;
            color: #383D41;
        }

        /* Estatísticas visuais */
        .total-card {
            background: linear-gradient(135deg, var(--hsfa-primary) 0%, var(--hsfa-primary-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 16px rgba(1, 113, 123, 0.2);
        }

        .total-card h2 {
            font-size: 50px;
            margin: 10px 0;
            font-weight: 700;
        }

        .total-card p {
            font-size: 20px;
            margin: 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="/css/images/logo1.png" alt="Logo HSFA" class="logo">
            <h1 class="titulo-relatorio">Relatório de Denúncias</h1>
            <p class="subtitulo-relatorio">Período: <?php echo $dataInicio ? date('d/m/Y', strtotime($dataInicio)) : 'Início'; ?> 
               até <?php echo $dataFim ? date('d/m/Y', strtotime($dataFim)) : 'Atual'; ?>
               <?php echo $status ? ' | Status: ' . htmlspecialchars($status) : ''; ?>
            </p>
        </div>

        <div class="print-controls no-print">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Imprimir / Salvar PDF
            </button>
        </div>
        
        <div class="content">
            <div class="total-card">
                <p>Total de Denúncias</p>
                <h2><?php echo count($denuncias); ?></h2>
            </div>

            <div class="resumo">
                <h3><i class="fas fa-chart-pie me-2"></i> Resumo do Relatório</h3>
                <?php
                $statusCount = [];
                $statusIcons = [
                    'Pendente' => 'fas fa-clock',
                    'Em Análise' => 'fas fa-search',
                    'Em Investigação' => 'fas fa-magnifying-glass',
                    'Concluída' => 'fas fa-check-circle',
                    'Arquivada' => 'fas fa-archive'
                ];
                $statusColors = [
                    'Pendente' => '#F37A47',
                    'Em Análise' => '#01717B',
                    'Em Investigação' => '#01717B',
                    'Concluída' => '#52B788',
                    'Arquivada' => '#6c757d'
                ];
                
                foreach ($denuncias as $denuncia) {
                    $status = $denuncia['status'] ?? 'Pendente';
                    $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
                }
                ?>
                
                <div class="resumo-grid">
                    <?php foreach ($statusCount as $status => $count): ?>
                    <div class="status-card" style="background-color: <?php echo $statusColors[$status] ?? '#01717B'; ?>20; border-left: 4px solid <?php echo $statusColors[$status] ?? '#01717B'; ?>;">
                        <i class="<?php echo $statusIcons[$status] ?? 'fas fa-clipboard-list'; ?>" style="font-size: 24px; color: <?php echo $statusColors[$status] ?? '#01717B'; ?>;"></i>
                        <div class="status-card-count" style="color: <?php echo $statusColors[$status] ?? '#01717B'; ?>;"><?php echo $count; ?></div>
                        <div class="status-card-label"><?php echo htmlspecialchars($status); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php foreach ($denuncias as $index => $denuncia): ?>
                <div class="denuncia-card">
                    <div class="denuncia-header">
                        <span><i class="fas fa-file-alt me-2"></i> Protocolo: <?php echo htmlspecialchars($denuncia['protocolo']); ?></span>
                        <span>#<?php echo $index + 1; ?></span>
                    </div>
                    <div class="denuncia-detalhes">
                        <div class="denuncia-info">
                            <div class="denuncia-info-item">
                                <span class="denuncia-info-label"><i class="fas fa-calendar me-1"></i> Data de Registro</span>
                                <span class="denuncia-info-value"><?php echo date('d/m/Y', strtotime($denuncia['data_criacao'])); ?></span>
                            </div>
                            
                            <div class="denuncia-info-item">
                                <span class="denuncia-info-label"><i class="fas fa-tag me-1"></i> Status</span>
                                <span class="denuncia-info-value">
                                    <span class="status status-<?php echo strtolower(str_replace(' ', '-', str_replace('í', 'i', $denuncia['status']))); ?>">
                                        <i class="<?php echo $statusIcons[$denuncia['status']] ?? 'fas fa-clipboard-list'; ?>"></i>
                                        <?php echo htmlspecialchars($denuncia['status']); ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="denuncia-info-item">
                                <span class="denuncia-info-label"><i class="fas fa-folder me-1"></i> Categoria</span>
                                <span class="denuncia-info-value"><?php echo htmlspecialchars($denuncia['categorias'] ?? 'Não categorizada'); ?></span>
                            </div>
                        </div>
                        
                        <div>
                            <h4><i class="fas fa-align-left me-2"></i> Descrição</h4>
                            <div class="evolucao">
                                <?php echo nl2br(htmlspecialchars($denuncia['descricao'])); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($denuncia['evolucoes'])): ?>
                            <div style="margin-top: 20px;">
                                <h4><i class="fas fa-history me-2"></i> Histórico de Evoluções</h4>
                                <?php foreach ($denuncia['evolucoes'] as $evolucao): ?>
                                    <div class="evolucao">
                                        <div class="evolucao-header">
                                            <span>
                                                <i class="<?php echo $statusIcons[$evolucao['status'] ?? 'Atualização'] ?? 'fas fa-clipboard-list'; ?> me-1"></i>
                                                <?php echo htmlspecialchars($evolucao['status'] ?? 'Atualização'); ?>
                                            </span>
                                            <span><i class="fas fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($evolucao['data'])); ?></span>
                                        </div>
                                        <div class="evolucao-content">
                                            <?php echo nl2br(htmlspecialchars($evolucao['observacao'] ?? '')); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (($index + 1) % 3 == 0 && ($index + 1) < count($denuncias)): ?>
                <div class="page-break"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>
                <strong>Hospital São Francisco de Assis</strong><br>
                R. 9-A, 110 - St. Aeroporto, Goiânia - GO, 74075-250<br>
                Telefone: (62) 3221-8000
            </p>
            <p>Relatório gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html> 