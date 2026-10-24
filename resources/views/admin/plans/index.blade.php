@extends('layouts.system', app(\App\Http\Controllers\SystemController::class)->common(request(), 'admin'))
@section('content')
<header class="page-header"><div><p class="admin-eyebrow">ADMINISTRAÇÃO</p><h1 class="headline">Configurar planos</h1><p class="admin-muted">Gerencie os valores e as configurações dos planos do Escopo Fácil.</p></div><a class="admin-button" href="{{ route('admin.plans.create') }}">Novo plano</a></header>
@include('admin.navigation')
@if(session('success'))<div class="system-alert" role="status">{{ session('success') }}</div>@endif
<section class="admin-panel"><h2>Planos disponíveis</h2><p class="admin-muted">Valores mensais e anuais. As funcionalidades de Free, Pro e Equipe seguem as regras de acesso do sistema.</p>
<div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Plano</th><th>Mensal</th><th>Anual</th><th>Projetos</th><th>Membros</th><th>Ações</th></tr></thead><tbody>
@forelse($plans as $plan)
<tr><td><strong>{{ $plan->name }}</strong><small>{{ $plan->slug }}</small>@if($plan->description)<small>{{ $plan->description }}</small>@endif</td>
    <td>R$ {{ number_format($plan->price_monthly, 2, ',', '.') }}</td><td>R$ {{ number_format($plan->price_yearly, 2, ',', '.') }}</td>
    @foreach(['projects', 'members'] as $resource)<td>{{ ($plan->limits[$resource] ?? -1) < 0 ? 'Ilimitados' : $plan->limits[$resource] }}</td>@endforeach
    <td><div class="admin-row-actions"><a class="admin-button admin-button--secondary" href="{{ route('admin.plans.edit', $plan) }}">Editar</a>
    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Deseja excluir este plano? Planos em uso não podem ser excluídos.');">@csrf @method('DELETE')<button type="submit" class="admin-button admin-button--danger-outline">Excluir</button></form></div></td>
</tr>
@empty<tr><td colspan="6">Nenhum plano cadastrado.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
