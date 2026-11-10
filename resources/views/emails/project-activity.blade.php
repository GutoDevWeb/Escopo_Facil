<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>{{ $title }} - Escopo Fácil</title></head>
<body style="margin:0;background:#f5f1ed;color:#202534;font-family:Arial,Helvetica,sans-serif;padding:32px 16px">
<table role="presentation" style="width:100%;max-width:560px;margin:0 auto;background:#fff;border-radius:20px"><tr><td style="padding:32px">
    <img src="{{ $message->embed(public_path('assets/landing/logo.png')) }}" alt="Escopo Fácil" width="180" style="max-width:100%;height:auto">
    <h1 style="font-size:24px;line-height:1.3">{{ $title }}</h1>
    <p>Olá, {{ $name }}!</p><p style="line-height:1.6">{{ $body }}</p>
    <p style="margin:28px 0"><a href="{{ $url }}" style="display:inline-block;background:#8b0836;color:#fff;text-decoration:none;border-radius:8px;padding:16px 24px">Abrir meus projetos</a></p>
    <p style="font-size:12px;color:#5e697c">Você recebeu este aviso porque participa de um projeto no Escopo Fácil.</p>
</td></tr></table></body></html>
