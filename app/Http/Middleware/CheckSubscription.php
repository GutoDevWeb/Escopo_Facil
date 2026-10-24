<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) return redirect()->route('login');
        abort_unless(app(\App\Services\PlanAccess::class)->active($user), 403,
            'Seu acesso ao sistema não está ativo. Consulte os planos ou entre em contato com o suporte.');

        return $next($request);
    }
}
