<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class,
            fn ($event) => app(\App\Services\UsageMetrics::class)->record($event->user, true));
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by('register:'.$request->ip()));
        RateLimiter::for('password-recovery', fn (Request $request) => Limit::perMinute(5)->by('recovery:'.$request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(10)->by('reset:'.$request->ip()));

        VerifyEmail::toMailUsing(fn ($user, $url) => (new MailMessage)
            ->when(config('mail.support_reply_to'), fn ($mail, $address) => $mail->replyTo($address, config('mail.from.name')))
            ->subject('Confirme seu e-mail — Escopo Fácil')
            ->view('emails.auth', [
                'name' => $user->name, 'title' => 'Confirme seu e-mail',
                'intro' => 'Sua conta no Escopo Fácil está quase pronta. Confirme seu endereço para começar a organizar seus projetos.',
                'action' => 'Confirmar meu e-mail', 'url' => $url,
                'expiry' => config('auth.verification.expire', 60),
            ]));

        ResetPassword::toMailUsing(fn ($user, $token) => (new MailMessage)
            ->when(config('mail.support_reply_to'), fn ($mail, $address) => $mail->replyTo($address, config('mail.from.name')))
            ->subject('Redefina sua senha — Escopo Fácil')
            ->view('emails.auth', [
                'name' => $user->name, 'title' => 'Redefina sua senha',
                'intro' => 'Recebemos uma solicitação para redefinir a senha da sua conta no Escopo Fácil. Use o botão abaixo para escolher uma nova senha.',
                'action' => 'Redefinir minha senha',
                'url' => url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)),
                'expiry' => config('auth.passwords.users.expire', 60),
            ]));
    }
}
