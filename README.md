# Escopo Fácil

<p align="center">
  <img src="public/system/assets/images/LogoV2.png" alt="Logo do Escopo Fácil" width="280">
</p>

<p align="center"><strong>Organize projetos, acompanhe tarefas e mantenha sua equipe alinhada.</strong></p>

<p align="center"><a href="https://www.escopofacil.com.br/">https://www.escopofacil.com.br</a></p>

## Sobre o projeto

O **Escopo Fácil** é uma plataforma web acadêmica para organizar projetos, tarefas e equipes. O projeto foi desenvolvido no curso de **Tecnologia em Sistemas para Internet do Centro Universitário Senac** por Natan Oliveira, Guilherme Augusto e Nicollas Ramos.

A aplicação combina uma landing page responsiva, autenticação de usuários, um workspace de projetos e um painel administrativo. O foco é transformar o acompanhamento de entregas em uma experiência simples, visual e objetiva.

> O ambiente publicado é demonstrativo. O checkout PIX serve para simulação acadêmica e não realiza cobranças reais.

## O que você pode fazer

- Criar projetos e convidar membros com papéis e permissões.
- Organizar tarefas em um quadro Kanban: **A Fazer**, **Em Andamento** e **Concluído**.
- Definir prioridade, prazo, descrição e checklist para cada tarefa.
- Consultar dashboard, relatórios, histórico e atividades recentes.
- Receber notificações sobre convites e atualizações dos projetos.
- Gerenciar perfil, avatar, confirmação de e-mail e recuperação de senha.
- Usar temas claro e escuro em uma interface responsiva para computador e celular.
- Instalar o atalho do sistema na tela inicial do celular, quando o navegador oferecer esse recurso.
- Administrar usuários, assinaturas e planos pelo painel restrito de administração.

## Tecnologias

| Camada | Tecnologias |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12, Eloquent, Blade |
| Frontend | HTML, CSS, JavaScript, React e Motion |
| Build | Vite, npm e Laravel Vite Plugin |
| Banco | PostgreSQL em hospedagem; SQLite para desenvolvimento e testes |
| E-mail | Laravel Mail e SMTP |
| Pagamentos | AbacatePay em modo de desenvolvimento e PIX local simulado |
| Qualidade | PHPUnit, testes JavaScript, Laravel Pint e GitHub Actions |
| Deploy | Docker, Vercel e Neon |

## Estrutura do projeto

- `app/`: controllers, models, policies, serviços, notificações e componentes PHP.
- `resources/views/`: páginas Blade, layouts, e-mails e modais.
- `resources/js/`: componentes React e scripts do frontend.
- `public/system/assets/`: estilos, scripts e imagens do workspace.
- `database/migrations/`: estrutura do banco e índices.
- `tests/`: testes automatizados de feature e unidade.
- `docs/`: documentação técnica, deploy, autenticação, pagamentos e mobile.

## Documentação complementar

- [Visão técnica e execução local](docs/projeto.md)
- [Deploy na Vercel](docs/deploy-vercel.md)
- [Hospedagem e configuração](docs/hospedagem.md)
- [Autenticação](docs/autenticacao.md)
- [Notificações de projetos](docs/notificacoes-projetos.md)
- [Pagamentos PIX demonstrativos](docs/pagamentos-pix.md)
- [Mobile e instalação do atalho](docs/mobile-e-atalho.md)
- [Contas de demonstração](docs/contas-demonstracao.md)
- [Documentos legais](docs/documentos-legais.md)

## Segurança e limites conhecidos

A aplicação usa validação no servidor, proteção CSRF, policies por projeto, sessões Laravel, hashing de senhas e controle de acesso por plano. Mesmo assim, as credenciais devem permanecer somente nas variáveis privadas do ambiente.

O projeto ainda possui limites próprios de uma demonstração acadêmica: pagamentos reais, cobrança recorrente, webhooks de produção, suporte externo em tempo real e autenticação por segundo fator não fazem parte do escopo atual.

## Equipe

**Natan Oliveira, Guilherme Augusto e Nicollas Ramos.**

---

<p align="center"><em>Feito com amor e muitas iterações. ❤️</em></p>
