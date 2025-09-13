#!/bin/bash
# Script para configurar cron jobs para backups automáticos

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para exibir mensagens coloridas
echo_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

echo_warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

echo_error() {
    echo -e "${RED}[ERRO]${NC} $1"
}

# Detectar diretório do projeto
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
PHP_BACKUP_SCRIPT="$PROJECT_DIR/scripts/run_backup.php"

echo_info "=== CONFIGURAÇÃO DE BACKUPS AUTOMÁTICOS ==="
echo_info "Diretório do projeto: $PROJECT_DIR"
echo_info "Script de backup: $PHP_BACKUP_SCRIPT"

# Verificar se o script de backup existe
if [ ! -f "$PHP_BACKUP_SCRIPT" ]; then
    echo_error "Script de backup não encontrado: $PHP_BACKUP_SCRIPT"
    exit 1
fi

# Detectar PHP
PHP_BIN=$(which php)
if [ -z "$PHP_BIN" ]; then
    echo_error "PHP não encontrado no PATH"
    exit 1
fi

echo_info "PHP encontrado: $PHP_BIN"

# Verificar se o usuário quer configurar cron
echo ""
echo "Este script configurará os seguintes cron jobs:"
echo "  - Backup diário do banco de dados às 02:00"
echo "  - Backup completo semanal aos domingos às 03:00"
echo "  - Limpeza de backups antigos diariamente às 04:00"
echo ""
read -p "Deseja continuar? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo_info "Operação cancelada."
    exit 0
fi

# Backup do crontab atual
echo_info "Fazendo backup do crontab atual..."
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || echo_warning "Nenhum crontab existente encontrado"

# Remover cron jobs antigos do sistema de backup (se existirem)
echo_info "Removendo cron jobs antigos do sistema de backup..."
crontab -l 2>/dev/null | grep -v "run_backup.php" | crontab -

# Adicionar novos cron jobs
echo_info "Adicionando novos cron jobs..."

# Criar arquivo temporário com os cron jobs
TEMP_CRON=$(mktemp)

# Obter crontab atual (se existir)
crontab -l 2>/dev/null > "$TEMP_CRON"

# Adicionar comentário identificador
echo "" >> "$TEMP_CRON"
echo "# === BACKUPS AUTOMÁTICOS HSFA DENÚNCIAS ===" >> "$TEMP_CRON"

# Backup diário do banco de dados às 02:00
echo "0 2 * * * $PHP_BIN $PHP_BACKUP_SCRIPT database true > /tmp/backup_daily.log 2>&1" >> "$TEMP_CRON"

# Backup completo semanal aos domingos às 03:00
echo "0 3 * * 0 $PHP_BIN $PHP_BACKUP_SCRIPT full true > /tmp/backup_weekly.log 2>&1" >> "$TEMP_CRON"

# Executar backups agendados a cada hora
echo "0 * * * * $PHP_BIN $PHP_BACKUP_SCRIPT scheduled > /tmp/backup_scheduled.log 2>&1" >> "$TEMP_CRON"

# Limpeza de logs antigos diariamente às 04:00
echo "0 4 * * * find /tmp -name 'backup_*.log' -mtime +7 -delete" >> "$TEMP_CRON"

echo "# === FIM BACKUPS AUTOMÁTICOS ===" >> "$TEMP_CRON"

# Instalar novo crontab
crontab "$TEMP_CRON"

# Remover arquivo temporário
rm "$TEMP_CRON"

# Verificar se foi instalado corretamente
if crontab -l | grep -q "run_backup.php"; then
    echo_info "✅ Cron jobs instalados com sucesso!"
    echo_info ""
    echo_info "Cronograma configurado:"
    echo_info "  - Backup diário: 02:00 (banco de dados)"
    echo_info "  - Backup semanal: 03:00 domingos (completo)"
    echo_info "  - Verificação de agendados: a cada hora"
    echo_info "  - Limpeza de logs: 04:00 (remove logs > 7 dias)"
    echo_info ""
    echo_info "Logs serão salvos em:"
    echo_info "  - /tmp/backup_daily.log"
    echo_info "  - /tmp/backup_weekly.log"
    echo_info "  - /tmp/backup_scheduled.log"
else
    echo_error "❌ Erro ao instalar cron jobs"
    exit 1
fi

# Testar o script de backup
echo_info ""
echo_info "Testando o script de backup..."
if $PHP_BIN "$PHP_BACKUP_SCRIPT" test; then
    echo_info "✅ Teste do sistema de backup passou!"
else
    echo_error "❌ Teste do sistema de backup falhou!"
    echo_warning "Verifique se o MySQL/MariaDB está acessível e as permissões estão corretas."
fi

# Exibir crontab atual
echo_info ""
echo_info "Crontab atual:"
crontab -l | grep -A 10 -B 2 "BACKUPS AUTOMÁTICOS"

echo_info ""
echo_info "=== CONFIGURAÇÃO CONCLUÍDA ==="
echo_info ""
echo_info "Para gerenciar os backups manualmente, use:"
echo_info "  php $PHP_BACKUP_SCRIPT help"
echo_info ""
echo_info "Para verificar os logs:"
echo_info "  tail -f /tmp/backup_daily.log"
echo_info "  tail -f /tmp/backup_weekly.log"
echo_info ""
echo_info "Para remover os cron jobs:"
echo_info "  crontab -e"
echo_info "  (remova as linhas entre os comentários dos backups)"
