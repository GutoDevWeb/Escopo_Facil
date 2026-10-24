<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsageMetrics
{
    public function record(User $user, bool $login = false): void
    {
        $key = ['user_id' => $user->id, 'day' => today()->toDateString()];
        DB::table('usage_metrics')->insertOrIgnore($key + ['requests' => 0, 'logins' => 0]);
        DB::table('usage_metrics')->where($key)->update([
            $login ? 'logins' : 'requests' => DB::raw(($login ? 'logins' : 'requests').' + 1'),
            'last_seen_at' => now(),
        ]);
    }
}
