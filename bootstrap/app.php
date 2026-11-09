<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (filter_var(env('TRUST_PLATFORM_PROXY', false), FILTER_VALIDATE_BOOL)) {
            $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT);
        }
        $middleware->web(append: [\App\Http\Middleware\TrackSystemActivity::class]);
        $middleware->alias([
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $invalidVerification = fn ($exception, \Illuminate\Http\Request $request) => $request->routeIs('verification.verify')
            ? response()->view('welcome', ['authScreen' => 'verification-invalid'], 403)
            : null;
        $exceptions->render(fn (\Illuminate\Routing\Exceptions\InvalidSignatureException $exception, \Illuminate\Http\Request $request) => $invalidVerification($exception, $request));
        $exceptions->render(fn (\Illuminate\Auth\Access\AuthorizationException $exception, \Illuminate\Http\Request $request) => $invalidVerification($exception, $request));
        $exceptions->render(function (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $exception, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() && $request->is('register', 'forgot-password', 'email/verification-notification')) {
                return response()->json([
                    'message' => 'Não foi possível enviar o e-mail agora.',
                    'csrf' => $request->session()->token(),
                    'authenticated' => $request->user() !== null,
                    'verified' => $request->user()?->hasVerifiedEmail() ?? false,
                    'email' => $request->user()?->email,
                ], 503);
            }
        });
    })->create();
