<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }} — Escopo Fácil</title></head>
<body style="margin:0;background:#f5f1ed;color:#202534;font-family:Arial,Helvetica,sans-serif;padding:32px 16px">
<table role="presentation" style="width:100%;max-width:560px;margin:0 auto;background:#fff;border-radius:20px;border-collapse:separate"><tr><td style="padding:40px 32px">
    <p style="margin:0 0 30px"><img src="{{ $message->embed(public_path('assets/landing/logo.png')) }}" alt="Escopo Fácil" width="180" style="display:block;width:180px;max-width:100%;height:auto;border:0"></p>
    <h1 style="font-size:27px;line-height:1.25;margin:0 0 20px">{{ $title }}</h1>
    <p style="line-height:1.6">Olá, {{ $name }}!</p><p style="line-height:1.6;color:#5e697c">{{ $intro }}</p>
    <table role="presentation" style="margin:28px 0"><tr><td style="background:#8b0836;border-radius:8px"><a href="{{ $url }}" style="display:inline-block;color:#fff;text-decoration:none;font-weight:bold;padding:17px 24px">{{ $action }}</a></td></tr></table>
    <p style="font-size:14px;color:#5e697c;line-height:1.6">Este link expira em {{ $expiry }} minutos. Se você não fez esta solicitação, pode ignorar este e-mail.</p>
    <p style="font-size:12px;color:#73839e;line-height:1.6;border-top:1px solid #eee;padding-top:20px;overflow-wrap:anywhere">Se o botão não funcionar, copie este endereço no navegador:<br><a href="{{ $url }}" style="color:#8b0836;word-break:break-all">{{ $url }}</a></p>
</td></tr></table></body></html>
