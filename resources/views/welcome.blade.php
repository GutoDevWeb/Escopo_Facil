<!DOCTYPE html>
<html lang="pt-BR" data-motion="full">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<x-app-install-head />
<title>Escopo Fácil — Organize seus projetos e tarefas</title>
<meta name="description" content="Organize projetos, tarefas e equipes com o Escopo Fácil. Do primeiro quadro à entrega final, tudo no lugar.">
<link rel="preload" href="{{ asset('assets/landing/open-runde-regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('assets/landing/open-runde-semibold.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('assets/landing/landing.css') . '?v=' . filemtime(public_path('assets/landing/landing.css')) }}"><script src="{{ asset('assets/landing/landing.js') . '?v=' . filemtime(public_path('assets/landing/landing.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('assets/landing/login-modal.css') . '?v=' . filemtime(public_path('assets/landing/login-modal.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/landing/checkout-modal.css') . '?v=' . filemtime(public_path('assets/landing/checkout-modal.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/landing/legal.css') . '?v=' . filemtime(public_path('assets/landing/legal.css')) }}">
<script src="{{ asset('assets/landing/legal.js') . '?v=' . filemtime(public_path('assets/landing/legal.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('assets/system/entry.css') . '?v=' . filemtime(public_path('assets/system/entry.css')) }}">
<script src="{{ asset('assets/system/entry.js') . '?v=' . filemtime(public_path('assets/system/entry.js')) }}" defer></script>
<script src="{{ asset('assets/landing/login-modal.js') . '?v=' . filemtime(public_path('assets/landing/login-modal.js')) }}" defer></script>
@vite('resources/js/landing.jsx')
@vite('resources/js/checkout.js')
<link rel="stylesheet" href="{{ asset('assets/landing/mobile.css') }}?v={{ filemtime(public_path('assets/landing/mobile.css')) }}">
</head>
<body>
@php($startUrl = auth()->check() ? route('dashboard') : route('register'))
<a class="skip" href="#conteudo">Ir para o conteúdo</a>
<header><a href="{{ url('/') }}" aria-label="Escopo Fácil — início"><img class="logo" src="{{ asset('assets/landing/logo-original.svg') }}" alt="Escopo Fácil" width="185" height="53"></a><button class="menu" aria-label="Abrir menu" aria-expanded="false" aria-controls="navigation">☰</button><nav id="navigation" aria-label="Navegação principal"><a href="#features">Funcionalidades</a><a href="#beneficios">Benefícios</a><a href="#precos">Preços</a><a href="#contato">Contato</a></nav><div class="header-account"><a class="button header-login" data-login-trigger aria-haspopup="dialog" aria-controls="login-modal" href="{{ route('login') }}">Entre</a><a class="button header-cta" href="{{ route('register') }}">Crie sua Conta</a></div></header>
<main id="conteudo">
<div class="hero-wrap">
<section class="hero" aria-labelledby="hero-title"><span class="cloud left" aria-hidden="true"></span><span class="cloud right" aria-hidden="true"></span><div class="hero-copy"><h1 id="hero-title">Organize seus<br>projetos e tarefas</h1><p>Uma plataforma simples para organizar projetos, tarefas e equipes sem<br class="desktop"> confusão. Do primeiro quadro à entrega final, tudo fica no lugar.</p><div class="actions"><a class="button" href="{{ $startUrl }}">Comece agora grátis</a><a class="text-link" href="#features">Ver recursos ↗</a></div></div>
<div class="dashboard-stage"><div class="workspace"><img src="{{ asset('assets/landing/dashboard.jpg') }}" alt="Quadro do Escopo Fácil com tarefas organizadas em A fazer, Em andamento e Concluído" width="1425" height="1024" fetchpriority="high"></div></div></section>
</div>
<div class="lower-page">
<section class="benefits section" id="acesso" aria-labelledby="benefits-title"><p class="eyebrow">Acesso em qualquer lugar</p><h2 id="benefits-title">Gerencie seus projetos,<br>sem instalar nada</h2><div class="mobile-scene" data-device="mobile"><div class="device-images"><img class="mobile-photo device-photo" src="{{ asset('assets/landing/mobile-original.png') }}" alt="Escopo Fácil no celular, com a lista de projetos acessível pelo navegador" width="1417" height="941" loading="lazy"><img class="web-photo device-photo" src="{{ asset('assets/landing/web-original.png') }}" alt="Escopo Fácil na versão web, com projetos organizados no tablet" width="1672" height="941" loading="lazy" aria-hidden="true"></div><div class="web-cta device-switch" role="group" aria-label="Visualização da plataforma"><button type="button" data-device-option="mobile" aria-pressed="true">App Mobile</button><button type="button" data-device-option="web" aria-pressed="false">Versão Web</button></div></div></section>
<x-landing-sections :start-url="$startUrl" />
<section class="pricing section" id="precos" aria-labelledby="pricing-title"><span class="cloud left" aria-hidden="true"></span><span class="cloud right" aria-hidden="true"></span><p class="eyebrow">Planos</p><h2 id="pricing-title">Planos simples<br>para organizar melhor</h2><div class="plans">
{{-- Apresentação conforme a referência visual; as regras de assinatura permanecem no backend. --}}
<article class="plan">
    <p class="plan-label">7 dias grátis</p><h3>Free</h3>
    <p class="plan-description">Para projetos pequenos</p>
    <ul><li>Projetos ilimitados</li><li>Tarefas por status</li><li>Membros do projeto</li><li>Busca de tarefas</li><li>Painel web</li></ul>
    <a class="button soft" href="{{ $startUrl }}">Realizar teste grátis</a>
</article>
<article class="plan featured">
    <div class="billing-switch" role="group" aria-label="Período de cobrança"><button type="button" data-period="yearly" aria-pressed="true">Anual</button><button type="button" data-period="monthly" aria-pressed="false">Mensal</button></div>
    <div class="plan-name-row"><p class="plan-label">Escopo Pro</p><span class="saving-badge">Economize 20%</span></div>
    <h3 class="price"><span data-yearly="19,90" data-monthly="27,90">R$ 19,90</span></h3>
    <p class="plan-description">Para equipes que precisam de<br class="plan-break"> mais controle.</p>
    <ul><li>Tudo do Básico</li><li>Prioridades e prazos</li><li>Gestão de membros</li><li>Controle por projeto</li><li>Relatórios simples</li></ul>
    <a class="button" data-checkout-link href="{{ route('subscription.start', ['period' => 'yearly']) }}">Comprar agora</a>
</article>
<article class="plan">
    <?php
        $teamPlan = \App\Models\Plan::where('slug', 'equipe')->first();
        $teamMonthly = number_format($teamPlan?->price_monthly ?? 49.90, 2, ',', '.');
        $teamYearly = number_format($teamPlan?->price_yearly ?? 499.00, 2, ',', '.');
    ?>
    <p class="plan-label">Escopo Equipe</p>
    <h3 class="price"><span data-yearly="{{ $teamYearly }}" data-monthly="{{ $teamMonthly }}">R$ {{ $teamYearly }}</span> <small class="price-period" data-price-period>/ano</small></h3>
    <p class="plan-description">Para grupos maiores e projetos</p>
    <ul><li>Tudo do Pro</li><li>Permissões por função</li><li>Suporte à equipe</li><li>Histórico de tarefas</li><li>Acompanhamento completo</li></ul>
    <a class="button soft" data-checkout-link href="{{ route('subscription.plan.start', ['slug' => 'equipe', 'period' => 'yearly']) }}">Comprar agora</a>
</article>
</div><p class="plan-note" role="status" aria-live="polite">Exibindo valores anuais.</p></section>
<x-contact-section />
</div>
</main>
<x-site-footer />
<x-support-whatsapp />
<x-login-modal :auth-screen="$authScreen ?? null" :auth-email="$authEmail ?? null" :auth-token="$authToken ?? null" />
<x-checkout-modal :initial-period="$checkoutPeriod ?? 'yearly'" :initial-open="$checkoutOpen ?? false" :initial-plan="$checkoutPlan ?? 'pro'" />
<x-system-entry />
<x-legal-modal />
</body></html>
