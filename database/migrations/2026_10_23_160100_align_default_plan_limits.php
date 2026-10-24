<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Align only the seed defaults, preserving administrator-defined quotas.
        $defaults = ['free' => ['projects'=>3,'members'=>1], 'pro'=>['projects'=>15,'members'=>5], 'equipe'=>['projects'=>-1,'members'=>25]];
        foreach ($defaults as $slug => $limits) {
            $plan = DB::table('plans')->where('slug', $slug)->first();
            if ($plan && json_decode($plan->limits ?? 'null', true) === $limits) {
                DB::table('plans')->where('id', $plan->id)->update(['limits'=>json_encode(['projects'=>-1,'members'=>-1])]);
            }
        }
    }
    public function down(): void
    {
        // Do not overwrite live quotas when rolling back.
    }
};
