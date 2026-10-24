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
        return $this->plan($user) !== null;
    }

    private function trialEndsAt(User $user): ?\Illuminate\Support\Carbon
    {
        $days = config('entitlements.free_trial_days');
        return $days && $user->created_at ? $user->created_at->copy()->addDays((int) $days) : null;
    }

    public function trialActive(User $user): bool
    {
        return $this->plan($user)?->slug === 'free' && ($this->trialEndsAt($user)?->isFuture() ?? false);
    }

    public function allows(User $user, string $feature): bool
    {
        $plan = $this->plan($user);
        $tier = $plan?->slug === 'free' && ($this->trialEndsAt($user)?->isFuture() ?? false) ? 'equipe' : $plan?->slug;
        return $plan !== null && in_array($feature, config('entitlements.features.'.$tier, []), true);
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
        $trial = $plan?->slug === 'free' && ($this->trialEndsAt($user)?->isFuture() ?? false);
        return ['slug' => $plan?->slug, 'name' => $plan?->name, 'active' => $plan !== null,
            'trial_active' => $trial, 'trial_ends_at' => $plan?->slug === 'free' ? $this->trialEndsAt($user)?->toIso8601String() : null,
            'features' => $plan ? config('entitlements.features.'.($trial ? 'equipe' : $plan->slug), []) : [],
            'limits' => $plan?->limits ?? []];
    }
}
