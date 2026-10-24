@extends('layouts.system', app(\App\Http\Controllers\SystemController::class)->common(request(), 'admin'))
@section('content')
<header class="page-header"><div><p class="admin-eyebrow">ADMINISTRAÇÃO</p><h1 class="headline">Editar plano</h1><p class="admin-muted">{{ $plan->name }}</p></div></header>
@include('admin.navigation')
<section class="admin-panel admin-editor"><form class="admin-form" action="{{ route('admin.plans.update', $plan) }}" method="POST">@csrf @method('PUT')
@include('admin.plans.form')
</form></section>
@endsection
