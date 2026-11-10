<!DOCTYPE html>
<html lang="pt-BR" class="system-app"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}"><title>Escopo Fácil</title>
<script src="{{ asset('system/assets/js/theme.js') }}?v={{ filemtime(public_path('system/assets/js/theme.js')) }}"></script>
@foreach(['global','layout','auth/components','avatar','toast','user-menu'] as $css)
<link rel="stylesheet" href="{{ asset('system/assets/css/'.$css.'.css') }}">
@endforeach
@if(in_array($page, ['dashboard','projetos','tarefas','configuracao']))
<link rel="stylesheet" href="{{ asset('system/assets/css/pages/'.$page.'.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('system/assets/css/integration.css') }}">
<link rel="stylesheet" href="{{ asset('system/assets/css/theme.css') }}?v={{ filemtime(public_path('system/assets/css/theme.css')) }}">
<link rel="stylesheet" href="{{ asset('system/assets/css/mobile.css') }}?v={{ filemtime(public_path('system/assets/css/mobile.css')) }}">
<x-app-install-head />
<script src="{{ asset('system/assets/js/mobile.js') }}?v={{ filemtime(public_path('system/assets/js/mobile.js')) }}" defer></script>
</head><body data-base-url="{{ url('/system') }}" data-usuario-id="{{ auth()->id() }}" data-features='@json($access["features"])' data-can-write="{{ ($canWrite ?? true) ? 'true' : 'false' }}">
<div class="layout">
<header class="system-mobile-header">
    <button type="button" data-sidebar-toggle aria-controls="system-sidebar" aria-expanded="false" aria-label="Abrir menu"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <a href="{{ route('dashboard') }}" aria-label="Escopo Fácil — Dashboard"><img src="{{ asset('system/assets/icon/logo/Vector (3).svg') }}" width="32" height="32" alt=""><span>Escopo Fácil</span></a>
</header>
<div class="sidebar-backdrop" data-sidebar-close hidden></div>
<aside class="sidebar" id="system-sidebar" aria-label="Menu do sistema">
    <button class="sidebar-mobile-close" type="button" data-sidebar-close aria-label="Fechar menu">×</button>
    <div class="sidebar-logo">
        <img class="sidebar-logo-light" src="{{ url('/system') }}/assets/images/Logo.png" alt="Escopo Fácil" width="185" height="54">
        <img class="sidebar-logo-dark" src="{{ asset('system/assets/images/logo-dark.png') }}" alt="Escopo Fácil" width="504" height="161">
    </div>

    <nav class="sidebar-nav">
        @if(auth()->user()->is_admin)
        <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ $page === 'admin' ? 'active' : '' }}">
            <img src="{{ asset('system/assets/icon/dashboard.svg') }}" alt=""> Administração
        </a>
        @endif
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ $page === 'dashboard' ? 'active' : '' }}">
            <img src="{{ url('/system') }}/assets/icon/dashboard.svg" alt=""> Dashboard
        </a>
        <a href="{{ route('projects.index') }}" class="sidebar-link {{ $page === 'projetos' || $page === 'tarefas' ? 'active' : '' }}">
            <img src="{{ url('/system') }}/assets/icon/folder-search.svg" alt=""> Projetos
        </a>
        <a href="{{ route('profile.edit') }}" class="sidebar-link {{ $page === 'configuracao' ? 'active' : '' }}">
            <img src="{{ url('/system') }}/assets/icon/configurações.svg" alt=""> Configuração
        </a>
    </nav>

    @if(!auth()->user()->is_admin && app(\App\Services\PlanAccess::class)->plan(auth()->user())?->slug === 'free')
    <div class="sidebar-banner">
        <div class="sidebar-banner-icon">
            <img src="{{ url('/system') }}/assets/icon/logo/Vector%20(3).svg" alt="Escopo Fácil" width="32" height="32">
        </div>
        <p class="sidebar-banner-title">Acesse nosso site</p>
        <p class="sidebar-banner-desc">Conheça planos, recursos e novidades do Escopo Fácil.</p>
        <a href="{{ url('/') }}" target="_blank" rel="noopener noreferrer" class="sidebar-banner-btn">
            Visitar site
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transform:scaleX(-1);flex-shrink:0">
                <path d="M19 12H5"/><path d="M12 19L5 12L12 5"/>
            </svg>
        </a>
    </div>

    @endif
    <div class="sidebar-logout">
        <button class="system-theme-switch" type="button" role="switch" data-theme-toggle aria-label="Modo escuro" aria-checked="false" title="Ativar modo escuro">
            <svg class="theme-switch-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg>
            <svg class="theme-switch-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.7 14a8.6 8.6 0 0 1-10.6-10.7A9 9 0 1 0 20.7 14Z"/></svg>
            <span class="theme-switch-thumb" aria-hidden="true"></span>
        </button>
        <div class="sidebar-plan {{ auth()->user()->is_admin ? 'sidebar-plan--admin' : '' }}" aria-label="Perfil ou plano atual">
            @if(auth()->user()->is_admin)
                <span class="sidebar-admin-label">Admin</span>
            @else
                <small>Seu plano</small><strong>{{ app(\App\Services\PlanAccess::class)->summary(auth()->user())['name'] ?? 'Free' }}</strong>
            @endif
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-link">
            <img src="{{ url('/system') }}/assets/icon/logout.svg" alt=""> Sair
        </button></form>
    </div>
</aside>
<main class="main">
@if($errors->any())<div class="system-alert" role="alert">{{ $errors->first() }}</div>@endif
@if(session('status'))<div class="system-alert" role="status">Alterações salvas.</div>@endif
@yield('content')</main></div>    
    <div id="user-menu-dropdown" class="user-menu-dropdown" hidden>
        <div class="user-menu-header">
            <?= \App\Support\SystemAvatar::renderAvatar($usuario['avatar'] ?? '', $usuario['nome'] ?? '', 'user-menu-avatar') ?>
            <div class="user-menu-info">
                <span class="user-menu-nome"><?= htmlspecialchars($usuario['nome'] ?? '') ?></span>
                <span class="user-menu-email"><?= htmlspecialchars($usuario['email'] ?? '') ?></span>
            </div>
        </div>
        <div class="user-menu-divider"></div>
        <a href="{{ url('/system') }}/index.php?page=configuracao" class="user-menu-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Configurações
        </a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="user-menu-item user-menu-item--danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sair
        </button></form>
    </div>
    


@include('system.notifications-panel')
<script src="{{ asset('system/assets/js/api.js') }}"></script>
<script src="{{ asset('system/assets/js/notifications.js') }}?v={{ filemtime(public_path('system/assets/js/notifications.js')) }}"></script>
@foreach(['toast','avatar','avatar-picker','user-menu'] as $js)
<script src="{{ asset('system/assets/js/'.$js.'.js') }}"></script>
@endforeach
@if(in_array($page, ['projetos','configuracao']) || ($page === 'tarefas' && isset($projetoId)))
<script src="{{ asset('system/assets/js/pages/'.$page.'.js') }}"></script>
@endif
@if($page === 'dashboard')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.js"></script>
@endif
<script src="{{ asset('system/assets/js/integration.js') }}?v={{ filemtime(public_path('system/assets/js/integration.js')) }}"></script>
</body></html>
