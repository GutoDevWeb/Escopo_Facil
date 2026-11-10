@php($featureAccess = $projectAccess ?? app(\App\Services\PlanAccess::class)->summary(auth()->user()))
<section class="plan-overview {{ ($dismissible ?? false) ? 'plan-overview--dismissible' : '' }}" aria-label="Recursos e limitações do plano">
    @if($dismissible ?? false)
    <button type="button" class="plan-overview-dismiss" data-dismiss-plan-overview aria-label="Fechar aviso de recursos deste projeto" title="Fechar aviso até a próxima visita">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
    </button>
    @endif
    <div class="plan-overview-heading"><div><span class="admin-eyebrow">{{ isset($projectAccess) ? 'RECURSOS DESTE PROJETO' : 'SEU PLANO' }}</span><h2>{{ $featureAccess['name'] ?? 'Free' }}</h2></div><span class="plan-overview-note">Projetos ilimitados · acesso por funcionalidades</span></div>
    <div class="plan-feature-grid">
    @foreach([
        ['tasks', 'Organização', 'Projetos, tarefas por status, membros, pesquisa e checklist.', 'Free'],
        ['reports', 'Controle avançado', 'Prioridades, prazos e relatórios do projeto.', 'Pro'],
        ['roles', 'Trabalho em equipe', 'Permissões por função, histórico e relatórios completos.', 'Equipe'],
    ] as [$feature, $label, $description, $required])
        <article class="plan-feature {{ in_array($feature, $featureAccess['features']) ? 'is-available' : 'is-locked' }}">
            <div><span class="plan-feature-symbol" aria-hidden="true">@if(in_array($feature, $featureAccess['features']))✓@else @include('system.lock-icon') @endif</span><strong>{{ $label }}</strong></div>
            <p>{{ $description }}</p><small>{{ in_array($feature, $featureAccess['features']) ? 'Disponível neste plano' : 'Requer plano '.$required }}</small>
        </article>
    @endforeach
    </div>
    @if($featureAccess['trial_active'] ?? false)
    <p class="plan-trial-notice"><strong>Teste completo ativo até {{ \Carbon\Carbon::parse($featureAccess['trial_ends_at'])->format('d/m/Y H:i') }}.</strong> Durante os primeiros 7 dias da conta, todos os recursos de Pro e Equipe estão liberados. Depois, você continua no Free, com as funcionalidades pagas bloqueadas. Seus dados são preservados.</p>
    @elseif(($featureAccess['slug'] ?? '') === 'free')
    <p class="plan-demo-guide">O período de teste completo terminou. Sua conta continua no Free; as opções com cadeado exigem uma assinatura Pro ou Equipe.</p>
    @endif
    @if(isset($canWrite) && !$canWrite)
    <p class="plan-demo-guide">{{ isset($project) && $project->archived ? 'Projeto arquivado: a edição está desabilitada.' : 'Seu acesso a este projeto é somente leitura: você pode consultar os cartões e checklists, sem alterar os dados.' }}</p>
    @endif
    @if(auth()->user()->is_demo)
    <p class="plan-demo-guide">Conta de demonstração: abra o projeto de exemplo e teste os cartões. No Pro, confira prioridade, prazo e relatório. No Equipe, confira também o histórico e os membros com funções diferentes. Os bloqueios são verificados pelo servidor.</p>
    @endif
</section>
