<title>Nova Denúncia Recebida - Protocolo {{protocolo}}</title>

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
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
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
        
        .alert {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
        }
        
        .protocol-box {
            background: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        
        .protocol-number {
            font-size: 28px;
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
        
        .btn:hover {
            background: #0056b3;
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
        
        .priority-high {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .next-steps {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .next-steps h3 {
            margin-top: 0;
            color: #0c5460;
        }
        
        .next-steps ul {
            margin-bottom: 0;
            padding-left: 20px;
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
            
            .protocol-number {
                font-size: 24px;
            }
            
            .details-table th,
            .details-table td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Nova Denúncia Recebida</h1>
            <p>Sistema de Denúncias - {{app_name}}</p>
        </div>
        
        <div class="content">
            <div class="alert">
                <strong>Atenção:</strong> Uma nova denúncia foi registrada no sistema e requer sua análise.
            </div>
            
            <div class="protocol-box">
                <p style="margin: 0; font-size: 16px; color: #6c757d;">Número do Protocolo</p>
                <div class="protocol-number">{{protocolo}}</div>
                <p style="margin: 0; font-size: 14px; color: #6c757d;">Guarde este número para acompanhamento</p>
            </div>
            
            <table class="details-table">
                <tr>
                    <th>📅 Data de Registro</th>
                    <td>{{data_criacao}}</td>
                </tr>
                <tr>
                    <th>📊 Status Inicial</th>
                    <td><strong>{{status}}</strong></td>
                </tr>
                <tr>
                    <th>📝 Descrição</th>
                    <td>{{descricao}}</td>
                </tr>
                {{#categorias}}
                <tr>
                    <th>🏷️ Categorias</th>
                    <td>{{categorias}}</td>
                </tr>
                {{/categorias}}
                {{#anexo}}
                <tr>
                    <th>📎 Anexo</th>
                    <td>Sim - Arquivo anexado</td>
                </tr>
                {{/anexo}}
            </table>
            
            <div class="next-steps">
                <h3>📋 Próximos Passos</h3>
                <ul>
                    <li>Acesse o sistema administrativo para analisar a denúncia</li>
                    <li>Verifique se há documentos anexados</li>
                    <li>Atribua um responsável se necessário</li>
                    <li>Atualize o status conforme o andamento</li>
                    <li>Mantenha o denunciante informado sobre o progresso</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{{app_url}}/admin/denuncias/{{protocolo}}" class="btn">
                    👁️ Visualizar Denúncia
                </a>
            </div>
            
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">
            
            <p style="font-size: 14px; color: #6c757d; margin: 0;">
                <strong>Importante:</strong> Esta denúncia deve ser tratada com confidencialidade. 
                Apenas pessoas autorizadas devem ter acesso às informações.
            </p>
        </div>
        
        <div class="footer">
            <p>
                Este email foi enviado automaticamente pelo<br>
                <strong>{{app_name}}</strong>
            </p>
            <p>
                <a href="{{app_url}}">Acessar Sistema</a> | 
                <a href="{{app_url}}/admin/configuracoes">Configurações</a>
            </p>
            <p style="font-size: 12px; margin-top: 15px;">
                © {{year}} Hospital São Francisco de Assis. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
