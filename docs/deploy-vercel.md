# Deploy online na Vercel

O Escopo Fácil usa Laravel e precisa executar PHP. O deploy é feito pelo **Container Runtime**, atualmente em beta na Vercel, com `Dockerfile.vercel` e FrankenPHP. Não use o preset Vite nem `dist` como diretório de saída. O Vite compila somente os assets, dentro do contêiner.

**O projeto continua acadêmico, agora hospedado.** `APP_ENV=production` configura segurança e funcionamento no servidor; não transforma a aplicação em um serviço comercial. Planos e pagamentos continuam demonstrativos, sem movimentação financeira real.

## Arquitetura preparada

- Laravel/PHP 8.4 e servidor FrankenPHP; raiz pública `/app/public`.
- Build frontend com Node 22 e instalação PHP via `composer.lock`, sem dependências de desenvolvimento na imagem final.
- Banco PostgreSQL externo por padrão; MySQL também aceito. Sessões, cache, rate limits e locks são compartilhados no banco.
- E-mails de conta síncronos por SMTP do Gmail exclusivo do projeto (`escopofacil@gmail.com`); nenhum worker é necessário no fluxo atual.
- Logs em stderr, HTTPS público, cookies seguros/encriptados e configuração de proxy explícita.
- Cron diário autenticado para atualizar assinaturas vencidas. As permissões já verificam o vencimento em cada requisição, independentemente do cron.
- Contas demonstrativas e o SQLite local não entram na imagem. O cadastro público continua criando contas reais com sete dias de recursos avançados.

## 1. Serviços que precisam existir

1. Criar um PostgreSQL gerenciado, com backups habilitados. Pode usar uma integração do Marketplace da Vercel. Guardar a conexão fornecida pelo provedor, com TLS; preferir a conexão com pooling compatível com sessões/transações PHP. Usar banco separado para Preview e Production.
2. Criar/acessar a conta Google `escopofacil@gmail.com`, ativar a verificação em duas etapas e gerar uma senha de aplicativo. Essa opção não exige comprar um domínio nem usar o Resend.
3. Escolher uma caixa que realmente receba contato para `CONTACT_MAIL_TO`. O serviço de envio não cria uma caixa de suporte.
4. Definir a URL pública final do sistema. Pode ser a URL `vercel.app` inicialmente; mudar depois exige atualizar `APP_URL` e republicar.

Nunca enviar senhas ou chaves ao Git. Não reutilizar o banco, o administrador ou as senhas de demonstração locais.

## 2. Variáveis privadas

Em **Vercel → Settings → Environment Variables**, configurar os valores de [`.env.production.example`](../.env.production.example). O arquivo é um modelo, não é carregado automaticamente pela Vercel.

Valores essenciais:

| Variável | Valor |
| --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | URL HTTPS final, sem caminho adicional |
| `APP_KEY` | Chave Laravel gerada uma única vez |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | URL de conexão privada fornecida pelo banco; alternativamente preencher host/porta/banco/usuário/senha |
| `DB_SSLMODE` | `require` ou a opção mais estrita suportada pelo provedor |
| `SESSION_DRIVER` / `CACHE_STORE` | `database` / `database` |
| `SESSION_SECURE_COOKIE` / `SESSION_ENCRYPT` / `SESSION_HTTP_ONLY` | `true` / `true` / `true` |
| `QUEUE_CONNECTION` / `LOG_CHANNEL` | `sync` / `stderr` |
| `PORT` / `TRUST_PLATFORM_PROXY` | `8080` / `true` (na Vercel) |
| `MAIL_MAILER` / `MAIL_HOST` | `smtp` / `smtp.gmail.com` |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | `escopofacil@gmail.com` / senha de aplicativo dessa conta Google |
| `MAIL_PORT` / `MAIL_SCHEME` / `MAIL_REQUIRE_TLS` | `587` / `smtp` / `true` |
| `MAIL_FROM_ADDRESS` | `escopofacil@gmail.com` |
| `MAIL_FROM_NAME` | `Escopo Fácil` |
| `SUPPORT_EMAIL` / `CONTACT_MAIL_TO` | `escopofacil@gmail.com` |
| `MAIL_REPLY_TO_ADDRESS` | `escopofacil@gmail.com` |
| `CRON_SECRET` | Segredo aleatório com no mínimo 32 caracteres |
| `LEGAL_VERSION` | `2026-10-22` (documentos do projeto acadêmico) |
| `LEGAL_RESPONSIBLE` | Identificação real dos responsáveis pela operação |

Para MySQL, usar `DB_CONNECTION=mysql`, conexão e porta do provedor e configurar o certificado em `MYSQL_ATTR_SSL_CA` quando exigido. A imagem inclui os dois drivers PDO; o banco externo ainda precisa ser validado.

Gerar valores localmente sem alterar o `.env` de desenvolvimento:

```sh
php artisan key:generate --show
php -r "echo bin2hex(random_bytes(32));"
```

Não trocar `APP_KEY` a cada deploy: ela protege sessões e dados criptografados. `TRUST_PLATFORM_PROXY=true` pressupõe que a aplicação seja acessada por um proxy que substitua os cabeçalhos encaminhados; não usar essa opção indiscriminadamente em um servidor exposto diretamente.

## 3. Preparar o banco antes do primeiro acesso

É necessário Docker para executar a mesma imagem localmente. Não é necessário instalar Docker para importar o repositório pelo painel da Vercel.

```sh
docker build -f Dockerfile.vercel -t escopo-facil .
docker run --rm --env-file .env.production escopo-facil php artisan migrate --force
docker run --rm --env-file .env.production escopo-facil php artisan db:seed --class=PlanSeeder --force
docker run --rm --env-file .env.production escopo-facil php artisan deploy:check
```

O `.env.production` usado nesses comandos deve conter as mesmas variáveis privadas configuradas na Vercel e permanecer fora do Git. Alternativamente executar os comandos em um runner privado com PHP 8.4, dependências e acesso ao banco. Migrations nunca são executadas em cada requisição nem no início automático de cada instância. Não usar `migrate:fresh` em produção.

**Neon:** para migrations e alterações de estrutura, use a URL **direta/unpooled**, disponível em **Connect** ao desativar o pooling. Ela aponta para o mesmo banco, sem `-pooler` no hostname. O site pode manter a URL pooled na Vercel. Se necessário, utilize um arquivo privado separado para os comandos administrativos; não substitua a conexão da aplicação apenas para executar migrations. [Orientação do Neon](https://neon.com/docs/connect/connection-pooling).

`deploy:check` valida a configuração, conexão, tabelas, planos e ausência de contas demonstrativas sem enviar mensagens ou imprimir credenciais. Ele não autentica no Gmail nem prova que o destinatário recebeu o e-mail.

## 4. Configurar o projeto Vercel

1. Importar o repositório com a raiz apontando para a pasta que contém `Dockerfile.vercel`.
2. Selecionar **Container** em Framework Preset. O nome `Dockerfile.vercel` foi escolhido para ter precedência sobre a detecção de Vite.
3. Remover os overrides antigos de Build Command, Install Command e Output Directory; não deixar `dist` ou `public/build` como saída estática.
4. Preencher as variáveis e fazer o deploy. O build é definido pelo Dockerfile.
5. Verificar os logs. A inicialização executa cache de configuração, `deploy:check --configuration-only`, cache de rotas e views. Configurações essenciais inválidas impedem a inicialização e aparecem nos logs privados.

Pelo CLI autenticado, os comandos equivalentes são:

```sh
vercel link
vercel project update --framework container --yes
vercel deploy --prod
```

`vercel.json` agenda `/internal/maintenance` diariamente às 06:00 UTC. A Vercel envia `Authorization: Bearer CRON_SECRET`; o endpoint recusa solicitações sem o segredo e usa um lock compartilhado. A frequência diária é compatível com as restrições usuais do Hobby; a autorização dos planos não depende dessa frequência.

## 5. Validar a instalação

1. Abrir `/up` (saúde do Laravel; não testa banco nem SMTP) e `/`.
2. Cadastrar uma conta própria, receber o e-mail pelo remetente institucional, confirmar e entrar.
3. Criar projeto, tarefa e checklist; sair e entrar novamente; conferir persistência após um redeploy.
4. Testar recuperação de senha e formulário de contato com contas próprias. Conferir a caixa do suporte, spam e logs privados se necessário.
5. Cadastrar a conta administrativa real, confirmar o e-mail e promover pelo terminal privado:

   ```sh
   docker run --rm --env-file .env.production escopo-facil php artisan user:admin seu-email@seu-dominio
   ```

6. Confirmar que outro usuário não acessa `/admin`, nem projetos alheios, e que o Free perde recursos avançados após o período de avaliação.
7. Ativar backups e testar restauração em um banco separado. Configurar alerta de falhas no deploy e no provedor de e-mail.

## 6. O que ainda depende da operação

O preparo do código não cria banco, conta Google ou projeto Vercel. Nenhum deploy externo é considerado validado antes dos testes acima. O Gmail possui limites de envio e pode restringir mensagens; esta configuração atende à demonstração acadêmica e deve ser acompanhada durante os testes.

**Pagamentos permanecem como demonstração acadêmica, sem cobranças reais.** Para demonstrar a compra de Pro e Equipe na hospedagem, mantenha `APP_ENV=production` e configure as variáveis abaixo na Vercel. Depois faça um novo deploy:

```dotenv
PIX_DEMO_ENABLED=true
PIX_DRIVER=abacate
ABACATEPAY_DEV_KEY=dev_SUA_CHAVE_DE_TESTE
```

A chave deve ser criada no modo de desenvolvimento da AbacatePay e mantida como segredo no servidor. O fluxo gera QR Code de teste, permite clicar em **Simular pagamento** e só ativa o plano após o servidor consultar o provedor e receber `PAID`. Não transferir dinheiro. Chaves `prod_` e o simulador local são recusados na hospedagem, mesmo com a opção habilitada. Com `PIX_DEMO_ENABLED=false` (padrão), o checkout hospedado permanece indisponível. Free com avaliação e atribuição de planos pela administração continuam disponíveis.

Os termos e a privacidade mantêm a versão acadêmica `2026-10-22`, a identificação institucional e os avisos de simulação do site. A hospedagem não elimina os cuidados com dados pessoais: os responsáveis devem confirmar os contatos e os prazos de conservação/backups dos serviços contratados.

O sistema atual utiliza avatares predefinidos e não tem upload persistente de arquivos. Qualquer upload futuro precisa de armazenamento de objetos externo; o disco do contêiner não é durável. Não iniciar workers em background dentro do processo HTTP.

O workflow `.github/workflows/production-checks.yml` executa a suíte em PostgreSQL, compila os assets, constrói a imagem e testa o HTTP do contêiner com dados e segredos fictícios de CI. Ele só será executado no GitHub após publicar as alterações. Nesta máquina, Docker não está instalado; portanto, o build da imagem e o teste em PostgreSQL não foram executados localmente. Os testes locais utilizam SQLite em memória e não alteram o banco de desenvolvimento.

## 7. Atualizações

Fazer backup, testar a alteração, executar migrations compatíveis no banco externo uma única vez e publicar a nova imagem. Manter a chave da aplicação e as variáveis privadas. Mudanças de schema incompatíveis exigem um plano de transição antes de fazer rollback de código.

## Referências verificadas

- [Vercel: PHP com Docker](https://vercel.com/kb/guide/deploy-php-on-vercel-with-docker)
- [Vercel: Container Runtime e limitações](https://vercel.com/docs/functions/runtimes/container)
- [Vercel: segurança de cron](https://vercel.com/docs/cron-jobs/manage-cron-jobs)
- [Laravel: deployment](https://laravel.com/docs/12.x/deployment)
- [Google: senhas de aplicativo](https://support.google.com/accounts/answer/185833?hl=pt-BR)
- [ANPD: direitos dos titulares](https://www.gov.br/anpd/pt-br/assuntos/titular-de-dados/direito-dos-titulares)
