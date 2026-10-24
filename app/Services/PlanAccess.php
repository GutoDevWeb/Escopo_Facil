<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;

class PlanAccess
{
    public function plan(User $user): ?Plan
    {
        // A paid plan_id is only a denormalized pointer, never proof of payment.
        $subscription = $user->subscriptions()->where('status', 'active')
            ->where('expires_at', '>', now())->latest('id')->first();
        if ($subscription && in_array($subscription->plan?->slug, ['pro', 'equipe'], true)) {
            return $subscription->plan;
        }
        return Plan::where('slug', 'free')->first();
    }

    public function active(User $user): bool
    {
        $plan = $this->plan($user);
        if (! $plan) return false;
        $days = config('entitlements.free_trial_days');
        return $plan->slug !== 'free' || $days === null || $user->created_at->copy()->addDays($days)->isFuture();
    }

    public function allows(User $user, string $feature): bool
    {
        return $this->active($user) && in_array($feature, config('entitlements.features.'.$this->plan($user)?->slug, []), true);
    }

    public function require(User $user, string $feature): void
    {
        abort_unless($this->allows($user, $feature), 403, 'Seu plano não disponibiliza esta funcionalidade.');
    }

    public function limit(User $user, string $resource): ?int
    {
        $limit = $this->plan($user)?->limits[$resource] ?? null;
        return is_numeric($limit) && (int) $limit >= 0 ? (int) $limit : null;
    }

    public function summary(User $user): array
    {
        $plan = $this->plan($user);
        return ['slug' => $plan?->slug, 'name' => $plan?->name, 'active' => $this->active($user),
            'features' => $this->active($user) ? config('entitlements.features.'.$plan?->slug, []) : [],
            'limits' => $plan?->limits ?? []];
    }
}
