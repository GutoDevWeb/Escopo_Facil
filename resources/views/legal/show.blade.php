<!DOCTYPE html>
<html lang="pt-BR">
<head>
<x-favicon />
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Escopo Fácil</title>
    <meta name="description" content="{{ $title }} do Escopo Fácil.">
    <link rel="stylesheet" href="{{ asset('assets/landing/legal.css') . '?v=' . filemtime(public_path('assets/landing/legal.css')) }}">
</head>
<body class="legal-page">
    <nav class="legal-page-nav" aria-label="Navegação"><a href="{{ url('/') }}"><img src="{{ asset('assets/landing/logo-original.svg') }}" alt="Escopo Fácil" width="185" height="53"></a><a href="{{ url('/') }}">← Voltar ao site</a></nav>
    <main class="legal-page-card">@include('legal.content')</main>
</body>
</html>
