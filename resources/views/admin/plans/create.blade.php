@extends('layouts.system', app(\App\Http\Controllers\SystemController::class)->common(request(), 'admin'))
@section('content')
<header class="page-header"><div><p class="admin-eyebrow">ADMINISTRAÇÃO</p><h1 class="headline">Novo plano</h1><p class="admin-muted">Preencha as informações do plano.</p></div></header>
@include('admin.navigation')
<section class="admin-panel admin-editor"><form class="admin-form" action="{{ route('admin.plans.store') }}" method="POST">@csrf
@include('admin.plans.form')
</form></section>
@endsection
