# Mapa de Rotas da Aplicação

## Visão Geral

Este documento descreve todas as rotas da aplicação de denúncias, incluindo métodos HTTP, autenticação, permissões e payloads esperados.

## Estrutura das Rotas

### Convenções
- **MÉTODO**: GET, POST, PUT, DELETE
- **Autenticação**: `AUTH` (requer login), `PUBLIC` (acesso livre)
- **RBAC**: Lista de permissões necessárias
- **Controller@method**: Classe e método que processa a rota

---

## Rotas Públicas

### Sistema de Denúncias
| Método | Rota | Controller | Autenticação | Descrição |
|--------|------|------------|--------------|-----------|
| GET | `/` | HomeController@index | PUBLIC | Página inicial |
| GET | `/denuncia/criar` | DenunciaController@index | PUBLIC | Formulário de nova denúncia |
| POST | `/denuncia/criar` | DenunciaController@store | PUBLIC | Salvar nova denúncia |
| GET | `/denuncia/consultar` | DenunciaController@status | PUBLIC | Consulta de status |
| POST | `/denuncia/consultar` | DenunciaController@checkStatus | PUBLIC | Processar consulta |
| GET | `/denuncia/detalhes` | DenunciaController@details | PUBLIC | Detalhes da denúncia |

---

## Rotas Administrativas

### Autenticação
| Método | Rota | Controller | Autenticação | Descrição |
|--------|------|------------|--------------|-----------|
| GET | `/admin/login` | AdminController@login | PUBLIC | Página de login |
| POST | `/admin/authenticate` | AdminController@authenticate | PUBLIC | Processar login |
| GET | `/admin/logout` | AdminController@logout | AUTH | Logout |

### Dashboard
| Método | Rota | Controller | Autenticação | RBAC |
|--------|------|------------|--------------|------|
| GET | `/admin` | AdminController@dashboard | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |
| GET | `/admin/dashboard` | AdminController@dashboard | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |

### Denúncias
| Método | Rota | Controller | Autenticação | RBAC |
|--------|------|------------|--------------|------|
| GET | `/admin/denuncias` | AdminController@denuncias | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |
| GET | `/admin/denuncias/pendentes` | AdminController@denunciasPendentes | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |
| GET | `/admin/denuncias/em-analise` | AdminController@denunciasEmAnalise | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |
| GET | `/admin/denuncias/em-investigacao` | AdminController@denunciasEmInvestigacao | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |
| GET | `/admin/denuncias/concluidas` | AdminController@denunciasConcluidas | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |
| GET | `/admin/denuncias/arquivadas` | AdminController@denunciasArquivadas | AUTH | `denuncias.view.all`, `denuncias.view.assigned` |
| **GET** | **`/admin/denuncia/{id}`** | **AdminDenunciaController@show** | **AUTH** | **`denuncias.view.all`, `denuncias.view.assigned`** |
| **POST** | **`/admin/denuncia/{id}/status`** | **AdminDenunciaController@updateStatus** | **AUTH** | **`denuncias.update.status`** |
| **POST** | **`/admin/denuncia/{id}/responder`** | **AdminDenunciaController@responder** | **AUTH** | **`denuncias.respond`** |

### Configurações
| Método | Rota | Controller | Autenticação | RBAC |
|--------|------|------------|--------------|------|
| GET | `/admin/configuracoes` | AdminController@configuracoes | AUTH | `settings.manage` |
| POST | `/admin/configuracoes/logo` | AdminController@uploadLogo | AUTH | `settings.manage` |
| POST | `/admin/configuracoes/senha` | AdminController@alterarSenha | AUTH | `settings.manage` |

### Relatórios
| Método | Rota | Controller | Autenticação | RBAC |
|--------|------|------------|--------------|------|
| GET | `/admin/relatorios` | AdminController@relatorios | AUTH | `reports.access` |
| GET | `/admin/relatorios/gerar` | AdminController@gerarRelatorio | AUTH | `reports.generate` |
| GET | `/admin/relatorios/estatistico` | AdminController@relatorioEstatistico | AUTH | `reports.generate` |
| GET | `/admin/relatorios/exportar-pdf` | AdminController@exportarRelatorioPDF | AUTH | `reports.export` |

### Usuários
| Método | Rota | Controller | Autenticação | RBAC |
|--------|------|------------|--------------|------|
| GET | `/admin/usuarios` | UserController@index | AUTH | `users.view` |
| GET | `/admin/usuarios/novo` | UserController@create | AUTH | `users.add` |
| POST | `/admin/usuarios/salvar` | UserController@store | AUTH | `users.add` |
| GET | `/admin/usuarios/editar/{id}` | UserController@edit | AUTH | `users.edit` |
| POST | `/admin/usuarios/atualizar/{id}` | UserController@update | AUTH | `users.edit` |
| POST | `/admin/usuarios/excluir/{id}` | UserController@delete | AUTH | `users.delete` |
| POST | `/admin/usuarios/status/{id}` | UserController@updateStatus | AUTH | `users.edit` |

---

## Payloads e Respostas

### POST /admin/denuncia/{id}/status
**Request (Form Data):**
```javascript
{
  "status": "Em Análise", // Pendente, Em Análise, Em Investigação, Concluída, Arquivada
  "observacao": "Observação opcional" // string, opcional
}
```

**Response (JSON):**
```javascript
{
  "success": true,
  "message": "Status atualizado com sucesso",
  "id": 123,
  "status": "Em Análise",
  "updated_at": "2025-09-05 15:30:00"
}
```

### POST /admin/denuncia/{id}/responder
**Request (Form Data):**
```javascript
{
  "resposta": "Texto da resposta", // string, obrigatório, máx 5000 chars
  "notificar": "true" // boolean, opcional
}
```

**Response (JSON):**
```javascript
{
  "success": true,
  "message": "Resposta adicionada com sucesso",
  "id": 123,
  "status": "Concluída",
  "responded_by": "Nome do Admin",
  "responded_at": "2025-09-05 15:30:00"
}
```

---

## Tratamento de Erros

### Códigos HTTP
- **200**: Sucesso
- **400**: Dados inválidos
- **401**: Não autenticado
- **403**: Acesso negado (sem permissão)
- **404**: Recurso não encontrado
- **500**: Erro interno do servidor

### Estrutura de Erro
```javascript
{
  "success": false,
  "message": "Descrição do erro"
}
```

---

## Middleware de Segurança

### AuthMiddleware
- Verifica sessão ativa
- Valida permissões via RBAC
- Registra tentativas não autorizadas
- Redireciona para login quando necessário

### Rate Limiting
- Criação de denúncias: 3 por hora
- Tentativas de login: 5 por 5 minutos

### CSRF Protection
- Token obrigatório em todos os POST
- Validação server-side
- Regeneração automática

---

## Logs de Auditoria

Todas as operações administrativas são registradas:
- Login/logout de usuários
- Alterações de status de denúncias
- Respostas enviadas
- Geração de relatórios
- Tentativas de acesso não autorizado

Localização: `storage/logs/app-YYYY-MM-DD.log`
