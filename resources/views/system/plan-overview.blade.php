@php($featureAccess = $projectAccess ?? app(\App\Services\PlanAccess::class)->summary(auth()->user()))
<section class="plan-overview" aria-label="Recursos e limitações do plano">
    <div class="plan-overview-heading"><div><span class="admin-eyebrow">{{ isset($projectAccess) ? 'RECURSOS DESTE PROJETO' : 'SEU PLANO' }}</span><h2>{{ $featureAccess['name'] ?? 'Free' }}</h2></div><span class="plan-overview-note">Projetos ilimitados · acesso por funcionalidades</span></div>
    <div class="plan-feature-grid">
    @foreach([
        ['tasks', 'Organização', 'Projetos, tarefas por status, membros, pesquisa e checklist.', 'Free'],
        ['reports', 'Controle avançado', 'Prioridades, prazos e relatórios do projeto.', 'Pro'],
        ['roles', 'Trabalho em equipe', 'Permissões por função, histórico e relatórios completos.', 'Equipe'],
    ] as [$feature, $label, $description, $required])
        <article class="plan-feature {{ in_array($feature, $featureAccess['features']) ? 'is-available' : 'is-locked' }}">
            <div><span class="plan-feature-symbol" aria-hidden="true">{{ in_array($feature, $featureAccess['features']) ? '✓' : '🔒' }}</span><strong>{{ $label }}</strong></div>
            <p>{{ $description }}</p><small>{{ in_array($feature, $featureAccess['features']) ? 'Disponível neste plano' : 'Requer plano '.$required }}</small>
        </article>
    @endforeach
    </div>
    @if(isset($canWrite) && !$canWrite)
    <p class="plan-demo-guide">{{ isset($project) && $project->archived ? 'Projeto arquivado: a edição está desabilitada.' : 'Seu acesso a este projeto é somente leitura: você pode consultar os cartões e checklists, sem alterar os dados.' }}</p>
    @endif
    @if(auth()->user()->is_demo)
    <p class="plan-demo-guide">Conta de demonstração: abra o projeto de exemplo e teste os cartões. No Pro, confira prioridade, prazo e relatório. No Equipe, confira também o histórico e os membros com funções diferentes. Os bloqueios são verificados pelo servidor.</p>
    @endif
</section>
