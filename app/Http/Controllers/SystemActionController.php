<?php

namespace App\Http\Controllers;

use App\Models\{Project, Task, User, ChecklistItem};
use App\Services\PlanAccess;
use App\Support\SystemAvatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Gate, Hash};
use Illuminate\Validation\{Rule, Rules\Password};

/** Adapts the original UI payloads to the same authorized workspace operations. */
class SystemActionController extends Controller
{
    public function __construct(private WorkspaceController $workspace, private PlanAccess $access) {}

    public function read(Request $request, string $group, string $action)
    {
        if ($group === 'membros' && $action === 'buscar') {
            $p = Project::findOrFail($request->integer('projeto_id'));
            Gate::authorize('manageMembers', $p);
            $data = $request->validate(['q' => 'required|string|min:2|max:100']);
            $users = User::whereNotNull('email_verified_at')->whereNotIn('id', $p->members()->pluck('users.id')->push($p->user_id))
                ->where(fn ($q) => $q->where('name', 'like', '%'.$data['q'].'%')->orWhere('email', 'like', '%'.$data['q'].'%'))->limit(10)->get();
            return response()->json($users->map(fn ($u) => ['id' => $u->id, 'nome' => $u->name, 'email' => $u->email, 'avatar' => $u->avatar ?? '']));
        }
        if ($group === 'checklist' && $action === 'listar') {
            $t = Task::findOrFail($request->integer('tarefa_id'));
            Gate::authorize('view', $t->project);
            return response()->json(['success' => true, 'items' => $t->checklistItems->map(fn ($i) => $this->item($i))]);
        }
        abort(404);
    }

    private function item(ChecklistItem $item): array
    {
        return ['id' => $item->id, 'texto' => $item->title, 'concluido' => (int) $item->completed];
    }

    public function write(Request $request, string $group, string $action)
    {
        $accept = $request->headers->get('Accept');
        try { return $this->dispatch($request, $group, $action); }
        finally {
            if ($accept === null) $request->headers->remove('Accept');
            else $request->headers->set('Accept', $accept);
        }
    }

    private function dispatch(Request $request, string $group, string $action)
    {
        if ($group === 'usuario') return $this->profile($request, $action);
        $this->access->require($request->user(), 'projects');
        $ajax = $request->expectsJson();
        $request->headers->set('Accept', 'application/json');
        $response = null;
        if ($group === 'projetos') {
            $request->merge(['title' => $request->input('titulo'), 'description' => $request->input('descricao')]);
            $response = match ($action) {
                'criar' => $this->workspace->store($request),
                'editar' => $this->workspace->update($request, Project::findOrFail($request->integer('projeto_id'))),
                'excluir' => $this->workspace->destroy($request, Project::findOrFail($request->integer('projeto_id'))),
                default => abort(404),
            };
            if (!$ajax) return redirect()->route('projects.index')->with('status', 'saved');
        } elseif ($group === 'membros') {
            $p = Project::findOrFail($request->integer('projeto_id'));
            if ($action === 'adicionar') {
                $response = $this->workspace->addMember($request, $p);
            } else {
                $u = User::findOrFail($request->integer('membro_id'));
                if ($action === 'remover' && $u->id === $request->user()->id && $u->id !== $p->user_id) {
                    Gate::authorize('view', $p);
                    $p->members()->detach($u->id);
                    $response = response()->noContent();
                } elseif ($action === 'remover') {
                    $response = $this->workspace->removeMember($request, $p, $u);
                } elseif ($action === 'alterar-role') {
                    $request->validate(['role' => ['required', Rule::in(['admin', 'membro', 'viewer'])]]);
                    $request->merge(['role' => ['admin' => 'manager', 'membro' => 'member', 'viewer' => 'viewer'][$request->input('role')]]);
                    $response = $this->workspace->updateMember($request, $p, $u);
                } else abort(404);
            }
        } elseif ($group === 'tarefas' || $group === 'checklist') {
            $t = $request->filled('tarefa_id') ? Task::findOrFail($request->integer('tarefa_id')) : null;
            $p = $t?->project ?? Project::findOrFail($request->integer('projeto_id'));
            if ($t && $request->filled('projeto_id')) abort_unless($p->id === $request->integer('projeto_id'), 404);
            if ($group === 'tarefas') {
                if (in_array($action, ['criar', 'editar'], true)) {
                    $request->validate(['status' => ['required', Rule::in([1, 2, 3])], 'prioridade' => ['sometimes', Rule::in([1, 2, 3])]]);
                    $data = ['title' => $request->input('titulo'), 'description' => $request->input('descricao'),
                        'status' => [1 => 'todo', 2 => 'doing', 3 => 'done'][$request->integer('status')]];
                    $priority = [1 => 'low', 2 => 'medium', 3 => 'high'][$request->integer('prioridade', 1)];
                    // The old form always sends its defaults. Ignore unchanged locked fields,
                    // while an actual attempt to change them still reaches the plan check.
                    if ($this->access->allows($p->owner, 'priorities') || $priority !== ($t?->priority ?? 'low')) $data['priority'] = $priority;
                    $date = $request->input('prazo') ?: null;
                    if ($this->access->allows($p->owner, 'deadlines') || $date !== $t?->due_date?->format('Y-m-d')) {
                        if (!$this->access->allows($p->owner, 'deadlines')) $this->access->require($p->owner, 'deadlines');
                        $data['due_date'] = $date;
                    }
                    $request->merge($data);
                }
                $response = match ($action) {
                    'criar' => $this->workspace->createTask($request, $p),
                    'editar' => $this->workspace->updateTask($request, $p, $t ?? abort(404)),
                    'deletar', 'excluir' => $this->workspace->deleteTask($request, $p, $t ?? abort(404)),
                    default => abort(404),
                };
            } else {
                abort_unless($t, 404);
                if ($action === 'criar') {
                    $request->merge(['title' => $request->input('texto')]);
                    $response = $this->workspace->createChecklist($request, $p, $t);
                    return response()->json(['success' => true, 'item' => $this->item(ChecklistItem::findOrFail($response->getData()->id))]);
                }
                $i = ChecklistItem::findOrFail($request->integer('item_id'));
                if ($action === 'toggle') {
                    $request->merge(['completed' => !$i->completed]);
                    $this->workspace->updateChecklist($request, $p, $t, $i);
                    return response()->json(['success' => true, 'concluido' => (int) $i->completed]);
                }
                if ($action !== 'deletar') abort(404);
                $response = $this->workspace->deleteChecklist($request, $p, $t, $i);
            }
        } else abort(404);
        return response()->json(['success' => true, 'ok' => true, 'data' => $response instanceof \Illuminate\Http\JsonResponse ? $response->getData() : null]);
    }

    private function profile(Request $request, string $action)
    {
        $u = $request->user();
        if ($action === 'atualizar') {
            $data = $request->validate(['nome' => 'required|string|max:255', 'cargo' => 'nullable|string|max:100',
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($u->id)]]);
            $u->name = $data['nome']; $u->job_role = $data['cargo']; $u->email = mb_strtolower($data['email']);
            if ($u->isDirty('email')) $u->email_verified_at = null;
        } elseif ($action === 'alterar-senha') {
            $data = $request->validate(['senha_atual' => 'required|current_password',
                'nova_senha' => ['required', Password::defaults(), 'same:confirmar_nova_senha']]);
            $u->password = Hash::make($data['nova_senha']);
        } elseif ($action === 'atualizar-avatar') {
            $data = $request->validate(['avatar' => 'required|string|max:255']);
            $a = SystemAvatar::parseAvatar($data['avatar']);
            abort_unless($a['color'].':'.$a['icon'] === $data['avatar'], 422, 'Avatar inválido.');
            $u->avatar = $data['avatar'];
        } else abort(404);
        $u->save();
        if (!$u->hasVerifiedEmail()) {
            $u->sendEmailVerificationNotification();
            return redirect()->route('verification.notice');
        }
        return redirect()->route('profile.edit')->with('status', 'saved');
    }
}
