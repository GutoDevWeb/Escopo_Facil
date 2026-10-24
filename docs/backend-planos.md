# Backend: planos, projetos e permissões

## Implementação e decisões

A documentação recebida em 23/10/2026 descrevia o backend anterior, mas verificar apenas `users.plan_id` não comprova uma assinatura paga nem restringe funcionalidades. Agora `App\Services\PlanAccess` determina os direitos efetivos em cada requisição. Os arquivos de `backup_antigo` foram consultados como referência de dados e fluxos e permanecem intactos.

Regra confirmada pela equipe: seguir o site, com projetos ilimitados e diferenças por funcionalidade; o Free não bloqueia após os sete dias anunciados como período inicial. Também não foi estabelecida uma cota numérica de membros. `config/entitlements.php` permite configurar um prazo Free, caso essa regra seja alterada. As cotas numéricas continuam editáveis pela administração (`-1` = ilimitado), incluindo o proprietário na contagem de membros por projeto. A migration de alinhamento altera apenas as cotas idênticas às antigas do seeder; limites personalizados são preservados.

| Recurso | Free | Pro | Equipe |
| --- | --- | --- | --- |
| Projetos, tarefas por status e pesquisa | Sim | Sim | Sim |
| Membros e checklist | Sim | Sim | Sim |
| Prioridades, prazos e filtros de prioridade | Não | Sim | Sim |
| Relatório por status | Não | Sim | Sim |
| Papéis gerente/leitor | Não | Não | Sim |
| Histórico de alterações de tarefas | Não | Não | Sim |
| Relatório por prioridade e tarefas atrasadas | Não | Não | Sim |
| Canal de suporte da equipe no sistema | Não | Não | Sim |

O suporte geral do site continua público. O canal Equipe não cria uma central de atendimento externa nem promete SLA.

## Plano efetivo e ciclo de vida

- O cadastro associa o plano de slug `free`; se ausente, cria o registro padrão. Campos enviados como `plan_id`, `is_admin` ou `plan=pro` não concedem acesso pago.
- Para Pro/Equipe, deve existir assinatura com `status=active`, plano reconhecido e `expires_at` futura. Um `plan_id` pago sem assinatura válida não libera recursos.
- Expiração, cancelamento e validade nula fazem o acesso retornar ao Free. Os projetos e tarefas não são apagados. Dados pagos antigos são preservados, mas sua edição e os recursos pagos ficam bloqueados conforme o plano efetivo.
- Pro e Equipe usam o mesmo checkout PIX acadêmico. A escolha do plano e do período é preservada durante login, confirmação de e-mail e recarga da página. Os valores vêm do catálogo de planos no servidor. Gerar o QR Code ou abrir uma URL não ativa assinatura: somente a confirmação do pagamento simulado ativa o plano escolhido. Ao mudar de Pro para Equipe, a assinatura anterior é substituída depois da confirmação; a operação repetida não duplica a assinatura. Continua sem renovação automática ou cobrança real. A atribuição administrativa com validade explícita também permanece disponível.
- O pagamento continua sendo **simulação local/AbacatePay DEV**, sem cobrança real ou ativação em produção.
- `php artisan subscriptions:expire` marca as assinaturas vencidas no histórico. Está agendado de hora em hora. Em implantação, executar o scheduler Laravel. A segurança não depende dele: a data é verificada em cada acesso.

## Escopo dos projetos

O plano é o **do proprietário do projeto**. Um usuário Free convidado para um projeto Equipe pode colaborar nos recursos que o proprietário contratou, respeitando seu papel. Um usuário Pro não consegue habilitar recursos Pro num projeto cujo proprietário está no Free. O plano pessoal controla a criação e os recursos dos projetos próprios.

`ProjectPolicy` aplica:

- Proprietário: administra o projeto, os membros, os papéis e a exclusão.
- Membro: consulta e edita tarefas e checklist; não administra o projeto nem os membros.
- Gerente (Equipe): administra dados do projeto, tarefas e membros comuns. Só o proprietário nomeia/remove gerentes ou exclui o projeto.
- Leitor (Equipe): consulta; não cria, edita ou exclui tarefas/checklist. Permanece somente leitura se o projeto voltar ao Free.
- Usuário externo: não acessa nem altera o projeto. Ser administrador da plataforma não concede acesso implícito aos projetos dos usuários.

IDs aninhados são conferidos: uma tarefa de outro projeto ou um item de outro checklist retorna 404. Operações sem permissão retornam 403; validações/cotas retornam 422; projeto arquivado bloqueia alterações de tarefas com 409. Consultas são limitadas aos projetos próprios ou com participação.

## Rotas

Rotas do sistema usam sessão Laravel, CSRF para gravações e middleware `auth`, `verified` e `subscription`. Para JSON, enviar `Accept: application/json`; páginas HTML mantêm formulários com CSRF e métodos Laravel.

| Método | Rota | Finalidade |
| --- | --- | --- |
| GET | `/account/entitlements` | Plano efetivo, recursos e limites |
| GET | `/account/team-support` | Contato do suporte Equipe |
| GET/POST | `/projects` | Listar/buscar e criar projetos |
| GET/PATCH/DELETE | `/projects/{project}` | Consultar, editar/arquivar, excluir |
| GET/POST | `/projects/{project}/tasks` | Pesquisar tarefas e criar |
| PATCH/DELETE | `/projects/{project}/tasks/{task}` | Editar/mover/excluir tarefa |
| POST | `/projects/{project}/members` | Adicionar conta já cadastrada e confirmada |
| PATCH/DELETE | `/projects/{project}/members/{member}` | Alterar papel/remover |
| POST | `/projects/{project}/tasks/{task}/checklist` | Criar item |
| PATCH/DELETE | `/projects/{project}/tasks/{task}/checklist/{item}` | Atualizar/concluir/excluir item |
| GET | `/projects/{project}/report` | Relatório Pro/Equipe |
| GET | `/projects/{project}/history` | Histórico Equipe paginado |
| GET | `/admin/subscribers` | Lista administrativa de assinantes |
| PATCH | `/admin/subscribers/{user}` | Atribuir/revogar assinatura com validade |

Campos de tarefa: `title`, `description`, `status` (`todo`, `doing`, `done`), `priority` (`low`, `medium`, `high`) e `due_date` (`AAAA-MM-DD`). Os códigos numéricos 1/2/3 do legado não são aceitos diretamente por essas rotas. Estado e prioridade possuem listas explícitas; autor e projeto são definidos pelo servidor.

O histórico registra criação, edição e exclusão de tarefas, inclusive depois de apagar a tarefa. Sua consulta é exclusiva do Equipe. Não é um registro completo de ações administrativas ou de todo o projeto.

## Administração

`is_admin` é um atributo separado de `job_role`, assinatura e papéis de projeto. Não é aceito no cadastro/perfil. As rotas `/admin/*` exigem também middleware `admin`.

Para promover uma **conta existente**, executar deliberadamente no servidor:

```powershell
php artisan user:admin pessoa@exemplo.com
php artisan user:admin pessoa@exemplo.com --revoke
```

Nenhuma conta recebe promoção automática pela migration. A administração gerencia assinantes em `/admin/subscribers` e cotas/preços em `/admin/plans`. Planos utilizados não podem ser apagados nem ter slug alterado. Cancelar um plano pago via administração consiste em atribuir o Free; assinaturas ativas anteriores passam a `replaced`.

## Banco e instalação

Novas tabelas Laravel: `projects`, `project_members`, `tasks`, `checklist_items` e `task_events`. Foram usadas chaves estrangeiras e exclusão em cascata para dados vinculados. A exclusão de uma conta remove seus projetos e participações; a autoria de tarefas em projetos de terceiros fica nula. Não foi feita importação dos usuários, senhas ou dados MySQL do legado.

```powershell
php artisan migrate
php artisan db:seed --class=PlanSeeder
npm run build
php artisan test
```

O dashboard e as telas originais foram integrados ao Laravel. Consulte [sistema-admin-email.md](sistema-admin-email.md) para o layout completo, administracao, metricas e SMTP. Nao houve importacao do MySQL antigo. Login SPA, animacao de carregamento e landing page foram preservados.

## Validação

`PlanAuthorizationTest` cobre alteração fraudulenta de plano no cadastro, `plan_id` sem assinatura, expiração, cancelamento, validade ausente, matriz de recursos, limites, acesso administrativo, papéis, IDs de outras contas/projetos, remoção de membro, checklist e projeto arquivado. Os testes de autenticação, PIX e documentos legais continuam na suíte.

Referência de framework: [middleware Laravel 12](https://laravel.com/framework/docs/12.x/middleware).
