@extends('layouts.system')
@section('content')
<div class="admin-overview">
    <header class="page-header"><div><p class="admin-eyebrow">ADMINISTRAÇÃO</p><h1 class="headline">Visão geral do Escopo Fácil</h1><p class="admin-muted">Usuários, assinaturas e atividade do sistema em um só lugar.</p></div></header>
    @include('admin.navigation')
    <div class="admin-metrics">
        @foreach([
            ['Usuários cadastrados', $metrics['users'], $metrics['verified'].' com e-mail confirmado'],
            ['Ativos agora', $metrics['online'], 'Atividade nos últimos 5 minutos'],
            ['Sessões válidas', $metrics['sessions'], 'Dentro do prazo da sessão'],
            ['Assinaturas ativas', $metrics['active_subscriptions'], $metrics['subscriptions'].' assinaturas no histórico'],
            ['Compras confirmadas', $metrics['purchases'], 'Pagamentos de teste confirmados'],
            ['Volume simulado', 'R$ '.number_format($metrics['simulated_amount'], 2, ',', '.'), 'Ambiente de simulação · sem receita real'],
            ['Projetos / tarefas', $metrics['projects'].' / '.$metrics['tasks'], $metrics['done'].' tarefas concluídas'],
            ['Logins / acessos', $metrics['logins'].' / '.$metrics['requests'], 'Contabilizados desde a ativação das métricas'],
        ] as [$label, $value, $description])
        <article class="admin-metric"><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $description }}</small></article>
        @endforeach
    </div>
    <div class="admin-charts">
        @foreach(['requests' => 'Uso do sistema', 'logins' => 'Logins realizados'] as $key => $label)
        <section class="admin-panel"><h2>{{ $label }}</h2><p class="admin-muted">Últimos 14 dias · {{ $series->sum($key) }} no período</p>
            <div class="admin-bars" role="img" aria-label="{{ $label }} por dia nos últimos 14 dias">
                @foreach($series as $point)
                <div class="admin-bar-column" title="{{ $point['day'] }}: {{ $point[$key] }}"><span>{{ $point[$key] }}</span><i style="height: {{ max(2, 125 * $point[$key] / max(1, $series->max($key))) }}px"></i><small>{{ $point['day'] }}</small></div>
                @endforeach
            </div>
        </section>
        @endforeach
    </div>
    <section class="admin-panel">
        <div class="admin-users-header"><div><h2>Usuários cadastrados</h2><p class="admin-muted">Plano efetivo, confirmação de e-mail e presença.</p></div>
            <form method="GET"><label class="sr-only" for="admin-search">Buscar usuários</label><input id="admin-search" name="search" value="{{ request('search') }}" placeholder="Nome ou e-mail"><button type="submit">Buscar</button></form>
        </div>
        <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Usuário</th><th>Perfil</th><th>Status da conta</th><th>Plano atual</th><th>Sessão</th><th>Última atividade</th></tr></thead><tbody>
            @forelse($rows as $row)
            <tr><td><strong>{{ $row['user']->name }}</strong><small>{{ $row['user']->email }}</small></td><td>{{ $row['user']->is_admin ? 'Administrador' : 'Usuário' }}</td><td>{{ $row['user']->hasVerifiedEmail() ? 'Confirmado' : 'Aguardando confirmação' }}</td><td>{{ $row['plan']['name'] ?? 'Free' }}</td><td><span class="admin-presence {{ $row['online'] ? 'is-online' : '' }}">{{ $row['online'] ? 'Ativo agora' : ($row['session'] ? 'Sessão válida · inativo' : 'Sem sessão ativa') }}</span></td><td>{{ $row['last_seen'] ? \Carbon\Carbon::parse($row['last_seen'])->format('d/m/Y H:i') : 'Sem registro' }}</td></tr>
            @empty<tr><td colspan="6">Nenhum usuário encontrado.</td></tr>@endforelse
        </tbody></table></div>
        {{ $users->links() }}
        <p class="admin-muted">Presença estimada pela última requisição. Fechar a aba não encerra a sessão imediatamente. Atualize a página para atualizar os indicadores.</p>
    </section>
</div>
@endsection
