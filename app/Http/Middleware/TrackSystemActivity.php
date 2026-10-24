<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\UsageMetrics;

class TrackSystemActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->user() && $request->route() && $response->getStatusCode() < 400) {
            app(UsageMetrics::class)->record($request->user());
        }
        return $response;
    }
}
