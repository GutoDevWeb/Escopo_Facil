<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:admin {email} {--revoke}', function () {
    $user = \App\Models\User::where('email', mb_strtolower($this->argument('email')))->first();
    if (! $user) { $this->error('Conta não encontrada.'); return 1; }
    $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();
    $this->info($this->option('revoke') ? 'Permissão administrativa removida.' : 'Permissão administrativa concedida.');
    return 0;
})->purpose('Concede ou revoga administração para uma conta existente (uso no servidor).');

Artisan::command('subscriptions:expire', function () {
    $count = \Illuminate\Support\Facades\DB::transaction(function () {
        return \App\Models\Subscription::where('status', 'active')->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())->update(['status' => 'expired']);
    });
    $this->info("Assinaturas expiradas: {$count}");
})->purpose('Atualiza o histórico de assinaturas vencidas; a autorização verifica a data em cada requisição.');

\Illuminate\Support\Facades\Schedule::command('subscriptions:expire')->hourly();

Artisan::command('notifications:deliver', function () {
    app(\App\Services\TaskDeadlineNotifications::class)->send();
    app(\App\Services\ProjectActivity::class)->retryMail();
    $this->info('Alertas de prazo verificados e e-mails pendentes processados.');
})->purpose('Envia alertas nas 24 horas anteriores ao prazo e tenta novamente e-mails pendentes.');

\Illuminate\Support\Facades\Schedule::command('notifications:deliver')->hourly()->withoutOverlapping();
