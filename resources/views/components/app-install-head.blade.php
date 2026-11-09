<x-favicon />
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v={{ filemtime(public_path('manifest.webmanifest')) }}">
<meta name="theme-color" content="#8b0836">
<meta name="apple-mobile-web-app-title" content="Escopo Fácil">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}?v={{ filemtime(public_path('icons/apple-touch-icon.png')) }}">
<link rel="stylesheet" href="{{ asset('assets/app-install.css') }}?v={{ filemtime(public_path('assets/app-install.css')) }}">
<script src="{{ asset('assets/app-install.js') }}?v={{ filemtime(public_path('assets/app-install.js')) }}" defer></script>
