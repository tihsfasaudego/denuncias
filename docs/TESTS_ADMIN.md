# Checklist de Testes Manuais - Sistema de Denúncias

## Pré-requisitos
- [ ] Servidor web (Apache/Nginx) configurado
- [ ] PHP 8.x instalado
- [ ] MySQL 8.x rodando
- [ ] Base de dados criada e populada
- [ ] Permissões de arquivo corretas
- [ ] CSRF tokens funcionais

---

## 🔐 Testes de Autenticação e Autorização

### Login Administrativo
- [ ] **GET /admin/login** - Página carrega corretamente
- [ ] **POST /admin/authenticate** - Login com credenciais válidas funciona
- [ ] **POST /admin/authenticate** - Login com credenciais inválidas falha
- [ ] **Rate limiting** - 5 tentativas de login bloqueiam por 5 minutos
- [ ] **GET /admin/logout** - Logout funciona corretamente
- [ ] **Sessão expirada** - Redireciona para login após 30 minutos

### RBAC (Controle de Acesso)
- [ ] **Usuário sem permissão** - Recebe 403 em rotas protegidas
- [ ] **Admin com permissão** - Acessa todas as rotas
- [ ] **Usuário com permissão específica** - Acessa apenas rotas permitidas
- [ ] **Tentativas não autorizadas** - Registradas nos logs

---

## 📊 Testes de Dashboard

### Visualização Geral
- [ ] **GET /admin/dashboard** - Carrega estatísticas corretamente
- [ ] **Cards de status** - Exibem contagens corretas por status
- [ ] **Tabela recente** - Mostra últimas 10 denúncias
- [ ] **Links de filtro** - Redirecionam para listas corretas
- [ ] **Permissões** - Respeita `denuncias.view.all` vs `denuncias.view.assigned`

### Navegação
- [ ] **Menu lateral** - Links funcionam corretamente
- [ ] **Breadcrumb** - Mostra caminho correto
- [ ] **Botões de ação** - Funcionam conforme esperado

---

## 📋 Testes de Listagem de Denúncias

### Listas por Status
- [ ] **GET /admin/denuncias/pendentes** - Mostra apenas denúncias pendentes
- [ ] **GET /admin/denuncias/em-analise** - Mostra apenas em análise
- [ ] **GET /admin/denuncias/concluidas** - Mostra apenas concluídas
- [ ] **Paginação** - Funciona corretamente
- [ ] **Filtros** - Aplicam corretamente (data, prioridade)

### Ações em Massa
- [ ] **Selecionar todos** - Checkbox funciona
- [ ] **Ações em lote** - Aparecem quando itens selecionados
- [ ] **Exportar lista** - Gera CSV/PDF corretamente

---

## 👁️ Testes de Visualização de Denúncia

### Carregamento
- [ ] **GET /admin/denuncia/{id}** - Carrega dados completos
- [ ] **Denúncia inexistente** - Retorna 404
- [ ] **Sem permissão** - Retorna 403
- [ ] **Dados sensíveis** - Não vazam para usuário não autorizado

### Layout e Funcionalidades
- [ ] **Cabeçalho** - Protocolo, status, prioridade corretos
- [ ] **Detalhes** - Descrição, datas, local, pessoas envolvidas
- [ ] **Anexos** - Download funciona se existir
- [ ] **Histórico** - Mostra alterações de status
- [ ] **Responsável** - Exibe corretamente

---

## ✏️ Testes de Alteração de Status

### Modal e Formulário
- [ ] **Botão "Alterar Status"** - Abre modal corretamente
- [ ] **Select de status** - Opções corretas disponíveis
- [ ] **Campo observação** - Opcional funciona
- [ ] **Validação** - Status obrigatório

### Submissão
- [ ] **POST /admin/denuncia/{id}/status** - Request correto
- [ ] **CSRF token** - Incluído na requisição
- [ ] **Resposta JSON** - Estrutura correta
- [ ] **Status HTTP** - 200 para sucesso, 4xx para erro

### Atualização da UI
- [ ] **Badge de status** - Atualiza sem reload
- [ ] **Toast de sucesso** - Aparece corretamente
- [ ] **Modal fecha** - Após sucesso
- [ ] **Logs** - Operação registrada

---

## 💬 Testes de Resposta/Comentários

### Formulário
- [ ] **Campo resposta** - Máximo 5000 caracteres
- [ ] **Checkbox notificar** - Opcional funciona
- [ ] **Validação** - Resposta obrigatória
- [ ] **Botão submit** - Desabilita durante envio

### Processamento
- [ ] **POST /admin/denuncia/{id}/responder** - Request correto
- [ ] **Transação** - Tudo ou nada (resposta + status)
- [ ] **Status automático** - Muda para "Concluída"
- [ ] **Registro na tabela** - `respostas` populada

### Resposta da API
- [ ] **JSON success** - Estrutura correta
- [ ] **Campos retornados** - ID, status, responded_by, responded_at
- [ ] **Tratamento de erro** - Mensagens claras

### Feedback Visual
- [ ] **Toast de sucesso** - Aparece corretamente
- [ ] **Campo limpa** - Após envio
- [ ] **Status atualiza** - Na interface
- [ ] **Histórico carrega** - Novas respostas aparecem

---

## 📈 Testes de Relatórios

### Interface
- [ ] **GET /admin/relatorios** - Carrega filtros corretamente
- [ ] **Filtros disponíveis** - Data, status, categoria, protocolo
- [ ] **Validação de datas** - Final não anterior à inicial
- [ ] **Botões de export** - CSV, PDF, Excel

### Geração
- [ ] **GET /admin/relatorios/gerar** - Processa filtros
- [ ] **Consulta otimizada** - Usa índices corretos
- [ ] **Paginação** - Resultados paginados
- [ ] **Performance** - Resposta em < 3 segundos

### Export
- [ ] **CSV export** - Formato correto, headers apropriados
- [ ] **PDF export** - Layout legível
- [ ] **Headers HTTP** - Content-Type e Content-Disposition
- [ ] **Filename** - Descritivo com timestamp

---

## 🔒 Testes de Segurança

### CSRF Protection
- [ ] **Token presente** - Em todos os forms POST
- [ ] **Token válido** - Request rejeitado sem token
- [ ] **Token único** - Por sessão/request
- [ ] **Regeneração** - Após cada request

### SQL Injection
- [ ] **Inputs sanitizados** - Prepared statements usados
- [ ] **Dados escapados** - Em views com htmlspecialchars
- [ ] **Parâmetros bind** - Nunca concatenação direta

### XSS Prevention
- [ ] **HTML escapado** - Em todas as saídas
- [ ] **Atributos seguros** - Em elementos dinâmicos
- [ ] **JavaScript seguro** - Sem eval ou innerHTML perigoso

### Rate Limiting
- [ ] **Denúncias** - Máximo 3 por hora por IP
- [ ] **Login** - Máximo 5 tentativas por 5 minutos
- [ ] **Mensagens** - Limita requests excessivos

---

## 📱 Testes de Responsividade

### Desktop
- [ ] **Layout** - Elementos alinhados corretamente
- [ ] **Tabelas** - Rolagem horizontal se necessário
- [ ] **Modais** - Centralizados e funcionais

### Tablet
- [ ] **Menu** - Collapsível funciona
- [ ] **Cards** - Ajustam ao tamanho da tela
- [ ] **Formulários** - Campos acessíveis

### Mobile
- [ ] **Navegação** - Touch-friendly
- [ ] **Botões** - Tamanhos adequados
- [ ] **Textos** - Legíveis sem zoom

---

## 🚨 Testes de Tratamento de Erros

### Validação de Dados
- [ ] **Campos obrigatórios** - Mensagens específicas
- [ ] **Formatos inválidos** - Rejeitados com mensagem
- [ ] **Tamanhos excedidos** - Limites respeitados

### Estados de Erro HTTP
- [ ] **400 Bad Request** - Dados inválidos
- [ ] **401 Unauthorized** - Não autenticado
- [ ] **403 Forbidden** - Sem permissão
- [ ] **404 Not Found** - Recurso inexistente
- [ ] **500 Internal Error** - Erro do servidor

### Logs de Erro
- [ ] **PHP errors** - Registrados em logs
- [ ] **SQL errors** - Não vazam para usuário
- [ ] **Security events** - Logs específicos
- [ ] **Performance** - Queries lentas logadas

---

## 🔄 Testes de Regressão

### Funcionalidades Existentes
- [ ] **Login público** - Ainda funciona
- [ ] **Criação de denúncias** - Não afetada
- [ ] **Consulta de status** - Mantém compatibilidade
- [ ] **Dashboard** - Estatísticas corretas

### Performance
- [ ] **Queries** - Não degradaram
- [ ] **Cache** - Funcionando corretamente
- [ ] **Sessões** - Não quebradas
- [ ] **Uploads** - Ainda funcionam

---

## 🧪 Cenários de Teste Específicos

### Cenário 1: Fluxo Completo de Denúncia
1. [ ] Criar denúncia anônima
2. [ ] Admin visualiza no dashboard
3. [ ] Admin acessa detalhes
4. [ ] Admin altera status
5. [ ] Admin adiciona resposta
6. [ ] Status muda para "Concluída"
7. [ ] Usuário consulta status
8. [ ] Histórico completo visível

### Cenário 2: Controle de Permissões
1. [ ] Usuário A (analista) loga
2. [ ] Visualiza apenas denúncias atribuídas
3. [ ] Tenta alterar status de denúncia alheia → 403
4. [ ] Admin atribui denúncia ao usuário A
5. [ ] Usuário A agora pode alterar status
6. [ ] Logs registram todas as tentativas

### Cenário 3: Relatórios e Export
1. [ ] Admin acessa relatórios
2. [ ] Aplica filtros (data + status)
3. [ ] Visualiza resultados paginados
4. [ ] Exporta para CSV
5. [ ] Verifica integridade dos dados
6. [ ] Exporta para PDF
7. [ ] Valida formatação

---

## 📋 Checklist Final de Aceitação

- [ ] **Visualizar** abre `/admin/denuncia/{id}` e renderiza dados completos
- [ ] **Responder** salva, muda status para `Concluída` e retorna `{ok:true}`
- [ ] **Alterar Status** emite POST para rota correta, atualiza badge
- [ ] **RBAC** funciona: sem permissão → 403
- [ ] **Logs** registram operações (id denúncia, usuário, timestamp)
- [ ] **Relatórios** filtram por período, status, categoria; export CSV
- [ ] **SQL injection** protegido; **CSRF** ativo; **XSS** mitigado
- [ ] Zero regressões em login, dashboard, listas
- [ ] Performance aceitável (< 3s para queries)
- [ ] Interface responsiva em desktop/tablet/mobile
- [ ] Tratamento robusto de erros e edge cases

---

## 🛠️ Comandos de Teste (cURL)

### Teste de Status
```bash
curl -X POST /admin/denuncia/123/status \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "status=Em%20Análise&observacao=Teste"
```

### Teste de Resposta
```bash
curl -X POST /admin/denuncia/123/responder \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "resposta=Resposta%20de%20teste&notificar=true"
```

### Teste de Relatório
```bash
curl "/admin/relatorios/gerar?data_inicio=2025-01-01&data_fim=2025-12-31&formato=csv"
```
