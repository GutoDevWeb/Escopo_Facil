<?php

namespace App\Console\Commands;

use App\Models\{Plan, User, Subscription};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreatePlanDemos extends Command
{
    protected $signature = 'dev:plan-demos';
    protected $description = 'Cria exemplos persistentes para comparar Free, Pro e Equipe em desenvolvimento.';
    public const PASSWORD = 'EscopoDemo!2026';

    public function handle(): int
    {
        if (!app()->environment('local', 'testing')) { $this->error('Disponível somente em desenvolvimento.'); return self::FAILURE; }
        if (Plan::whereIn('slug', ['free', 'pro', 'equipe'])->count() !== 3) { $this->error('Cadastre primeiro os planos Free, Pro e Equipe.'); return self::FAILURE; }
        // Refuse to adopt an existing real account, even if it uses a test address.
        foreach (['free', 'pro', 'equipe', 'gestor', 'leitor'] as $key) {
            $existing = User::where('email', "demo.{$key}@escopofacil.test")->first();
            if ($existing && !$existing->is_demo) { $this->error('Já existe uma conta não demonstrativa com um dos endereços reservados. Nenhum dado foi alterado.'); return self::FAILURE; }
        }
        DB::transaction(function () {
            $accounts = [];
            foreach (['free' => 'Free', 'pro' => 'Escopo Pro', 'equipe' => 'Equipe', 'gestor' => 'Gestor convidado', 'leitor' => 'Leitor convidado'] as $key => $label) {
                $u = User::firstOrNew(['email' => "demo.{$key}@escopofacil.test"]);
                if (!$u->exists) {
                    $u->fill(['name' => 'Demo '.$label, 'password' => self::PASSWORD, 'job_role' => 'dev-fullstack', 'plan_id' => Plan::where('slug', 'free')->value('id')]);
                    $u->forceFill(['is_demo' => true, 'email_verified_at' => now()])->save();
                }
                $accounts[$key] = $u;
            }
            foreach (['free', 'pro', 'equipe'] as $tier) {
                $u = $accounts[$tier]; $plan = Plan::where('slug', $tier)->firstOrFail();
                if ($tier !== 'free' && !$u->subscriptions()->where('plan_id', $plan->id)->where('status', 'active')->where('expires_at', '>', now())->exists()) {
                    $u->subscriptions()->where('status', 'active')->update(['status' => 'replaced']);
                    Subscription::create(['user_id' => $u->id, 'plan_id' => $plan->id, 'status' => 'active', 'expires_at' => now()->addYear()]);
                }
                if ($tier === 'free') $u->subscriptions()->where('status', 'active')->update(['status' => 'replaced']);
                $u->update(['plan_id' => $plan->id]);
                $title = ['free' => 'Exemplo Free — Organize suas primeiras tarefas', 'pro' => 'Exemplo Pro — Prioridades, prazos e relatório', 'equipe' => 'Exemplo Equipe — Permissões e histórico'][$tier];
                if ($u->projects()->where('title', $title)->exists()) continue;
                $project = $u->projects()->create(['title' => $title, 'description' => 'Projeto de demonstração. Abra as tarefas e confira o quadro de recursos do plano. Os dados são editáveis para você testar.']);
                foreach ([['todo', 'Planejar a próxima entrega'], ['doing', 'Preparar a apresentação'], ['done', 'Revisar o escopo inicial']] as $index => [$status, $taskTitle]) {
                    $task = $project->tasks()->make(['title' => $taskTitle,
                        'description' => $tier === 'free' ? 'Experimente mover o cartão e marcar o checklist. Prioridade, prazo e relatórios exigem Pro.' : ($tier === 'pro' ? 'Abra este cartão para editar a prioridade e o prazo. Consulte também o relatório do projeto.' : 'Edite o cartão e consulte o histórico. Em Projetos > Gerenciar, confira os membros Gestor e Leitor.'),
                        'status' => $status, 'priority' => $tier === 'free' ? 'low' : ['high', 'medium', 'low'][$index],
                        'due_date' => $tier === 'free' ? null : today()->addDays($index + 2)->toDateString()]);
                    $task->user_id = $u->id; $task->save();
                    $task->checklistItems()->createMany([['title' => 'Conferir os requisitos', 'completed' => true], ['title' => 'Validar a entrega', 'completed' => $status === 'done']]);
                    $project->events()->create(['task_id' => $task->id, 'user_id' => $u->id, 'action' => 'created', 'changes' => ['title' => $task->title, 'status' => $status]]);
                }
                if ($tier === 'equipe') {
                    $project->members()->attach($accounts['gestor']->id, ['role' => 'manager']);
                    $project->members()->attach($accounts['leitor']->id, ['role' => 'viewer']);
                }
            }
        });
        $this->info('Demonstrações criadas. Reexecutar não duplica projetos nem redefine senhas.');
        $this->table(['Plano', 'Login'], [['Free', 'demo.free@escopofacil.test'], ['Pro', 'demo.pro@escopofacil.test'], ['Equipe', 'demo.equipe@escopofacil.test']]);
        $this->line('Senha inicial das novas contas: '.self::PASSWORD);
        return self::SUCCESS;
    }
}
