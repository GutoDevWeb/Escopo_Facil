<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Projetos · {{ $access['name'] }}</h2></x-slot>
    <div class="max-w-7xl mx-auto p-6 space-y-6">
        <x-workspace-errors />
        <p>Seu plano: {{ $access['name'] }} · <a class="underline" href="{{ url('/#precos') }}">Consultar planos</a></p>
        <form method="GET" class="flex gap-3"><label>Buscar projeto <input type="search" name="search" value="{{ request('search') }}" maxlength="200" class="rounded"></label><button type="submit">Buscar</button></form>
        <section class="bg-white p-6 rounded-lg space-y-3">
            <h3 class="font-semibold">Novo projeto</h3>
            <form method="POST" action="{{ route('projects.store') }}" class="space-y-3">
                @csrf
                <label class="block">Título <input name="title" required maxlength="200" class="block w-full rounded" value="{{ old('title') }}"></label>
                <label class="block">Descrição <textarea name="description" maxlength="10000" class="block w-full rounded">{{ old('description') }}</textarea></label>
                <button class="bg-gray-800 text-white rounded px-4 py-2" type="submit">Criar projeto</button>
            </form>
        </section>
        <section class="grid gap-4 md:grid-cols-2" aria-label="Seus projetos">
            @forelse($projects as $project)
                <article class="bg-white p-6 rounded-lg"><h3 class="font-semibold text-lg"><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a></h3><p>{{ $project->description }}</p><p>{{ $project->tasks_count }} tarefas · {{ $project->archived ? 'Arquivado' : 'Ativo' }} · {{ $project->user_id === auth()->id() ? 'Proprietário' : 'Membro' }}</p><a class="underline" href="{{ route('projects.show', $project) }}">Abrir quadro</a></article>
            @empty <p>Nenhum projeto encontrado.</p> @endforelse
        </section>
        {{ $projects->links() }}
    </div>
</x-app-layout>
