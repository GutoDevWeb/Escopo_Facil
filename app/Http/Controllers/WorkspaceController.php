<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\PlanAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function __construct(private PlanAccess $access) {}

    public function entitlements(Request $request)
    {
        return response()->json($this->access->summary($request->user()))->header('Cache-Control', 'no-store, private');
    }

    public function teamSupport(Request $request)
    {
        $this->access->require($request->user(), 'team_support');
        return response()->json(['email' => config('legal.support_email'), 'hours' => '8h às 18h']);
    }

    public function index(Request $request)
    {
        if (!$request->expectsJson()) return app(SystemController::class)->projects($request);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:200']]);
        $query = Project::visibleTo($request->user())->withCount('tasks')->latest('id');
        if ($data['search'] ?? null) $query->where('title', 'like', '%'.$data['search'].'%');
        $projects = $query->paginate(20)->withQueryString();
        return $request->expectsJson() ? response()->json($projects) : view('workspace.index', [
            'projects' => $projects, 'access' => $this->access->summary($request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string', 'max:10000']]);
        $project = DB::transaction(function () use ($request, $data) {
            $owner = User::lockForUpdate()->findOrFail($request->user()->id);
            $this->access->require($owner, 'projects');
            $limit = $this->access->limit($owner, 'projects');
            if ($limit !== null && $owner->projects()->count() >= $limit) {
                throw ValidationException::withMessages(['plan' => 'Limite de projetos do plano atingido.']);
            }
            return $owner->projects()->create($data);
        });
        return $request->expectsJson() ? response()->json($project, 201) : redirect()->route('projects.show', $project);
    }

    public function show(Request $request, Project $project)
    {
        if (!$request->expectsJson()) return app(SystemController::class)->tasks($request, $project);
        Gate::authorize('view', $project);
        $project->load(['members' => fn ($q) => $q->select('users.id', 'users.name', 'users.email')]);
        $project->loadCount('tasks');
        return $request->expectsJson() ? response()->json($project) : view('workspace.show', [
            'project' => $project, 'access' => $this->access->summary($project->owner),
            'tasks' => $project->tasks()->with('checklistItems')->latest('id')->get(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        Gate::authorize('update', $project);
        $data = $request->validate(['title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'], 'archived' => ['sometimes', 'boolean']]);
        $this->access->require($project->owner, 'projects');
        $project->update($data);
        return $request->expectsJson() ? response()->json($project) : redirect()->route('projects.show', $project);
    }

    public function destroy(Request $request, Project $project)
    {
        Gate::authorize('delete', $project);
        $project->delete();
        return $request->expectsJson() ? response()->noContent() : redirect()->route('projects.index');
    }

    private function writable(Project $project): void
    {
        Gate::authorize('editTasks', $project);
        $this->access->require($project->owner, 'tasks');
        abort_if($project->archived, 409, 'Desarquive o projeto antes de fazer alterações.');
    }

    private function taskData(Request $request, Project $project, bool $creating): array
    {
        $data = $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'status' => ['sometimes', Rule::in(['todo', 'doing', 'done'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);
        if (array_key_exists('priority', $data)) $this->access->require($project->owner, 'priorities');
        if (array_key_exists('due_date', $data)) $this->access->require($project->owner, 'deadlines');
        return $data;
    }

    private function belongs(Project $project, Task $task): void { abort_unless($task->project_id === $project->id, 404); }

    private function event(Project $project, ?Task $task, Request $request, string $action, array $changes): void
    {
        $project->events()->create(['task_id' => $task?->id, 'user_id' => $request->user()->id, 'action' => $action, 'changes' => $changes]);
    }

    public function tasks(Request $request, Project $project)
    {
        Gate::authorize('view', $project);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::in(['todo', 'doing', 'done'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])]]);
        $query = $project->tasks()->with('checklistItems')->latest('id');
        if ($data['search'] ?? null) $query->where('title', 'like', '%'.$data['search'].'%');
        if ($data['status'] ?? null) $query->where('status', $data['status']);
        if ($data['priority'] ?? null) {
            $this->access->require($project->owner, 'priorities');
            $query->where('priority', $data['priority']);
        }
        return response()->json($query->paginate(50));
    }

    public function createTask(Request $request, Project $project)
    {
        $this->writable($project);
        $data = $this->taskData($request, $project, true);
        $task = DB::transaction(function () use ($request, $project, $data) {
            $task = $project->tasks()->make($data);
            $task->user_id = $request->user()->id;
            $task->save();
            $this->event($project, $task, $request, 'created', $task->only(['title', 'status', 'priority', 'due_date']));
            return $task;
        });
        return $request->expectsJson() ? response()->json($task, 201) : redirect()->route('projects.show', $project);
    }

    public function updateTask(Request $request, Project $project, Task $task)
    {
        $this->belongs($project, $task);
        $this->writable($project);
        $data = $this->taskData($request, $project, false);
        DB::transaction(function () use ($request, $project, $task, $data) {
            $before = $task->only(array_keys($data));
            $task->update($data);
            $this->event($project, $task, $request, 'updated', ['before' => $before, 'after' => $data]);
        });
        return $request->expectsJson() ? response()->json($task) : redirect()->route('projects.show', $project);
    }

    public function deleteTask(Request $request, Project $project, Task $task)
    {
        $this->belongs($project, $task);
        $this->writable($project);
        DB::transaction(function () use ($request, $project, $task) {
            $this->event($project, null, $request, 'deleted', ['task_id' => $task->id, 'title' => $task->title]);
            $task->delete();
        });
        return $request->expectsJson() ? response()->noContent() : redirect()->route('projects.show', $project);
    }

    public function addMember(Request $request, Project $project)
    {
        Gate::authorize('manageMembers', $project);
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'role' => ['sometimes', Rule::in(['member', 'manager', 'viewer'])]]);
        $role = $data['role'] ?? 'member';
        if ($role !== 'member') $this->access->require($project->owner, 'roles');
        abort_if($role === 'manager' && $project->user_id !== $request->user()->id, 403);
        $member = User::where('email', mb_strtolower(trim($data['email'])))->whereNotNull('email_verified_at')->first();
        if (! $member) throw ValidationException::withMessages(['email' => 'Informe uma conta cadastrada com e-mail confirmado.']);
        DB::transaction(function () use ($project, $member, $role) {
            $owner = User::lockForUpdate()->findOrFail($project->user_id);
            $this->access->require($owner, 'members');
            if ($member->id === $owner->id || $project->members()->where('users.id', $member->id)->exists()) {
                throw ValidationException::withMessages(['email' => 'Esta pessoa já participa do projeto.']);
            }
            $limit = $this->access->limit($owner, 'members');
            if ($limit !== null && $project->members()->count() + 1 >= $limit) {
                throw ValidationException::withMessages(['plan' => 'Limite de membros do plano atingido (incluindo o proprietário).']);
            }
            $project->members()->attach($member->id, ['role' => $role]);
        });
        return $request->expectsJson() ? response()->json(['message' => 'Membro adicionado.'], 201) : redirect()->route('projects.show', $project);
    }

    public function updateMember(Request $request, Project $project, User $member)
    {
        Gate::authorize('manageMembers', $project);
        $this->access->require($project->owner, 'roles');
        $data = $request->validate(['role' => ['required', Rule::in(['member', 'manager', 'viewer'])]]);
        abort_unless($member->id !== $project->user_id && $project->members()->where('users.id', $member->id)->exists(), 404);
        // Managers can manage members, but only the owner appoints/removes managers.
        $previous = $project->members()->where('users.id', $member->id)->first()->pivot->role;
        abort_if($project->user_id !== $request->user()->id && ($data['role'] === 'manager' || $previous === 'manager'), 403);
        $project->members()->updateExistingPivot($member->id, $data);
        return $request->expectsJson() ? response()->json(['message' => 'Permissão atualizada.']) : redirect()->route('projects.show', $project);
    }

    public function removeMember(Request $request, Project $project, User $member)
    {
        Gate::authorize('manageMembers', $project);
        $existing = $project->members()->where('users.id', $member->id)->first();
        abort_unless($existing && $member->id !== $project->user_id, 404);
        abort_if($project->user_id !== $request->user()->id && $existing->pivot->role === 'manager', 403);
        $project->members()->detach($member->id);
        return $request->expectsJson() ? response()->noContent() : redirect()->route('projects.show', $project);
    }

    public function createChecklist(Request $request, Project $project, Task $task)
    {
        $this->belongs($project, $task);
        $this->writable($project);
        $data = $request->validate(['title' => ['required', 'string', 'max:255']]);
        $item = $task->checklistItems()->create($data);
        return $request->expectsJson() ? response()->json($item, 201) : redirect()->route('projects.show', $project);
    }

    public function updateChecklist(Request $request, Project $project, Task $task, ChecklistItem $item)
    {
        $this->belongs($project, $task);
        $this->writable($project);
        abort_unless($item->task_id === $task->id, 404);
        $item->update($request->validate(['title' => ['sometimes', 'required', 'string', 'max:255'], 'completed' => ['sometimes', 'boolean']]));
        return $request->expectsJson() ? response()->json($item) : redirect()->route('projects.show', $project);
    }

    public function deleteChecklist(Request $request, Project $project, Task $task, ChecklistItem $item)
    {
        $this->belongs($project, $task);
        $this->writable($project);
        abort_unless($item->task_id === $task->id, 404);
        $item->delete();
        return $request->expectsJson() ? response()->noContent() : redirect()->route('projects.show', $project);
    }

    public function report(Request $request, Project $project)
    {
        Gate::authorize('view', $project);
        $this->access->require($project->owner, 'reports');
        $report = ['total' => $project->tasks()->count(), 'by_status' => $project->tasks()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')];
        if ($this->access->allows($project->owner, 'full_reports')) {
            $report['by_priority'] = $project->tasks()->selectRaw('priority, count(*) as total')->groupBy('priority')->pluck('total', 'priority');
            $report['overdue'] = $project->tasks()->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count();
        }
        return $request->expectsJson() ? response()->json($report) : view('workspace.data', app(SystemController::class)->common($request, 'tarefas') + ['project' => $project, 'title' => 'Relatório do projeto', 'data' => $report]);
    }

    public function history(Request $request, Project $project)
    {
        Gate::authorize('view', $project);
        $this->access->require($project->owner, 'history');
        $events = $project->events()->latest('id')->paginate(50);
        return $request->expectsJson() ? response()->json($events) : view('workspace.data', app(SystemController::class)->common($request, 'tarefas') + ['project' => $project, 'title' => 'Histórico de tarefas', 'data' => $events->items(), 'pagination' => $events]);
    }
}
