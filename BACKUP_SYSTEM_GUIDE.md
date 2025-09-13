# 🔒 Sistema de Backup e Recuperação - HSFA Denúncias

## 📋 Resumo da Situação

Durante a investigação, foi identificado que **as denúncias sumiram do banco de dados** localizado em `192.168.2.40`. Para evitar futuras perdas de dados e permitir recuperação rápida, foi implementado um **sistema completo de backup automático e ferramentas de recuperação**.

## 🛠️ Ferramentas Implementadas

### 1. 🔍 **Sistema de Investigação**

- `scripts/investigate_missing_data.php` - Analisa perda de dados e possíveis causas
- `scripts/check_database_integrity.php` - Verifica integridade do banco de dados

### 2. 💾 **Sistema de Backup Automático**

- `app/Core/BackupManager.php` - Gerenciador principal de backups (melhorado)
- `scripts/run_backup.php` - Interface de linha de comando para backups
- `scripts/backup_scheduler.php` - Agendador de backups automáticos
- `scripts/setup_cron.sh` - Configuração automática de cron jobs

### 3. 🔄 **Sistema de Recuperação**

- `scripts/recovery_tools.php` - Ferramentas completas de recuperação
- Suporte para restauração de banco de dados, arquivos e configurações

### 4. 🔐 **Sistema de Auditoria**

- `app/Core/AuditLogger.php` - Log de todas as operações críticas
- Rastreamento de exclusões e modificações de dados

### 5. 📊 **Interface Unificada**

- `scripts/system_admin.php` - Painel central de administração

## 🚀 Como Usar

### 1. **Investigar o Problema Atual**

```bash
# Investigar dados perdidos
php scripts/investigate_missing_data.php

# Verificar integridade do banco
php scripts/check_database_integrity.php
```

### 2. **Configurar Backups Automáticos**

```bash
# Configurar agendamentos padrão
php scripts/backup_scheduler.php setup

# Configurar cron jobs (Linux/Unix)
bash scripts/setup_cron.sh

# OU usar o sistema unificado
php scripts/system_admin.php setup-cron
```

### 3. **Executar Backups Manuais**

```bash
# Backup rápido do banco de dados
php scripts/run_backup.php database

# Backup completo (banco + arquivos + configs)
php scripts/run_backup.php full

# OU usar interface interativa
php scripts/system_admin.php quick-backup
```

### 4. **Gerenciar Agendamentos**

```bash
# Listar agendamentos
php scripts/backup_scheduler.php list

# Executar backups agendados
php scripts/backup_scheduler.php run

# Adicionar agendamento personalizado
php scripts/backup_scheduler.php add database daily
```

### 5. **Recuperar Dados**

```bash
# Listar backups disponíveis
php scripts/recovery_tools.php list

# Verificar integridade de um backup
php scripts/recovery_tools.php verify <backup_id>

# Restaurar backup
php scripts/recovery_tools.php restore <backup_id>
```

### 6. **Interface Unificada**

```bash
# Painel principal de administração
php scripts/system_admin.php

# Status geral do sistema
php scripts/system_admin.php status

# Testar todos os sistemas
php scripts/system_admin.php test-all
```

## ⏰ Agendamentos Padrão Configurados

- **Diário às 02:00**: Backup do banco de dados
- **Semanal (domingo às 03:00)**: Backup completo
- **A cada hora**: Verificação de backups agendados
- **Diário às 04:00**: Limpeza de logs antigos

## 📁 Estrutura de Arquivos de Backup

```
storage/backups/
├── full_backup_2024-01-15_03-00-00/
│   ├── database.sql
│   ├── uploads/
│   ├── config/
│   ├── logs/
│   └── manifest.json
├── database_backup_2024-01-15_02-00-00.sql.gz
└── config_backup_2024-01-15.tar.gz
```

## 🔐 Sistema de Auditoria

O sistema agora registra **todas as operações críticas**:

- **DELETE**: Operações de exclusão com dados completos
- **UPDATE**: Modificações de registros importantes
- **LOGIN/LOGOUT**: Tentativas de acesso
- **BACKUP/RESTORE**: Operações de backup e recuperação
- **CONFIG**: Alterações de configuração

### Consultar Logs de Auditoria

```sql
-- Ver últimas exclusões
SELECT * FROM audit_log 
WHERE operation = 'DELETE' 
ORDER BY created_at DESC 
LIMIT 20;

-- Ver operações críticas
SELECT * FROM audit_log 
WHERE level = 'critical' 
ORDER BY created_at DESC;
```

## 🚨 Cenários de Emergência

### 1. **Perda Total de Dados**

```bash
# 1. Investigar o problema
php scripts/system_admin.php investigate

# 2. Listar backups disponíveis
php scripts/system_admin.php list-backups

# 3. Restaurar último backup completo
php scripts/recovery_tools.php restore <backup_id>
```

### 2. **Backup de Emergência**

```bash
# Backup imediato antes de manutenção
php scripts/system_admin.php quick-backup

# Backup completo não comprimido (mais rápido)
php scripts/run_backup.php full false
```

### 3. **Verificação de Integridade**

```bash
# Verificar sistema completo
php scripts/system_admin.php test-all

# Verificar apenas banco de dados
php scripts/system_admin.php integrity
```

## 📊 Monitoramento

### Status do Sistema

```bash
# Status geral
php scripts/system_admin.php status

# Status específico dos backups
php scripts/backup_scheduler.php status
```

### Logs Importantes

- **Backups**: `/tmp/backup_*.log`
- **Aplicação**: `storage/logs/app-*.log`
- **Auditoria**: Tabela `audit_log` no banco
- **Integridade**: `storage/logs/integrity_check_*.json`

## ⚙️ Configurações

### Variáveis de Ambiente

```env
# Configurações de backup
MYSQLDUMP_PATH=/usr/bin/mysqldump
BACKUP_RETENTION_DAYS=30
BACKUP_COMPRESSION=true

# Configurações de auditoria
AUDIT_RETENTION_DAYS=365
AUDIT_LEVEL=medium
```

### Personalização de Agendamentos

```bash
# Agendamentos personalizados
php scripts/backup_scheduler.php add files weekly
php scripts/backup_scheduler.php add config monthly
```

## 🛡️ Prevenção de Futuras Perdas

### 1. **Auditoria Completa**

- Todas as exclusões são registradas com dados completos
- Rastreamento de usuários e IPs
- Logs estruturados em JSON

### 2. **Backups Redundantes**

- Múltiplos tipos de backup (diário, semanal, mensal)
- Compressão automática para economia de espaço
- Verificação de integridade

### 3. **Monitoramento Ativo**

- Notificações automáticas em caso de falha
- Verificação contínua de integridade
- Alertas para espaço em disco

### 4. **Recuperação Rápida**

- Ferramentas automatizadas de restauração
- Backups de segurança antes de operações críticas
- Verificação de integridade antes da restauração

## 📞 Resolução de Problemas

### Erro: "mysqldump não encontrado"

```bash
# Instalar MySQL client
sudo apt-get install mysql-client

# Ou especificar caminho
export MYSQLDUMP_PATH=/usr/local/bin/mysqldump
```

### Erro: "Permissões insuficientes"

```bash
# Ajustar permissões dos diretórios
chmod 755 storage/backups
chmod 755 storage/logs
chmod 755 public/uploads
```

### Backup muito lento

```bash
# Usar backup sem compressão
php scripts/run_backup.php database false

# Ou apenas banco de dados
php scripts/run_backup.php database
```

## 📈 Próximos Passos Recomendados

1. **Executar investigação completa** para entender a causa da perda
2. **Configurar backups automáticos** imediatamente
3. **Testar processo de recuperação** com backup de teste
4. **Monitorar logs de auditoria** regularmente
5. **Configurar alertas** para notificações automáticas

## 🎯 Conclusão

O sistema implementado oferece:

- ✅ **Prevenção**: Backups automáticos e auditoria completa
- ✅ **Detecção**: Monitoramento e verificação de integridade
- ✅ **Recuperação**: Ferramentas completas de restauração
- ✅ **Investigação**: Análise detalhada de problemas

Com essas ferramentas, o sistema está **muito mais protegido** contra futuras perdas de dados e preparado para **recuperação rápida** em caso de problemas.

---

**🚨 IMPORTANTE**: Execute `php scripts/system_admin.php investigate` **imediatamente** para analisar a situação atual e `php scripts/system_admin.php setup-cron` para configurar os backups automáticos.
