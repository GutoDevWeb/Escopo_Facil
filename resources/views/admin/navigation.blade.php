<nav class="admin-section-nav" aria-label="Navegação administrativa">
    <a class="admin-button {{ request()->routeIs('admin.dashboard') ? '' : 'admin-button--secondary' }}" href="{{ route('admin.dashboard') }}">Visão geral</a>
    <a class="admin-button {{ request()->routeIs('admin.subscribers.*') ? '' : 'admin-button--secondary' }}" href="{{ route('admin.subscribers.index') }}">Gerenciar assinaturas</a>
    <a class="admin-button {{ request()->routeIs('admin.plans.*') ? '' : 'admin-button--secondary' }}" href="{{ route('admin.plans.index') }}">Configurar planos</a>
</nav>
