# Hospedagem e e-mail do Escopo Fácil

> **Deploy na Vercel:** siga [o roteiro atualizado de produção](deploy-vercel.md), com Container Runtime, PostgreSQL externo e variáveis privadas. As instruções abaixo também documentam a alternativa em servidor PHP convencional; o SQLite persistente citado nelas **não se aplica à Vercel**.

## Estado do preparo

O modelo [`.env.production.example`](../.env.production.example) utiliza **Gmail SMTP com a conta exclusiva `escopofacil@gmail.com`**. É necessário que essa conta exista, tenha verificação em duas etapas e uma senha de aplicativo em `MAIL_PASSWORD`. Não é necessário comprar domínio para essa configuração. As instruções do Resend abaixo permanecem como alternativa futura, caso a equipe passe a controlar um domínio próprio.

## 1. Endereços e envio de e-mail

| Variável | Finalidade |
| --- | --- |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Autenticação no provedor SMTP. O usuário pode ser um identificador técnico, conforme o provedor. |
| `MAIL_FROM_ADDRESS` | Endereço que o destinatário vê como remetente. Precisa ser autorizado pelo provedor. |
| `MAIL_FROM_NAME` | Nome exibido: Escopo Fácil. |
| `MAIL_REPLY_TO_ADDRESS` | Opcional: caixa existente que recebe respostas às mensagens de confirmação e recuperação. Vazio omite esse cabeçalho. |
| `SUPPORT_EMAIL` | Endereço público exibido no site e nos documentos. |
| `CONTACT_MAIL_TO` | Destino das mensagens enviadas pelo formulário de contato. |

Cadastro e recuperação enviam mensagens **somente para o endereço da conta do usuário**, sem cópia para o administrador. O remetente é a conta exclusiva do projeto, exibida com o nome Escopo Fácil; o endereço Gmail continua visível nos detalhes da mensagem. O formulário envia para a caixa de suporte configurada.

### Configuração atual: Gmail do projeto

1. Entrar em `escopofacil@gmail.com` e ativar a verificação em duas etapas.
2. Gerar uma senha em [Senhas de app do Google](https://myaccount.google.com/apppasswords), com um nome como `Escopo Fácil Vercel`.
3. Configurar `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_SCHEME=smtp`, `MAIL_REQUIRE_TLS=true`.
4. Usar `escopofacil@gmail.com` em `MAIL_USERNAME`, `MAIL_FROM_ADDRESS`, `MAIL_REPLY_TO_ADDRESS`, `SUPPORT_EMAIL` e `CONTACT_MAIL_TO`.
5. Em `MAIL_PASSWORD`, colocar a senha de aplicativo dessa conta, sem os espaços de apresentação. Não usar senha comum do Google nem chave Resend. Na Vercel, alterar as variáveis privadas e fazer redeploy.
6. Testar confirmação, recuperação e formulário de contato após publicar. A configuração sozinha não comprova a entrega. O Gmail tem limites de envio; acompanhar os testes acadêmicos.

Ao trocar o provedor local, a senha anterior é removida quando pertence a outra conta. A senha nova deve ser preenchida privadamente. A conta Google não é criada automaticamente pelo projeto.

### Ativar o Resend sem criar uma caixa de e-mail

1. Criar uma conta no [Resend](https://resend.com) e adicionar o domínio que vocês controlam em **Domains**. `escopofacil.com.br` no modelo só pode ser usado se vocês tiverem acesso a esse domínio.
2. Publicar os registros DNS indicados pelo painel e aguardar a verificação. Não copiar registros genéricos nem alterar o MX da caixa de entrada sem necessidade; seguir os nomes e valores exatos solicitados para envio.
3. Criar uma API key com permissão de envio para o domínio e colocá-la em `MAIL_PASSWORD` no ambiente privado do servidor. Não enviar a chave pelo chat nem adicioná-la ao Git.
4. Usar `MAIL_HOST=smtp.resend.com`, `MAIL_PORT=587`, `MAIL_USERNAME=resend`, `MAIL_SCHEME=smtp` e `MAIL_REQUIRE_TLS=true`, conforme o modelo.
5. Definir `MAIL_FROM_ADDRESS=nao-responda@SEU-DOMINIO` e `MAIL_FROM_NAME="Escopo Fácil"`. O endereço remetente não precisa ser uma caixa de entrada. Se verificar somente um subdomínio, o endereço deve usar esse subdomínio.
6. Deixar `MAIL_REPLY_TO_ADDRESS` vazio enquanto não houver caixa para respostas. O cliente de e-mail ainda pode tentar responder ao remetente, mas não haverá caixa convencional para receber essa resposta.
7. Preencher `CONTACT_MAIL_TO` com uma caixa que já exista para receber o formulário. Isso não muda o remetente das confirmações. Enquanto estiver vazio, o formulário fica indisponível para envio.
8. Atualizar o cache de configuração (`php artisan config:cache`) e testar cadastro e recuperação com contas próprias. Consultar as mensagens em **Emails** no painel do Resend.

A caixa `Suporte@escopofacil.com.br` exibida como contato público continua precisando de um destino real se vocês quiserem receber mensagens enviadas diretamente para ela. O Resend usado apenas para envio não cria essa caixa. Recebimento por API/webhook é outro fluxo e não está implementado neste projeto.

### Se também quiserem uma caixa de suporte

Para usar `Suporte@escopofacil.com.br` para receber respostas e contatos:

1. Ter acesso ao domínio e ao painel DNS.
2. Criar a caixa de suporte no serviço de e-mail escolhido, para receber respostas e contatos. Um serviço transacional de envio não necessariamente fornece uma caixa de entrada.
3. Autorizar o domínio/remetente no provedor SMTP.
4. Publicar exatamente os registros SPF e DKIM informados pelo provedor. Não criar dois registros SPF separados no mesmo nome; coordenar todos os remetentes no registro existente.
5. Configurar DMARC de acordo com os serviços legítimos de envio e acompanhar os relatórios antes de aplicar uma política restritiva. Registros MX são os fornecidos pelo serviço da caixa de entrada.
6. Preencher host, porta, credenciais e remetente no `.env` do servidor ou no gerenciador de variáveis da hospedagem.

O modelo usa STARTTLS na porta 587 (`MAIL_SCHEME=smtp`, `MAIL_REQUIRE_TLS=true`). Se o provedor exigir TLS implícito na porta 465, use `MAIL_SCHEME=smtps` e a porta informada por ele. Não desative a validação de certificados.

O formulário envia para `CONTACT_MAIL_TO` e mantém o visitante no Reply-To; o visitante não é usado como remetente SMTP. `LEGAL_CONTACT_EMAILS` deixa de controlar o destino desse formulário.

## 2. Requisitos da hospedagem

- PHP 8.2+ e extensões exigidas pelo Laravel e pelo banco escolhido; Composer disponível no build ou servidor.
- Servidor web apontando **exclusivamente para `public/`**. Não publicar a raiz do repositório nem a pasta histórica `Sistema/`.
- HTTPS e `APP_URL` com a URL pública exata, para gerar links corretos de confirmação e recuperação.
- `storage/` e `bootstrap/cache/` graváveis pelo processo PHP, sem permissões globais `777`.
- Node/npm no ambiente de build ou compilação antecipada dos assets. Node não precisa ficar executando para servir um build pronto.
- Saída de rede para o SMTP e possibilidade de executar o scheduler.
- Banco e arquivos persistentes com backups. O SQLite precisa ficar em disco persistente, gravável pelo PHP e fora de `public/`; um filesystem efêmero perde os dados em redeploys. Múltiplas instâncias precisam de uma arquitetura de banco/sessões compartilhada apropriada.

Proxies e balanceadores devem ser configurados conforme a hospedagem para que o Laravel reconheça HTTPS e gere URLs corretas. Não confiar em qualquer proxy sem conhecer a infraestrutura.

## 3. Primeira instalação no servidor

Os comandos abaixo são um roteiro para adaptar à hospedagem; não foram executados em um servidor externo.

1. Publicar o código e instalar as dependências:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci
   npm run build
   ```

   O build usa dependências de desenvolvimento do npm. Se ele for feito em CI, enviar também `public/build/` para o servidor, apesar de a pasta não ser versionada. Não publicar `public/hot`.

2. Usar o modelo `.env.production.example` para preparar o `.env` privado. Substituir todos os placeholders, preencher credenciais e definir `APP_ENV=production`, `APP_DEBUG=false` e a URL HTTPS. Não copiar o `.env` pessoal nem o banco de testes.

3. Criar o arquivo SQLite no caminho persistente configurado, com proprietário/permissões adequados. Se a hospedagem exigir outro banco, adaptar as variáveis e validar as migrations nesse banco antes da publicação.

4. Gerar a chave **somente na primeira instalação**, e preparar o banco:

   ```sh
   php artisan key:generate --force
   php artisan migrate --force
   php artisan db:seed --class=PlanSeeder --force
   php artisan optimize
   ```

   Não regenerar a chave em atualizações. Guardar `APP_KEY` em local seguro junto à estratégia de recuperação. Não rodar `migrate:fresh` em produção.

5. Configurar a raiz web `public/`, HTTPS e uma chamada cron a cada minuto:

   ```cron
   * * * * * cd /CAMINHO/DO/PROJETO && php artisan schedule:run >> /dev/null 2>&1
   ```

   O envio de confirmação e recuperação atual é síncrono: não depende de worker de fila. Se futuramente essas notificações forem enfileiradas, um worker supervisionado será necessário, com reinício a cada deploy.

6. Cadastrar e confirmar uma conta real; promover a conta autorizada pelo terminal privado do servidor:

   ```sh
   php artisan user:admin email-do-administrador@example.com
   ```

   Os comandos `dev:*` não são para produção. Não importar contas demonstrativas nem o administrador de testes.

## 4. Validar antes de abrir ao público

- Abrir `/up`, a landing page e os assets em HTTPS.
- Cadastrar uma conta própria de teste e receber a confirmação, verificando remetente, Reply-To e URL do botão.
- Conferir os cabeçalhos de autenticação SPF/DKIM/DMARC no destinatário. Aceitação pelo SMTP não garante que a mensagem chegará à caixa principal; consultar também spam e os eventos do provedor.
- Confirmar a conta, entrar, recuperar a senha e sair.
- Enviar um contato e verificar se chega à caixa institucional, com resposta direcionada ao visitante.
- Confirmar bloqueio de administração para usuários comuns e acesso aos projetos conforme permissões/plano.
- Verificar logs no servidor sem exibir mensagens de depuração publicamente.
- Testar persistência após redeploy e restauração de backup.

Os comandos locais `mail:test-smtp` e `mail:test-verification` continuam restritos a local/testing; em produção, validar pelos fluxos reais acima, com contas próprias.

## 5. Atualizações

Fazer backup consistente do banco e dos arquivos persistentes, publicar a nova versão, instalar dependências, gerar o build, executar `php artisan migrate --force` e `php artisan optimize`. Após mudar variáveis, reconstruir o cache de configuração. Reiniciar processos persistentes existentes e validar `/up` e os fluxos afetados. Alterações incompatíveis no banco exigem plano de migração/rollback adequado.

## 6. Limite atual: pagamentos

**O PIX implementado é acadêmico e está bloqueado em produção.** Publicar o site não habilita compras de Pro/Equipe. Não basta trocar uma chave para habilitar cobrança real: falta definir e implementar o fluxo de produção, incluindo confirmação confiável do provedor, webhook e tratamento do ciclo de assinatura. Antes do lançamento comercial, decidir esse fluxo e alinhar os botões e textos do site. A administração pode atribuir assinaturas manualmente com validade, conforme as regras existentes.

## Referências

- [Laravel 12: deployment](https://laravel.com/docs/12.x/deployment)
- [Laravel 12: mail](https://laravel.com/docs/12.x/mail)
- [Resend: SMTP](https://resend.com/docs/send-with-smtp)
- [Resend: verificação de domínio](https://resend.com/docs/dashboard/domains/introduction)
- [Administração e e-mail](sistema-admin-email.md)
- [Pagamentos simulados](pagamentos-pix.md)
