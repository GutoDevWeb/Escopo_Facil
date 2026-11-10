<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class MaintenanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('hosting.cron_secret');
        abort_unless(is_string($secret) && strlen($secret) >= 32 && hash_equals($secret, (string) $request->bearerToken()), 403);

        return Cache::lock('maintenance:subscriptions', 300)->get(function () {
            Artisan::call('subscriptions:expire');
            Artisan::call('notifications:deliver');
            return response()->json(['ok' => true])->header('Cache-Control', 'no-store');
        }) ?? response()->json(['ok' => true, 'running' => true])->header('Cache-Control', 'no-store');
    }
}
