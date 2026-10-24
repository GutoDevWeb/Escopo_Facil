@extends('layouts.system', app(\App\Http\Controllers\SystemController::class)->common(request(), 'admin'))
@section('content')
<header class="page-header"><div><p class="admin-eyebrow">ADMINISTRAÇÃO</p><h1 class="headline">Gerenciar assinaturas</h1><p class="admin-muted">Consulte o plano atual e atualize a assinatura de cada usuário.</p></div></header>
@include('admin.navigation')
@if(session('success'))<div class="system-alert" role="status">{{ session('success') }}</div>@endif
<section class="admin-panel">
    <h2>Assinantes</h2><p class="admin-muted">{{ $users->total() }} usuários cadastrados. Planos pagos exigem uma data de validade.</p>
    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Usuário</th><th>Plano atual</th><th>Atualizar assinatura</th></tr></thead><tbody>
    @forelse($users as $subscriber)
        @php
            $effective = app(\App\Services\PlanAccess::class)->plan($subscriber);
            $subscription = $subscriber->subscriptions()->where('status', 'active')->where('expires_at', '>', now())->latest('id')->first();
        @endphp
        <tr><td><strong>{{ $subscriber->name }}</strong><small>{{ $subscriber->email }}</small></td>
        <td><span class="admin-plan-badge">{{ $effective?->name ?? 'Free' }}</span><small>{{ $subscription ? 'Até '.$subscription->expires_at->format('d/m/Y H:i') : 'Sem vencimento' }}</small></td>
        <td><form method="POST" action="{{ route('admin.subscribers.update', $subscriber) }}" class="admin-subscription-form admin-form">
            @csrf @method('PATCH')
            <label>Plano<select name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($effective?->id === $plan->id)>{{ $plan->name }}</option>@endforeach</select></label>
            <label>Validade do plano pago<input type="datetime-local" name="expires_at" value="{{ $subscription?->expires_at->format('Y-m-d\TH:i') }}"></label>
            <button class="admin-button" type="submit">Salvar assinatura</button>
        </form></td></tr>
    @empty<tr><td colspan="3">Nenhum usuário cadastrado.</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-pagination">{{ $users->links() }}</div>
</section>
@endsection
