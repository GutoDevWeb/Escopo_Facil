<?php

namespace App\Http\Controllers;

use App\Models\{Project, Task, User};
use App\Services\PlanAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemController extends Controller
{
    public function __construct(private PlanAccess $access) {}

    public function common(Request $request, string $page): array
    {
        $u = $request->user();
        return ['page' => $page, 'usuario' => $this->person($u), 'avatarStr' => $u->avatar ?? '',
            'access' => $this->access->summary($u)];
    }

    private function person(User $user): array
    {
        return ['id' => $user->id, 'nome' => $user->name, 'email' => $user->email,
            'cargo' => $user->job_role ?? '', 'role' => $user->job_role ?: 'Membro', 'avatar' => $user->avatar ?? ''];
    }

    public function dashboard(Request $request)
    {
        $projects = Project::visibleTo($request->user())->where('archived', false)->pluck('id');
        $tasks = Task::whereIn('project_id', $projects)->get();
        $counts = $tasks->countBy('status');
        $tarefasPendentes = $counts['todo'] ?? 0;
        $tarefasFazendo = $counts['doing'] ?? 0;
        $tarefasConcluidas = $counts['done'] ?? 0;
        $totalProjetos = $projects->count();
        $progresso = $tasks->count() ? (int) round(100 * $tarefasConcluidas / $tasks->count()) : 0;
        $recentes = $tasks->sortByDesc('updated_at')->take(5)->map(fn ($t) => ['titulo' => $t->title,
            'status' => array_search($t->status, [1 => 'todo', 2 => 'doing', 3 => 'done'])])->values()->all();
        $days = collect(range(6, 0))->map(fn ($d) => today()->subDays($d));
        $labelsDias = $days->map(fn ($d) => $d->format('d/m'))->toJson();
        $performanceJson = $days->map(fn ($d) => $tasks->filter(fn ($t) => $t->status === 'done' && $t->updated_at->isSameDay($d))->count())->toJson();
        return view('dashboard', $this->common($request, 'dashboard') + compact('totalProjetos', 'tarefasPendentes', 'tarefasFazendo', 'tarefasConcluidas', 'progresso', 'recentes', 'labelsDias', 'performanceJson') + [
            'statusLabel' => [1 => 'Pendente', 2 => 'Em andamento', 3 => 'Concluído'],
            'statusClass' => [1 => 'pending', 2 => 'in-progress', 3 => 'completed'],
        ]);
    }

    public function projects(Request $request)
    {
        $projetos = Project::visibleTo($request->user())->with(['owner', 'members'])->latest('id')->get()->map(function ($p) use ($request) {
            $members = collect([$this->person($p->owner) + ['role' => 'admin', 'role_label' => 'Proprietário']])->merge($p->members->map(fn ($u) => $this->person($u) + ['role' => $u->pivot->role === 'manager' ? 'admin' : 'membro', 'role_label' => ['manager' => 'Gestor', 'viewer' => 'Leitor', 'member' => 'Membro'][$u->pivot->role] ?? 'Membro']));
            return ['id' => $p->id, 'titulo' => $p->title, 'descricao' => $p->description ?? '',
                'role' => Gate::allows('update', $p) ? 'admin' : 'membro', 'criado_por' => $p->owner->name,
                'owner_id' => $p->user_id, 'can_roles' => $this->access->allows($p->owner, 'roles') && $p->user_id === $request->user()->id,
                'data_criacao' => $p->created_at->format('d/m/Y'), 'membros' => $members->values()->all(),
                'membros_extra' => max(0, $members->count() - 4)];
        })->all();
        return view('system.projects', $this->common($request, 'projetos') + compact('projetos'));
    }

    public function tasks(Request $request, Project $project)
    {
        Gate::authorize('view', $project);
        $tarefas = $project->tasks()->with('checklistItems')->latest('id')->get()->map(function ($t) {
            $creator = User::find($t->user_id);
            return ['id' => $t->id, 'titulo' => $t->title, 'descricao' => $t->description ?? '',
                'status' => array_search($t->status, [1 => 'todo', 2 => 'doing', 3 => 'done']),
                'prioridade' => array_search($t->priority, [1 => 'low', 2 => 'medium', 3 => 'high']),
                'prazo' => $t->due_date?->format('Y-m-d'), 'criado_por_nome' => $creator?->name ?? '',
                'criado_por_avatar' => $creator?->avatar ?? '', 'checklist_total' => $t->checklistItems->count(),
                'checklist_done' => $t->checklistItems->where('completed', true)->count()];
        })->all();
        return view('system.tasks', array_replace($this->common($request, 'tarefas'), [
            'access' => $this->access->summary($project->owner), 'canWrite' => Gate::allows('editTasks', $project) && !$project->archived,
            'project' => $project, 'projeto' => ['id' => $project->id, 'titulo' => $project->title],
            'projetoId' => $project->id, 'isMembro' => true, 'tarefas' => $tarefas,
            'prioridades' => [1 => ['texto' => 'Baixa', 'classe' => 'low', 'icone' => 'grafico-baixa.svg'], 2 => ['texto' => 'Média', 'classe' => 'medium', 'icone' => 'grafico-media.svg'], 3 => ['texto' => 'Alta', 'classe' => 'high', 'icone' => 'grafico-alta.svg']],
            'tarefasPorStatus' => fn ($tasks, $status) => array_filter($tasks, fn ($t) => $t['status'] === $status),
            'formatarPrazo' => fn ($date) => $date ? date('d/m/Y', strtotime($date)) : 'Sem prazo',
        ]));
    }

    public function settings(Request $request)
    {
        return view('system.settings', $this->common($request, 'configuracao') + [
            'cargoSelecionado' => fn ($value, $option) => $value === $option ? 'selected' : '',
        ]);
    }

    public function legacy(Request $request)
    {
        return match ($request->query('page', 'dashboard')) {
            'dashboard' => redirect()->route('dashboard'),
            'projetos' => redirect()->route('projects.index'),
            'tarefas' => redirect()->route('projects.show', Project::findOrFail($request->integer('projeto_id'))),
            'configuracao' => redirect()->route('profile.edit'),
            default => abort(404),
        };
    }
}
