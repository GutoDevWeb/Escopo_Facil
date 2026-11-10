# Notificações de projetos

- Convites (adição de uma conta confirmada): aviso no sistema e e-mail com o nome de quem convidou e do projeto. O acesso é imediato, sem uma segunda etapa de aceite.
- Alterações de dados do projeto, tarefas, status, prioridades, prazos, checklist, membros e permissões: avisos no sistema aos demais participantes. A própria ação não notifica o autor.
- Remoção: a pessoa removida recebe o aviso com o nome de quem a removeu e do projeto. Não recebe atualizações posteriores nem consegue acessar o projeto.
- Exclusão: os demais participantes recebem o aviso, que continua disponível depois da exclusão. Proprietário e membros com papel Admin podem excluir; membros comuns, leitores e administradores globais sem participação não podem.
- A bolinha de cada projeto é independente da leitura no sino. Abrir o quadro de tarefas marca as atualizações existentes como vistas apenas para aquela conta. Novas atualizações voltam a acender o indicador. O sistema verifica novidades a cada minuto enquanto está aberto e ao retornar à aba.

## Prazos e execução em produção

As tarefas têm uma data, sem horário. O vencimento é considerado às 23h59 no horário de Brasília (`America/Sao_Paulo`). O aviso é gerado na janela das últimas 24 horas, uma vez por tarefa, data e participante. Tarefas concluídas, sem prazo, vencidas e projetos arquivados são ignorados. Uma mudança de data permite um novo aviso para a nova data.

O comando `php artisan notifications:deliver` verifica prazos e reenvia e-mails pendentes. Ele está incluído no agendador horário do Laravel e na rota privada `/internal/maintenance` já chamada pelo cron da Vercel. O cron atual da Vercel é diário; por isso o aviso não é garantido exatamente 24 horas antes do vencimento. Contas ativas também verificam os seus prazos ao carregar notificações; criação/alteração de tarefas e entrada no projeto verificam os prazos correspondentes imediatamente. Para verificações horárias sem usuários ativos, execute o agendador Laravel a cada minuto em um serviço que suporte essa frequência.

Falhas de SMTP preservam a participação e o aviso no sistema. O envio fica pendente no banco, disponível para nova tentativa após 15 minutos pelo comando acima. Convites já enviados não são reenviados; convites pendentes de projetos sem acesso não são enviados. Os logs registram o identificador do aviso e o tipo da exceção, sem senha SMTP nem conteúdo privado.

## Publicação

Execute `php artisan migrate --force` no banco usado pelo site para adicionar os campos de acompanhamento à tabela `notifications`. Mantenha o SMTP e `APP_URL` de produção configurados, além do `CRON_SECRET` para a rota privada de manutenção. Nenhuma nova chave de API é necessária.
