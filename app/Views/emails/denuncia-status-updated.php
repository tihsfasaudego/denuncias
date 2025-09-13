<title>Status Atualizado - Protocolo {{protocolo}}</title>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .content {
            padding: 30px;
        }
        
        .status-change {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .status-from,
        .status-to {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 0 10px;
        }
        
        .status-from {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-to {
            background: #d4edda;
            color: #155724;
        }
        
        .arrow {
            font-size: 24px;
            color: #6c757d;
            margin: 0 10px;
        }
        
        .protocol-box {
            background: #e7f3ff;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        
        .protocol-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
            letter-spacing: 2px;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .details-table th,
        .details-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .details-table th {
            background: #f8f9fa;
            font-weight: 600;
            width: 30%;
        }
        
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .response-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
        }
        
        .response-box h4 {
            margin-top: 0;
            color: #495057;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #dee2e6;
        }
        
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        
        .timeline {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .timeline h4 {
            margin-top: 0;
            color: #495057;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        
        .timeline-icon {
            margin-right: 15px;
            font-size: 18px;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-date {
            font-size: 12px;
            color: #6c757d;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 0;
            }
            
            .header,
            .content {
                padding: 20px;
            }
            
            .status-from,
            .status-to {
                display: block;
                margin: 5px 0;
            }
            
            .arrow {
                display: block;
                transform: rotate(90deg);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Status Atualizado</h1>
            <p>Sistema de Denúncias - {{app_name}}</p>
        </div>
        
        <div class="content">
            <div class="protocol-box">
                <p style="margin: 0; font-size: 16px; color: #6c757d;">Protocolo</p>
                <div class="protocol-number">{{protocolo}}</div>
            </div>
            
            <div class="status-change">
                <h3 style="margin-top: 0; color: #495057;">Mudança de Status</h3>
                <div style="margin: 20px 0;">
                    <span class="status-from">{{status_anterior}}</span>
                    <span class="arrow">→</span>
                    <span class="status-to">{{novo_status}}</span>
                </div>
                <p style="color: #6c757d; margin: 0;">
                    Atualizado em {{data_atualizacao}} por {{responsavel}}
                </p>
            </div>
            
            {{#resposta}}
            <div class="response-box">
                <h4>💬 Resposta/Observação</h4>
                <p>{{resposta}}</p>
            </div>
            {{/resposta}}
            
            <table class="details-table">
                <tr>
                    <th>📅 Data Original</th>
                    <td>{{data_criacao}}</td>
                </tr>
                <tr>
                    <th>📊 Status Atual</th>
                    <td><strong>{{novo_status}}</strong></td>
                </tr>
                <tr>
                    <th>👤 Responsável</th>
                    <td>{{responsavel}}</td>
                </tr>
                <tr>
                    <th>⏱️ Tempo Decorrido</th>
                    <td>{{tempo_decorrido}}</td>
                </tr>
            </table>
            
            {{#historico}}
            <div class="timeline">
                <h4>📈 Histórico de Atualizações</h4>
                {{#items}}
                <div class="timeline-item">
                    <div class="timeline-icon">{{icon}}</div>
                    <div class="timeline-content">
                        <strong>{{status}}</strong>
                        {{#observacao}}<br><small>{{observacao}}</small>{{/observacao}}
                        <div class="timeline-date">{{data}} {{#admin}}por {{admin}}{{/admin}}</div>
                    </div>
                </div>
                {{/items}}
            </div>
            {{/historico}}
            
            <div style="text-align: center;">
                <a href="{{app_url}}/denuncias/consultar?protocolo={{protocolo}}" class="btn">
                    👁️ Acompanhar Denúncia
                </a>
            </div>
            
            {{#novo_status_concluida}}
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; padding: 15px; margin: 20px 0; color: #155724;">
                <h4 style="margin-top: 0;">✅ Denúncia Concluída</h4>
                <p>Sua denúncia foi analisada e concluída. Agradecemos sua contribuição para a melhoria contínua dos nossos serviços.</p>
                {{#pode_avaliar}}
                <p>Você pode avaliar o atendimento recebido através do link abaixo:</p>
                <div style="text-align: center; margin: 15px 0;">
                    <a href="{{app_url}}/denuncias/avaliar?protocolo={{protocolo}}" style="background: #28a745; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600;">
                        ⭐ Avaliar Atendimento
                    </a>
                </div>
                {{/pode_avaliar}}
            </div>
            {{/novo_status_concluida}}
            
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">
            
            <p style="font-size: 14px; color: #6c757d; margin: 0;">
                <strong>Dúvidas?</strong> Entre em contato conosco através do sistema ou pelos canais oficiais de atendimento.
            </p>
        </div>
        
        <div class="footer">
            <p>
                Este email foi enviado automaticamente pelo<br>
                <strong>{{app_name}}</strong>
            </p>
            <p>
                <a href="{{app_url}}">Consultar Denúncia</a> | 
                <a href="{{app_url}}/denuncias/nova">Nova Denúncia</a>
            </p>
            <p style="font-size: 12px; margin-top: 15px;">
                © {{year}} Hospital São Francisco de Assis. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
