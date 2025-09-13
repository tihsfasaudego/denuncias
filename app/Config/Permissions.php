<?php
/**
 * Definição de permissões e perfis do Sistema de Denúncias
 */

class Permissions {
    // Lista de todas as permissões disponíveis no sistema
    public static $all = [
        // Permissões para gerenciamento de usuários
        'users.view' => 'Visualizar lista de usuários',
        'users.add' => 'Adicionar novos usuários',
        'users.edit' => 'Editar usuários existentes',
        'users.delete' => 'Excluir usuários',
        
        // Permissões para gerenciamento de denúncias
        'denuncias.view.all' => 'Visualizar todas as denúncias',
        'denuncias.view.assigned' => 'Visualizar denúncias atribuídas',
        'denuncias.assign' => 'Atribuir denúncias a usuários',
        'denuncias.update.status' => 'Atualizar status de denúncias',
        'denuncias.request.info' => 'Solicitar informações adicionais',
        'denuncias.investigate' => 'Encaminhar para investigação',
        'denuncias.conclude' => 'Concluir denúncias',
        'denuncias.archive' => 'Arquivar denúncias',
        'denuncias.delete' => 'Excluir denúncias',
        
        // Permissões para relatórios
        'reports.view' => 'Visualizar relatórios',
        'reports.generate' => 'Gerar novos relatórios',
        'reports.export' => 'Exportar relatórios',
        
        // Permissões para configurações do sistema
        'settings.view' => 'Visualizar configurações',
        'settings.manage' => 'Gerenciar configurações do sistema',
        
        // Permissões para auditoria
        'audit.view' => 'Visualizar logs de auditoria',
        'audit.manage' => 'Gerenciar logs de auditoria',

        // Permissões para categorias
        'categories.manage' => 'Gerenciar categorias de denúncias',
        
        // Permissões para confidencialidade
        'confidentiality.ensure' => 'Garantir confidencialidade no tratamento'
    ];
    
    // Permissões atribuídas ao perfil Administrador
    public static $administrador = [
        'users.view',
        'users.add',
        'users.edit',
        'users.delete',
        'denuncias.view.all',
        'denuncias.assign',
        'denuncias.update.status',
        'denuncias.request.info',
        'denuncias.investigate',
        'denuncias.conclude',
        'denuncias.archive',
        'denuncias.delete',
        'reports.view',
        'reports.generate',
        'reports.export',
        'settings.view',
        'settings.manage',
        'audit.view',
        'audit.manage',
        'categories.manage'
    ];
    
    // Permissões atribuídas ao perfil Analista
    public static $analista = [
        'denuncias.view.assigned',
        'denuncias.update.status',
        'denuncias.request.info',
        'denuncias.investigate',
        'reports.view'
    ];
    
    // Permissões atribuídas ao perfil Gestor
    public static $gestor = [
        'denuncias.view.all',
        'denuncias.assign',
        'denuncias.update.status',
        'denuncias.request.info',
        'denuncias.investigate',
        'denuncias.conclude',
        'denuncias.archive',
        'reports.view',
        'reports.generate',
        'reports.export',
        'audit.view'
    ];
    
    // Permissões atribuídas ao perfil Ouvidor
    public static $ouvidor = [
        'denuncias.view.all',
        'denuncias.update.status',
        'denuncias.request.info',
        'denuncias.investigate',
        'denuncias.conclude', 
        'reports.view',
        'reports.generate',
        'confidentiality.ensure'
    ];
    
    /**
     * Retorna a descrição de uma permissão específica
     * 
     * @param string $permission Slug da permissão
     * @return string Descrição da permissão ou string vazia se não existir
     */
    public static function getDescription($permission) {
        return self::$all[$permission] ?? '';
    }
    
    /**
     * Retorna todas as permissões de um perfil específico
     * 
     * @param string $role Nome do perfil (administrador, analista, gestor, ouvidor)
     * @return array Lista de permissões do perfil
     */
    public static function getByRole($role) {
        $role = strtolower($role);
        
        switch ($role) {
            case 'administrador':
                return self::$administrador;
            case 'analista':
                return self::$analista;
            case 'gestor':
                return self::$gestor;
            case 'ouvidor':
                return self::$ouvidor;
            default:
                return [];
        }
    }
} 