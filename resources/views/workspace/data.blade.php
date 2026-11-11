@extends('layouts.system')
@section('content')
    <h1 class="headline">{{ $title }}</h1>
    <div class="workspace-data max-w-7xl mx-auto p-6 space-y-4">
        <a class="underline" href="{{ route('projects.show', $project) }}">← Voltar ao projeto</a>
        <section class="admin-panel">
            @if(array_key_exists('total', $data))
                <p>Total de tarefas: {{ $data['total'] }}</p>
                @foreach(['todo'=>'A fazer','doing'=>'Em andamento','done'=>'Concluído'] as $key=>$label)<p>{{ $label }}: {{ $data['by_status'][$key] ?? 0 }}</p>@endforeach
                @if(isset($data['by_priority']))
                    <h3 class="font-semibold">Por prioridade</h3>
                    @foreach(['low'=>'Baixa','medium'=>'Média','high'=>'Alta'] as $key=>$label)<p>{{ $label }}: {{ $data['by_priority'][$key] ?? 0 }}</p>@endforeach
                    <p>Tarefas atrasadas: {{ $data['overdue'] }}</p>
                @endif
            @else
                @forelse($data as $event)
                    <article class="border-b pb-4"><p>{{ $event->created_at->format('d/m/Y H:i') }} · {{ ['created'=>'Tarefa criada','updated'=>'Tarefa atualizada','deleted'=>'Tarefa excluída'][$event->action] ?? $event->action }}</p><p>{{ $event->changes['title'] ?? $event->changes['after']['title'] ?? 'Atualização de andamento ou dados' }}</p></article>
                @empty <p>Nenhuma alteração registrada.</p> @endforelse
            @endif
        </section>
        @isset($pagination){{ $pagination->links() }}@endisset
    </div>
@endsection
