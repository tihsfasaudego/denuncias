#!/bin/bash

# Script de teste para os novos endpoints administrativos
# Uso: ./test_endpoints.sh [base_url] [session_cookie]

BASE_URL=${1:-"http://localhost"}
SESSION_COOKIE=${2:-""}

echo "🧪 Testando endpoints administrativos"
echo "URL base: $BASE_URL"
echo "Cookie de sessão: $SESSION_COOKIE"
echo "========================================"

# Teste 1: Visualizar denúncia
echo -e "\n1. Testando visualização de denúncia (GET /admin/denuncia/1)"
curl -s -w "\nStatus: %{http_code}\n" \
     -H "Cookie: $SESSION_COOKIE" \
     "$BASE_URL/admin/denuncia/1" | head -20

# Teste 2: Alterar status
echo -e "\n2. Testando alteração de status (POST /admin/denuncia/1/status)"
curl -s -w "\nStatus: %{http_code}\n" \
     -H "Cookie: $SESSION_COOKIE" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "status=Em%20Análise&observacao=Teste%20automático" \
     "$BASE_URL/admin/denuncia/1/status"

# Teste 3: Responder denúncia
echo -e "\n3. Testando resposta à denúncia (POST /admin/denuncia/1/responder)"
curl -s -w "\nStatus: %{http_code}\n" \
     -H "Cookie: $SESSION_COOKIE" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "resposta=Resposta%20de%20teste%20automático&notificar=true" \
     "$BASE_URL/admin/denuncia/1/responder"

# Teste 4: Relatório filtrado
echo -e "\n4. Testando relatório com filtros (GET /admin/relatorios/gerar)"
curl -s -w "\nStatus: %{http_code}\n" \
     -H "Cookie: $SESSION_COOKIE" \
     "$BASE_URL/admin/relatorios/gerar?data_inicio=2025-01-01&data_fim=2025-12-31&formato=csv" | head -10

# Teste 5: Relatório estatístico
echo -e "\n5. Testando relatório estatístico (GET /admin/relatorios/estatistico)"
curl -s -w "\nStatus: %{http_code}\n" \
     -H "Cookie: $SESSION_COOKIE" \
     "$BASE_URL/admin/relatorios/estatistico?data_inicio=2025-01-01&data_fim=2025-12-31"

echo -e "\n✅ Testes concluídos!"
echo -e "\n📝 Para usar corretamente:"
echo "1. Faça login no admin e copie o cookie PHPSESSID"
echo "2. Execute: ./test_endpoints.sh http://localhost PHPSESSID=seu_cookie_aqui"
echo "3. Verifique os códigos de status HTTP (200 = sucesso, 403 = sem permissão)"
