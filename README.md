<p align="center">
  <img src="public/system/assets/images/Logo.png" alt="Logo Escopo Fácil" width="280">
</p>

<p align="center">
  <strong>Organize tarefas, acompanhe o progresso e conclua projetos com clareza.</strong>
</p>

<p align="center">
  <img alt="Projeto acadêmico" src="https://img.shields.io/badge/projeto-acadêmico-8b0836?style=for-the-badge">
  <img alt="PHP 8.2 ou superior" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white">
  <img alt="Laravel 12" src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white">
  <img alt="SQLite" src="https://img.shields.io/badge/SQLite-database-003B57?style=for-the-badge&logo=sqlite&logoColor=white">
</p>

## Sobre

Para hospedar o projeto acadêmico completo, siga o [guia de deploy na Vercel](docs/deploy-vercel.md). A configuração usa PHP em contêiner, banco externo e e-mail transacional; o preset Vite estático não executa o backend. Planos e pagamentos permanecem demonstrativos, sem cobranças reais.

O **Escopo Fácil** é uma aplicação web para gerenciamento de projetos voltada a equipes que buscam mais organização, colaboração e controle sobre suas entregas. Desenvolvido como projeto acadêmico no curso de **Tecnologia em Sistemas para Internet do Centro Universitário Senac**, reúne um site institucional, autenticação, sistema de trabalho e painel administrativo.

Com ele, os usuários criam projetos, adicionam membros, organizam tarefas em um Kanban e acompanham checklists. A plataforma também oferece personalização de perfil, relatórios e funcionalidades liberadas conforme o plano contratado.

A implementação atual utiliza **Laravel**, preservando o visual e as interações do sistema original. A pasta [`Sistema/`](Sistema/) mantém a versão anterior em PHP, PDO e MySQL como referência histórica.

## Problema que o projeto resolve

Informações espalhadas em conversas, planilhas e arquivos dificultam saber quem está fazendo cada tarefa, quais entregas estão atrasadas e o que já foi concluído. O Escopo Fácil centraliza essas informações em uma plataforma visual para estudantes, equipes pequenas e grupos de trabalho.

## Funcionalidades

- 🔐 Cadastro, login, logout, confirmação de e-mail e recuperação de senha.
- 👤 Edição de perfil, alteração de senha e exclusão de conta com confirmação.
- 🎨 Personalização de avatar com cores e ícones.
- 📁 Criação, consulta, edição, arquivamento e exclusão de projetos.
- 👥 Gerenciamento de membros e papéis de acesso por projeto.
- 📌 Kanban com movimentação de cartões, busca e checklists.
- 📊 Prioridades, prazos, relatórios e histórico conforme o plano.
- 🔔 Notificações internas persistentes, com indicador apenas para itens não visualizados.
- 💳 Planos Free, Escopo Pro e Equipe, com checkout PIX simulado.
- 🛡️ Administração de usuários, assinaturas e planos, com métricas e gráficos.
- 🌐 Site responsivo com animações, planos, contato, equipe e documentos legais.
- 🍞 Formulários em modais, feedback visual e mensagens de validação.
- 🧪 Testes automatizados e contas demonstrativas para comparar os planos.

## Tecnologias

| Camada | Tecnologias |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12, Eloquent, migrations, policies e middleware |
| Templates e interface | Blade, HTML, CSS e JavaScript |
| Animações da landing page | React 19, React DOM e Motion |
| Componentes da base de autenticação | Laravel Breeze, Tailwind CSS e Alpine.js |
| Gráficos e checkout | Chart.js e QRCode |
| Banco de dados atual | SQLite |
| E-mail | Notificações Laravel, Symfony Mailer e SMTP |
| Build | Vite 7, Laravel Vite Plugin, Node.js e npm |
| Testes | PHPUnit 11, Faker e Mockery |
| Desenvolvimento | Composer, Artisan, Git/GitHub e Laragon no ambiente Windows utilizado |

As versões instaladas e a função das dependências estão na [documentação completa](docs/projeto.md). O sistema anterior utilizava PHP/PDO e MySQL; sua estrutura não é necessária para executar a aplicação atual.

## Como o projeto funciona

A aplicação usa páginas renderizadas pelo Laravel, com interações assíncronas em JavaScript. React é utilizado em componentes de animação da landing page.

```text
app/                   Controllers, models, policies, serviços e comandos
bootstrap/             Inicialização e configuração de middleware
config/                Configurações, incluindo funcionalidades dos planos
database/              Migrations, factories e seeders
resources/views/       Telas e componentes Blade
resources/js/          Landing page, checkout e entradas do Vite
public/assets/landing/ Imagens, estilos e recursos do site
public/system/assets/  Estilos, scripts e imagens do sistema interno
routes/                Rotas públicas, autenticadas e administrativas
tests/                 Testes automatizados
docs/                  Documentação técnica e roteiros de teste
Sistema/               Implementação original preservada como referência
```

```text
Usuário envia formulário ou ação da interface
        ↓
Rotas e middleware verificam a sessão e o acesso
        ↓
Controller valida os dados
        ↓
Policies e PlanAccess verificam permissões e plano
        ↓
Eloquent consulta ou atualiza o banco
        ↓
Servidor responde com uma página, redirecionamento ou JSON
```

## Site institucional e protótipo

O site institucional faz parte da aplicação atual e abre na rota `/`. Após iniciar o servidor local, acesse **http://127.0.0.1:8000**.

O [protótipo no Figma](https://www.figma.com/design/r6XBWNskGGCUPOhLBtpp5D/Escopo-facil?node-id=373-112&t=WbsunolnTIE2uQLY-1) permanece como referência do desenvolvimento visual.

## Principais fluxos do sistema

1. **Cadastro e confirmação:** o usuário cria uma conta Free, aceita os documentos e confirma o endereço pelo link recebido por e-mail.
2. **Login:** a conta confirmada acessa o sistema e suas funcionalidades autorizadas.
3. **Dashboard:** apresenta indicadores, atividades e gráficos dos projetos e tarefas.
4. **Projetos e membros:** o usuário organiza projetos e compartilha o trabalho conforme os papéis permitidos.
5. **Tarefas e checklist:** os cartões passam por A Fazer, Em Andamento e Concluído, com etapas internas de acompanhamento.
6. **Assinatura:** Pro e Equipe abrem o checkout PIX de simulação; a confirmação do pagamento simulado ativa o plano.
7. **Configuração:** permite editar perfil, senha, avatar e consultar os recursos do plano atual.
8. **Notificações:** o sino apresenta avisos da própria conta e registra os itens visualizados.
9. **Administração:** administradores consultam métricas, buscam usuários e gerenciam assinaturas e planos.

## Planos e permissões

Novas contas Free recebem **sete dias de acesso a todos os recursos de Pro e Equipe**, contados da criação da conta. O teste não concede acesso administrativo. Após esse período, a conta continua funcionando no Free, com recursos pagos bloqueados e dados preservados.

| Funcionalidade | Free após o teste | Escopo Pro | Equipe |
| --- | --- | --- | --- |
| Projetos, membros, tarefas, busca e checklist | Sim | Sim | Sim |
| Prioridades e prazos | Não | Sim | Sim |
| Relatório do projeto | Não | Sim | Sim |
| Papéis avançados de membros | Não | Não | Sim |
| Histórico de tarefas e relatório completo | Não | Não | Sim |
| Recurso de suporte da equipe | Não | Não | Sim |

Por padrão, projetos e membros são ilimitados; a administração pode configurar cotas. Em projetos compartilhados, as funcionalidades seguem o plano do proprietário, e o papel do participante define suas ações. As verificações são feitas no backend, além dos cadeados e controles desabilitados na interface.

O resumo **Seu plano** fica em **Configuração**. O card lateral **Acesse nosso site** aparece somente para contas comuns Free. O perfil **Admin** é independente do plano comercial.

## CRUD no projeto

CRUD significa **Create, Read, Update e Delete**: criar, consultar, atualizar e excluir dados.

| Entidade | Criar | Consultar | Atualizar | Excluir |
| --- | --- | --- | --- | --- |
| Projetos | Criar projeto | Listar projetos acessíveis | Editar e arquivar | Excluir projeto |
| Tarefas | Criar tarefa | Consultar quadro e cartões | Editar dados e status | Excluir tarefa |
| Membros | Adicionar participante | Listar membros | Alterar papel autorizado | Remover participante |
| Checklist | Criar item | Listar itens da tarefa | Marcar conclusão | Excluir item |
| Usuário | Cadastrar conta | Consultar perfil | Editar dados, senha e avatar | Excluir a própria conta |
| Planos | Criar como administrador | Consultar catálogo | Editar preços e limites | Excluir quando não utilizado |

## Banco de dados

O banco atual utiliza **SQLite**, preparado por migrations e seeders Laravel.

| Tabelas | Responsabilidade |
| --- | --- |
| `users` | Contas, perfil, confirmação de e-mail e identificação administrativa. |
| `plans`, `subscriptions` | Catálogo de planos, assinatura, status e validade. |
| `pix_payments` | Pedidos PIX simulados, valores e confirmação. |
| `projects`, `project_members` | Projetos, proprietários, participantes e papéis. |
| `tasks`, `checklist_items` | Tarefas, status, prioridade, prazo e checklist. |
| `task_events` | Histórico de alterações de tarefas. |
| `notifications` | Avisos internos e registro de leitura. |
| `usage_metrics` | Contadores de uso, logins e última atividade. |
| `sessions`, `password_reset_tokens` | Sessões autenticadas e recuperação de senha. |

Os dados do MySQL da versão original não são importados automaticamente. O banco local não é enviado ao GitHub.

## Autenticação e segurança

- Senhas armazenadas com hashing pelo Laravel.
- Sessão regenerada no login e invalidada no logout.
- Confirmação de e-mail por link assinado e recuperação por token temporário.
- Proteção CSRF, validação no servidor e limites de requisições.
- Middleware para áreas autenticadas, contas confirmadas e administração.
- Policies para impedir acesso indevido a projetos e tarefas de outras contas.
- Funcionalidades pagas condicionadas à validade da assinatura ou do teste Free.
- Valores do checkout calculados no servidor e ativação sem duplicação.
- Registro da versão dos termos e do aviso de privacidade aceitos.

A confirmação de e-mail verifica o endereço cadastrado; não há segundo fator por aplicativo autenticador implementado.

## Organização visual e experiência do usuário

- Site com identidade visual do Escopo Fácil, navegação entre seções e animações de entrada padronizadas.
- Dashboard com indicadores e gráficos, sem o painel detalhado do plano ocupando a área principal.
- Sidebar com navegação, identificação do plano e rótulo administrativo discreto.
- Kanban com cartões, modais de edição e feedback por mensagens/toasts.
- Cadeados cinza para recursos indisponíveis após o teste Free.
- Notificações com bolinha vermelha somente enquanto houver itens não visualizados.
- Administração com métricas, tabelas e busca por nome ou e-mail na Visão Geral e em Gerenciar Assinaturas.

## Como executar localmente

Requisitos: **PHP 8.2+**, Composer, extensão PDO SQLite e **Node.js 20.19+ ou 22.12+**, com npm.

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

Configure `APP_URL=http://127.0.0.1:8000` no `.env` e acesse esse endereço. Para trabalhar no frontend, execute `npm run dev` em outro terminal, mantendo o servidor PHP ativo.

O envio de confirmação e recuperação de senha depende das configurações SMTP no `.env`. O HTML do e-mail está em `resources/views/emails/auth.blade.php`; os textos e assuntos são definidos em `app/Providers/AppServiceProvider.php`. Consulte o [guia de administração e e-mail](docs/sistema-admin-email.md).

### Contas de demonstração

```sh
php artisan dev:plan-demos
php artisan dev:create-admin
php artisan dev:notification
```

As contas demonstrativas possuem projetos e tarefas de exemplo. Os logins e o roteiro de comparação estão em [contas de demonstração](docs/contas-demonstracao.md). O administrador usa `DEV_ADMIN_EMAIL` e `DEV_ADMIN_PASSWORD`; sem senha configurada, o comando gera uma aleatória. Esses comandos são destinados a ambientes local/testing.

O `.env`, as senhas reais, o banco local e as dependências instaladas não são versionados.

## Testes

```sh
php artisan test
npm run build
```

A execução registrada em **24/10/2026** teve **117 testes e 719 asserções aprovados**, cobrindo autenticação, planos, permissões, checkout, administração, busca de assinantes e notificações. Consulte a documentação para o escopo das validações realizadas.

## Objetivo acadêmico

O projeto foi desenvolvido para aplicar conhecimentos de organização de código, desenvolvimento web, banco relacional, CRUD, autenticação, autorização, experiência visual, testes automatizados e colaboração com Git/GitHub.

O checkout opera em **simulação acadêmica, sem cobrança real**, restrita aos ambientes local/testing. Os ícones sociais ainda não possuem links e o WhatsApp utiliza contato demonstrativo.

## Documentação complementar

| Documento | Conteúdo |
| --- | --- |
| [Projeto](docs/projeto.md) | Visão geral, arquitetura, tecnologias, instalação e limites atuais. |
| [Backend e planos](docs/backend-planos.md) | Regras de acesso e funcionalidades por plano. |
| [Autenticação](docs/autenticacao.md) | Fluxos de conta, modais e verificação. |
| [Pagamentos PIX](docs/pagamentos-pix.md) | Checkout e ativação de assinaturas em simulação. |
| [Administração e e-mail](docs/sistema-admin-email.md) | Painel administrativo, SMTP e personalização de mensagens. |
| [Hospedagem](docs/hospedagem.md) | Preparação de produção, domínio, SMTP institucional e validação. |
| [Contas de demonstração](docs/contas-demonstracao.md) | Contas e roteiro de comparação dos planos. |
| [Documentos legais](docs/documentos-legais.md) | Termos, privacidade e aceite. |

## Próximas evoluções

- Conectar eventos do sistema às notificações, como convites e prazos próximos.
- Implementar convites de membros por e-mail.
- Preparar pagamentos de produção, com webhook e tratamento de renovação/cancelamento.
- Configurar canais reais de suporte e links das redes sociais.
- Avaliar atualização em tempo real de notificações e presença.

## Equipe

**Natan Oliveira, Guilherme Augusto e Nicollas Ramos.**

---

<p align="center">
  <strong>Feito com amor, café e muitas iterações.</strong> ❤️
</p>
