# Comparação prática dos planos

As contas abaixo já estão criadas no banco local. Entre pelo login normal do site. Todas usam a senha inicial **`EscopoDemo!2026`** e já têm o e-mail confirmado. Os exemplos não representam pagamentos reais.

| Plano | E-mail | Exemplo para conferir |
| --- | --- | --- |
| Free | `demo.free@escopofacil.test` | Projeto com três tarefas e checklist; prioridade, prazo, relatório e histórico bloqueados. |
| Escopo Pro | `demo.pro@escopofacil.test` | Tarefas com prioridades e prazos; botão de relatório disponível; funções de equipe e histórico bloqueados. |
| Equipe | `demo.equipe@escopofacil.test` | Prioridades, prazos, relatório completo, histórico e membros Gestor e Leitor. |

## Roteiro

1. Entre em cada conta e confira o painel **Recursos do seu plano** no dashboard.
2. Abra **Projetos → Ver tarefas** no projeto de exemplo. O painel mostra os recursos do plano do proprietário desse projeto.
3. No Free, abra um cartão: título, descrição, status e checklist estão disponíveis; prioridade e prazo não aparecem. As rotas de relatório/histórico retornam 403 mesmo se acessadas diretamente.
4. No Pro, abra um cartão e altere prioridade ou prazo. Consulte **Relatório do projeto**. O histórico e as funções avançadas de membros continuam bloqueados.
5. No Equipe, altere um cartão e abra **Histórico de tarefas**. Consulte o relatório completo, que inclui prioridades e atrasos. Em **Projetos → Gerenciar**, confira os membros identificados como Gestor e Leitor.

Também estão disponíveis `demo.gestor@escopofacil.test` e `demo.leitor@escopofacil.test`, com a mesma senha inicial. O Gestor pode gerenciar o projeto de Equipe dentro dos limites do seu papel. O Leitor pode consultar os cartões e checklists; a interface informa **somente leitura** e o backend recusa edições. Essas contas têm plano pessoal Free e participam do projeto do proprietário Equipe: o recurso compartilhado segue o plano do proprietário, e o papel de cada participante limita suas ações.

Nenhuma conta demonstrativa é administradora. O Free continua disponível após os sete dias iniciais. Todos os planos permitem projetos ilimitados. As assinaturas demonstrativas Pro/Equipe têm validade de um ano a partir da criação; não há PIX ou venda fictícia registrada para criá-las.

## Dados e manutenção

Projetos e tarefas são persistentes e editáveis. Para recriar dados ausentes, há o comando local:

```sh
php artisan dev:plan-demos
```

Ele não duplica os projetos existentes, não desfaz edições de tarefas e não redefine senhas existentes. É bloqueado em produção. As contas são identificadas por `users.is_demo`; uma conta real que coincida com um endereço reservado não é adotada nem modificada pelo comando.

`PlanDemoTest` valida as permissões em cada conta, os bloqueios HTTP 403, a consulta de recursos, os exemplos persistentes, o leitor sem edição e a restrição do comando ao desenvolvimento. `PlanAuthorizationTest` cobre também expiração, manipulação de IDs, planos pagos sem assinatura válida e controle de papéis.
