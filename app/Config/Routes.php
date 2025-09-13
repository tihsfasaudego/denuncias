<?php
/**
 * Configuração Centralizada de Rotas
 * 
 * Este arquivo define todas as rotas da aplicação de forma organizada
 */

return [
    // ===== ROTAS PÚBLICAS =====
    'public' => [
        // Página inicial
        ['GET', '/', 'HomeController@index'],
        
        // Sistema de denúncias
        ['GET', '/denuncia/criar', 'DenunciaController@index'],
        ['POST', '/denuncia/criar', 'DenunciaController@store'],
        ['GET', '/denuncia/consultar', 'DenunciaController@status'],
        ['POST', '/denuncia/consultar', 'DenunciaController@checkStatus'],
        ['GET', '/denuncia/detalhes', 'DenunciaController@details'],
    ],
    
    // ===== ROTAS ADMINISTRATIVAS =====
    'admin' => [
        // ===== ROTAS PÚBLICAS (SEM AUTENTICAÇÃO) =====
        'public' => [
            ['GET', '/admin/login', 'AdminController@login'],
            ['POST', '/admin/authenticate', 'AdminController@authenticate'],
        ],
        
        // ===== ROTAS PROTEGIDAS (COM AUTENTICAÇÃO) =====
        'protected' => [
            // Dashboard
            ['GET', '/admin', 'AdminController@dashboard'], // Redireciona para dashboard
            ['GET', '/admin/', 'AdminController@dashboard'], // Redireciona para dashboard
            ['GET', '/admin/dashboard', 'AdminController@dashboard'],
            ['GET', '/admin/logout', 'AdminController@logout'],
            
            // Denúncias - Rotas completas
            ['GET', '/admin/denuncias', 'AdminController@denuncias'],
            ['GET', '/admin/denuncias/pendentes', 'AdminController@denunciasPendentes'],
            ['GET', '/admin/denuncias/em-analise', 'AdminController@denunciasEmAnalise'],
            ['GET', '/admin/denuncias/em-investigacao', 'AdminController@denunciasEmInvestigacao'],
            ['GET', '/admin/denuncias/concluidas', 'AdminController@denunciasConcluidas'],
            ['GET', '/admin/denuncias/arquivadas', 'AdminController@denunciasArquivadas'],
            ['GET', '/admin/denuncia/{id}', 'AdminDenunciaController@show'],
            ['GET', '/admin/denuncia/{id}/dados', 'AdminDenunciaController@getDados'],
            ['POST', '/admin/denuncia/{id}/status', 'AdminDenunciaController@updateStatus'],
            ['POST', '/admin/denuncia/{id}/responder', 'AdminDenunciaController@responder'],
            ['POST', '/admin/denuncia/{id}/excluir', 'AdminController@excluirDenuncia'],
            
            // Configurações
            ['GET', '/admin/configuracoes', 'AdminController@configuracoes'],
            ['POST', '/admin/configuracoes/logo', 'AdminController@uploadLogo'],
            ['POST', '/admin/configuracoes/senha', 'AdminController@alterarSenha'],
            
            // Relatórios
            ['GET', '/admin/relatorios', 'AdminController@relatorios'],
            ['GET', '/admin/relatorios/gerar', 'AdminController@gerarRelatorio'],
            ['GET', '/admin/relatorios/estatistico', 'AdminController@relatorioEstatistico'],
            ['GET', '/admin/relatorios/exportar-pdf', 'AdminController@exportarRelatorioPDF'],
            
            // Usuários
            ['GET', '/admin/usuarios', 'AdminController@usuarios'],
            
            // Perfil
            ['GET', '/admin/perfil', 'UserController@profile'],
            ['POST', '/admin/perfil/atualizar', 'UserController@updateProfile'],
            
            // Debug (apenas em desenvolvimento)
            ['GET', '/admin/debug/historico-status', 'AdminController@debugHistoricoStatus'],
        ]
    ],
    
    // ===== API ROUTES =====
    'api' => [
        // Saúde da API
        ['GET', '/api/health', 'ApiController@healthCheck'],
        ['GET', '/api/docs', 'ApiController@apiDocumentation'],
        
        // Autenticação
        ['POST', '/api/auth/login', 'ApiController@login'],
        ['POST', '/api/auth/refresh', 'ApiController@refreshToken'],
        ['POST', '/api/auth/logout', 'ApiController@logout'],
        
        // Denúncias (com autenticação)
        ['GET', '/api/denuncias', 'ApiController@listDenuncias'],
        ['GET', '/api/denuncias/{protocolo}', 'ApiController@getDenuncia'],
        ['POST', '/api/denuncias', 'ApiController@createDenuncia'],
        ['PUT', '/api/denuncias/{protocolo}', 'ApiController@updateDenuncia'],
        ['DELETE', '/api/denuncias/{protocolo}', 'ApiController@deleteDenuncia'],
        
        // Estatísticas
        ['GET', '/api/stats', 'ApiController@getStats'],
        
        // Notificações
        ['GET', '/api/notifications', 'NotificationController@list'],
        ['POST', '/api/notifications/mark-read', 'NotificationController@markAsRead'],
        ['POST', '/api/notifications/mark-all-read', 'NotificationController@markAllAsRead'],
        ['DELETE', '/api/notifications/{id}', 'NotificationController@delete'],
        ['GET', '/api/notifications/unread-count', 'NotificationController@getUnreadCount'],
    ]
];
