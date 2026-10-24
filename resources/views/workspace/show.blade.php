<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $project->title }}</h2></x-slot>
    <div class="max-w-7xl mx-auto p-6 space-y-6">
        <x-workspace-errors />
        <a class="underline" href="{{ route('projects.index') }}">← Projetos</a>
        <p>{{ $project->description }}</p><p>Plano do projeto: {{ $access['name'] }} · {{ $project->archived ? 'Arquivado' : 'Ativo' }}</p>
        @can('update', $project)
            <details class="bg-white rounded p-4"><summary>Editar projeto</summary><form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-3 mt-4">@csrf @method('PATCH')<label class="block">Título <input class="block rounded w-full" name="title" value="{{ $project->title }}" required maxlength="200"></label><label class="block">Descrição <textarea class="block rounded w-full" name="description">{{ $project->description }}</textarea></label><label>Estado <select name="archived"><option value="0" @selected(!$project->archived)>Ativo</option><option value="1" @selected($project->archived)>Arquivado</option></select></label><button type="submit" class="p-2 bg-gray-800 text-white rounded">Salvar</button></form></details>
        @endcan
        <section class="bg-white rounded p-4 space-y-4"><h3 class="font-semibold">Membros</h3><p>{{ $project->owner->name }} · Proprietário</p>
            @foreach($project->members as $member)
                <div class="flex flex-wrap gap-3 items-center"><span>{{ $member->name }} · {{ $member->pivot->role }}</span>
                    @can('manageMembers', $project)
                        @if(in_array('roles', $access['features']))<form method="POST" action="{{ route('projects.members.update', [$project, $member]) }}">@csrf @method('PATCH')<label>Papel <select name="role">@foreach(['member' => 'Membro', 'manager' => 'Gerente', 'viewer' => 'Leitor'] as $value => $label)<option value="{{ $value }}" @selected($member->pivot->role === $value)>{{ $label }}</option>@endforeach</select></label><button type="submit">Salvar papel</button></form>@endif
                        <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}">@csrf @method('DELETE')<button type="submit">Remover</button></form>
                    @endcan
                </div>
            @endforeach
            @can('manageMembers', $project)<form method="POST" action="{{ route('projects.members.store', $project) }}" class="flex flex-wrap gap-3">@csrf<label>E-mail da conta confirmada <input type="email" required name="email" class="rounded"></label>@if(in_array('roles', $access['features']))<label>Papel <select name="role"><option value="member">Membro</option><option value="manager">Gerente</option><option value="viewer">Leitor</option></select></label>@endif<button type="submit">Adicionar membro</button></form>@endcan
        </section>
        @can('editTasks', $project)
            @if(!$project->archived)
                <details class="bg-white rounded p-4"><summary>Nova tarefa</summary><form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="space-y-3 mt-4">@csrf<label class="block">Título <input required maxlength="200" name="title" class="block rounded w-full"></label><label class="block">Descrição <textarea name="description" class="block rounded w-full"></textarea></label>@if(in_array('priorities', $access['features']))<label>Prioridade <select name="priority"><option value="low">Baixa</option><option value="medium">Média</option><option value="high">Alta</option></select></label>@endif @if(in_array('deadlines', $access['features']))<label>Prazo <input type="date" name="due_date"></label>@endif<button type="submit" class="bg-gray-800 text-white p-2 rounded">Criar tarefa</button></form></details>
            @endif
        @endcan
        <section class="grid gap-4 lg:grid-cols-3" aria-label="Quadro de tarefas">
            @foreach(['todo' => 'A fazer', 'doing' => 'Em andamento', 'done' => 'Concluído'] as $status => $label)
                <div class="bg-gray-200 rounded p-4 space-y-4" data-task-column="{{ $status }}"><h3 class="font-semibold">{{ $label }}</h3>
                    @foreach($tasks->where('status', $status) as $task)
                        <article class="bg-white p-4 rounded space-y-3" @can('editTasks', $project) @if(!$project->archived) draggable="true" data-task-url="{{ route('projects.tasks.update', [$project, $task]) }}" @endif @endcan>
                            <h4 class="font-semibold">{{ $task->title }}</h4><p>{{ $task->description }}</p>
                            @if(in_array('priorities', $access['features']))<p>Prioridade: {{ ['low'=>'Baixa','medium'=>'Média','high'=>'Alta'][$task->priority] }}</p>@endif
                            @if(in_array('deadlines', $access['features']))<p>Prazo: {{ $task->due_date?->format('d/m/Y') ?? 'Sem prazo' }}</p>@endif
                            @can('editTasks', $project)
                                @if(!$project->archived)
                                    <details><summary>Editar tarefa</summary><form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}" class="space-y-3">@csrf @method('PATCH')<label class="block">Título <input class="block rounded w-full" name="title" required maxlength="200" value="{{ $task->title }}"></label><label class="block">Descrição <textarea class="block rounded w-full" name="description">{{ $task->description }}</textarea></label><label>Estado <select name="status">@foreach(['todo'=>'A fazer','doing'=>'Em andamento','done'=>'Concluído'] as $value=>$text)<option value="{{ $value }}" @selected($task->status===$value)>{{ $text }}</option>@endforeach</select></label>@if(in_array('priorities', $access['features']))<label>Prioridade <select name="priority">@foreach(['low'=>'Baixa','medium'=>'Média','high'=>'Alta'] as $value=>$text)<option value="{{ $value }}" @selected($task->priority===$value)>{{ $text }}</option>@endforeach</select></label>@endif @if(in_array('deadlines', $access['features']))<label>Prazo <input type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}"></label>@endif<button type="submit">Salvar tarefa</button></form></details>
                                    <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}">@csrf @method('DELETE')<button type="submit">Excluir tarefa</button></form>
                                @endif
                            @endcan
                            <h5 class="font-semibold">Checklist</h5>
                            @foreach($task->checklistItems as $item)
                                <p>{{ $item->completed ? '✓' : '○' }} {{ $item->title }}</p>
                                @can('editTasks', $project) @if(!$project->archived)<form method="POST" action="{{ route('projects.checklist.update', [$project, $task, $item]) }}">@csrf @method('PATCH')<input type="hidden" name="completed" value="{{ $item->completed ? 0 : 1 }}"><button type="submit">{{ $item->completed ? 'Desmarcar' : 'Concluir' }}</button></form><form method="POST" action="{{ route('projects.checklist.destroy', [$project, $task, $item]) }}">@csrf @method('DELETE')<button type="submit">Remover item</button></form>@endif @endcan
                            @endforeach
                            @can('editTasks', $project) @if(!$project->archived)<form method="POST" action="{{ route('projects.checklist.store', [$project, $task]) }}">@csrf<label>Novo item <input class="rounded w-full" required name="title" maxlength="255"></label><button type="submit">Adicionar item</button></form>@endif @endcan
                        </article>
                    @endforeach
                </div>
            @endforeach
        </section>
        <p id="board-feedback" role="status"></p>
        @if(in_array('reports', $access['features']))<a class="underline" href="{{ route('projects.report', $project) }}">Relatório do projeto</a>@endif
        @if(in_array('history', $access['features']))<a class="underline" href="{{ route('projects.history', $project) }}">Histórico de tarefas</a>@endif
        @can('delete', $project)<form method="POST" action="{{ route('projects.destroy', $project) }}">@csrf @method('DELETE')<button class="text-red-700" type="submit">Excluir projeto</button></form>@endcan
    </div>
    <script>
        (() => {
            let moving;
            document.querySelectorAll('[data-task-url]').forEach(card => card.addEventListener('dragstart', event => {
                moving = card;
                event.dataTransfer.setData('text/plain', card.dataset.taskUrl);
            }));
            document.addEventListener('dragend', () => { moving = null; });
            document.querySelectorAll('[data-task-column]').forEach(column => {
                column.addEventListener('dragover', event => { if (moving) event.preventDefault(); });
                column.addEventListener('drop', async event => {
                    if (!moving) return;
                    event.preventDefault();
                    const card = moving;
                    moving = null;
                    const feedback = document.getElementById('board-feedback');
                    try {
                        const response = await fetch(card.dataset.taskUrl, { method: 'PATCH', credentials: 'same-origin', headers: {
                            Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        }, body: JSON.stringify({ status: column.dataset.taskColumn }) });
                        if (!response.ok) throw new Error('Não foi possível mover a tarefa. Atualize o quadro e tente novamente.');
                        location.reload();
                    } catch (error) { feedback.textContent = error.message; }
                });
            });
        })();
    </script>
</x-app-layout>
