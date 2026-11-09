# Sistema completo, administração e e-mail

## Entrada no sistema e banco novo

O login verificado abre `/dashboard` com o layout do sistema de `backup_antigo`: sidebar, indicadores, gráficos, projetos, Kanban, modais, membros, configurações e avatar. Os dados são fornecidos por Eloquent. Não há conexão ao MySQL antigo, sessão PHP independente, importação de contas antigas nem execução dos scripts PHP do backup. Os arquivos do backup foram preservados.

`SystemController` prepara os dados das telas; `SystemActionController` traduz os payloads dos componentes antigos para as operações autorizadas de `WorkspaceController`. As URLs de compatibilidade `/system/actions/...php` são rotas Laravel, protegidas por autenticação, confirmação de e-mail, CSRF e policies. Os arquivos públicos contêm somente recursos estáticos.

As regras dos planos permanecem no servidor. O Free tem sete dias de todos os recursos, contados da criação da conta. Depois, prioridades, prazos, relatórios e funções de Equipe são bloqueados, com cadeados cinza na interface; o acesso básico continua disponível. O resumo do plano e a validade do teste ficam em Configuração. A permissão de edição também considera proprietário, gestor, membro, visualizador e projeto arquivado. O indicador da sidebar sempre mostra o plano **do usuário conectado**, inclusive quando ele participa de projeto pertencente a outro plano. O card de acesso ao site fica restrito a contas comuns Free.

## Administração

`/admin` exige `auth`, `verified` e `admin`. O atributo `users.is_admin` controla o perfil administrativo; ele não pode ser preenchido pelo cadastro público. O link Administração e o rótulo minimalista **Admin** aparecem apenas para administradores. Um administrador não recebe automaticamente uma assinatura comercial: perfil administrativo e plano são conceitos separados.

O painel inclui:

- tabela paginada de usuários com busca por nome/e-mail, status de confirmação e plano efetivo;
- sessões válidas e presença estimada nos últimos cinco minutos, consultadas na tabela `sessions`;
- assinaturas pagas ativas e histórico de assinaturas, separados do número de pagamentos confirmados;
- volume de pagamentos **simulados**, explicitamente sem receita real;
- projetos, tarefas, conclusões, logins e requisições autenticadas;
- gráficos diários dos últimos 14 dias, sem dependência de serviços externos.

`usage_metrics` armazena contadores diários por usuário e última atividade. Não registra senhas, conteúdo de sessões ou payloads das requisições. A coleta começa na instalação; os indicadores não inventam dados históricos. Requisições com erro não incrementam o contador de uso. O painel é atualizado ao recarregar a página.

Uma sessão válida pode estar ociosa. Fechar o navegador não informa imediatamente o logout ao servidor. Com `SESSION_DRIVER=database`, o painel distingue **Ativo agora**, **Sessão válida · inativo** e **Sem sessão ativa**. Outros drivers não oferecem a listagem de sessões neste painel.

### Conta dedicada de desenvolvimento

```dotenv
DEV_ADMIN_EMAIL=admin@escopofacil.test
DEV_ADMIN_PASSWORD=defina-uma-senha-local-forte
```

```sh
php artisan dev:create-admin
```

O comando funciona apenas em `local/testing`, exige senha com pelo menos 12 caracteres quando configurada e gera uma senha aleatória caso a variável esteja vazia. A conta é confirmada exclusivamente por esse comando de desenvolvimento. Uma execução repetida não redefine senha, não promove uma conta existente e não muda suas permissões. Não é executado automaticamente em produção. A credencial configurada neste ambiente fica no `.env`, que é ignorado pelo Git.

## Diagnóstico e configuração de e-mail

O legado possui `Sistema/includes/mailer.php` (originalmente em `backup_antigo`), que utiliza `mail()` nativo. O `.env.example` antigo contém remetente e opções de links de depuração, sem usuário/senha SMTP. Não havia `.env` SMTP no backup para importar. O Laravel estava com `MAIL_MAILER=log`: a mensagem era registrada localmente, sem envio externo.

O fluxo existente é **confirmação de endereço de e-mail**, por link assinado e com expiração; não é autenticação em dois fatores a cada login. Cadastro e reenvio utilizam as notificações Laravel e o template `emails/auth.blade.php`; erros de transporte não são apresentados como envio concluído. A confirmação continua exigindo a conta autenticada correspondente, assinatura e hash válidos.

Configuração local autorizada temporariamente, enquanto a caixa do domínio do projeto não existe:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_REQUIRE_TLS=true
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=escopofacil@gmail.com
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=escopofacil@gmail.com
MAIL_FROM_NAME="Escopo Fácil"
```

Preencha `MAIL_PASSWORD` com a senha de aplicativo **da mesma conta Google**. Não coloque a senha pessoal ou credenciais no código, nos testes ou no Git. Para usar `Suporte@escopofacil.com.br` futuramente, será necessário criar/hospedar essa caixa ou configurar um remetente autorizado no provedor. Os contatos públicos do site continuam com o endereço de suporte solicitado.

```sh
php artisan config:clear
php artisan mail:test-smtp Ramosnicollas99@gmail.com
```

Esse teste envia uma mensagem de diagnóstico sem criar/modificar contas. O sucesso significa aceitação pelo servidor SMTP; confirme a chegada na caixa de entrada/spam do destinatário.

Para testar a confirmação real de uma conta cadastrada e ainda não confirmada:

```sh
php artisan mail:test-verification email-da-conta-de-teste
```

O link deve ser aberto no navegador autenticado nessa conta. Nenhum dos comandos imprime a senha SMTP. Ambos ficam restritos a desenvolvimento. Caso exista servidor persistente, reinicie-o depois de alterar o `.env`; limpe também o cache de configuração.

### Preparação para hospedagem

O modelo [`.env.production.example`](../.env.production.example) separa o remetente (`MAIL_FROM_ADDRESS`), a caixa de respostas dos e-mails de conta (`MAIL_REPLY_TO_ADDRESS`), o contato público (`SUPPORT_EMAIL`) e o destino do formulário (`CONTACT_MAIL_TO`). O formulário não usa mais `LEGAL_CONTACT_EMAILS` para definir o destino. Confirmação e recuperação são enviadas apenas ao próprio usuário, sem cópia para o administrador.

O modelo de produção usa Gmail SMTP com a conta exclusiva `escopofacil@gmail.com`, nome Escopo Fácil e senha de aplicativo dessa mesma conta em `MAIL_PASSWORD`. Ela também recebe respostas e contatos. Não é necessário domínio próprio para essa opção. O [guia de hospedagem](hospedagem.md) descreve a configuração atual e mantém Resend como alternativa futura com domínio verificado. Nenhuma senha válida é incluída nos modelos ou no Git.

### Teste SMTP local executado

O Laragon deste computador possui Mailpit. Foi validado no navegador, em banco SQLite isolado, o caminho completo: cadastro → envio SMTP real para Mailpit → mensagem capturada → abertura do link assinado → acesso ao dashboard. Não foi usado `Mail::fake()` nesse teste. Houve também validação de login, modais de projeto/tarefa, CSRF, bloqueios Free e painel admin sem exceções JavaScript.

Mailpit local: `http://127.0.0.1:8025`, SMTP `127.0.0.1:1025`. Para usá-lo, configure host/porta locais, usuário e senha vazios e `MAIL_REQUIRE_TLS=false`. Ele captura mensagens para testes e não as entrega na Internet.

Após configurar uma conta Gmail existente e sua senha de aplicativo, o servidor aceitou o envio e o responsável confirmou o recebimento da mensagem. A entrega SMTP externa foi validada neste ambiente. Credenciais reais permanecem somente no `.env` local; novas instalações precisam configurar suas próprias contas.

## Instalação e validação

```sh
php artisan migrate
php artisan db:seed --class=PlanSeeder
php artisan dev:create-admin
php artisan test
```

Não execute `migrate:fresh` no banco de trabalho. As novas migrations adicionam avatar e métricas sem importar ou apagar dados. Os testes automatizados usam banco em memória, e a revisão no navegador utiliza banco isolado em `.preview`.

Arquivos principais desta integração:

- `app/Http/Controllers/SystemController.php`, `SystemActionController.php` e `Admin/DashboardController.php`;
- `app/Http/Controllers/WorkspaceController.php` e `ProfileController.php`;
- `app/Services/UsageMetrics.php`, `app/Http/Middleware/TrackSystemActivity.php` e `app/Support/SystemAvatar.php`;
- `app/Console/Commands/CreateDevelopmentAdmin.php`, `TestSmtp.php` e `TestVerificationMail.php`;
- `config/development.php`, `config/mail.php`, `.env.example`, `bootstrap/app.php`, `app/Providers/AppServiceProvider.php` e `routes/web.php`;
- `database/migrations/2026_10_23_170000_add_system_avatar_to_users.php` e `2026_10_23_180000_create_usage_metrics_table.php`;
- `resources/views/layouts/system.blade.php`, `layouts/navigation.blade.php`, `dashboard.blade.php`, `admin/dashboard.blade.php`, `workspace/data.blade.php` e `system/`;
- `public/system/assets/`, incluindo os recursos preservados do sistema e `css/integration.css`, `js/api.js`, `js/integration.js`;
- `tests/Feature/AdminDashboardTest.php` e `SystemInterfaceTest.php`.
