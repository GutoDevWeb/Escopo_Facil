# Escopo Fácil

Aplicação web acadêmica para organizar projetos, tarefas e equipes, desenvolvida no curso de **Tecnologia em Sistemas para Internet do Centro Universitário Senac**.

## Funcionalidades

- Site responsivo com animações, planos, contato e documentos legais.
- Cadastro, login, confirmação de e-mail e recuperação de senha.
- Projetos, Kanban, busca, checklists, prioridades e prazos.
- Planos **Free**, **Escopo Pro** e **Equipe**, com permissões verificadas no backend e sete dias de teste completo para novas contas Free.
- Checkout PIX de **simulação**, painel administrativo e notificações internas.

## Tecnologias

**PHP 8.2+ · Laravel 12 · Blade · SQLite · JavaScript · React 19 · Motion · CSS · Vite 7 · PHPUnit 11**

O projeto também utiliza Tailwind/Alpine na base Breeze, Chart.js nos gráficos e QRCode no checkout. As versões e o papel de cada dependência estão na [documentação completa](docs/projeto.md).

## Rodar localmente

Requisitos: PHP/Composer, PDO SQLite e Node.js 20.19+ ou 22.12+ com npm.

Na raiz de uma instalação nova:

```sh
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

Acesse `http://127.0.0.1:8000` e ajuste `APP_URL` no `.env` para esse endereço. Configure SMTP no `.env` para receber a confirmação de cadastro. Durante o desenvolvimento do frontend, use `npm run dev` em outro terminal.

## Contas de demonstração

```sh
php artisan dev:plan-demos
php artisan dev:create-admin
php artisan dev:notification
```

As contas demonstrativas possuem projetos e tarefas de exemplo. Confira os [logins e o roteiro de comparação dos planos](docs/contas-demonstracao.md). A conta administrativa usa as variáveis `DEV_ADMIN_EMAIL` e `DEV_ADMIN_PASSWORD`; sem senha configurada, o comando gera uma aleatória.

## Verificação

```sh
php artisan test
npm run build
```

## Documentação

- [Visão geral, arquitetura, tecnologias e instalação](docs/projeto.md)
- [Planos e autorização](docs/backend-planos.md)
- [Autenticação](docs/autenticacao.md)
- [Pagamentos PIX simulados](docs/pagamentos-pix.md)
- [Administração e e-mail](docs/sistema-admin-email.md)

O checkout é acadêmico, sem cobrança real. O `.env`, o banco local e as dependências instaladas não são versionados. `Sistema` (anteriormente `backup_antigo`) preserva a implementação anterior; a aplicação atual utiliza o Laravel.

**Equipe:** Natan Oliveira, Guilherme Augusto e Nicollas Ramos.
