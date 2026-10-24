<?php

namespace Tests\Feature;

use App\Models\{Plan, User, Subscription};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PlanSeeder::class);
        config(['payments.driver' => 'local']);
    }

    public function test_team_choice_and_price_survive_guest_login(): void
    {
        $u = User::factory()->create();
        $team = Plan::where('slug', 'equipe')->firstOrFail();
        $this->get('/assinar/equipe?period=monthly')->assertOk()->assertViewHas('checkoutPlan', 'equipe');
        $this->getJson('/checkout/options/equipe')->assertOk()->assertJsonPath('plan.slug', 'equipe')->assertJsonPath('plan.monthly', 4990);
        $url = route('subscription.checkout', ['plan' => $team, 'period' => 'yearly']);
        $this->get($url)->assertRedirect(route('login'));
        $this->postJson('/login', ['email' => $u->email, 'password' => 'password'])->assertOk()->assertJsonPath('redirect', $url);
        $this->get($url)->assertOk()->assertViewHas('checkoutPlan', 'equipe')->assertViewHas('checkoutPeriod', 'yearly');
    }

    public function test_pro_upgrade_waits_for_team_payment_then_replaces_subscription_once(): void
    {
        $u = User::factory()->create();
        $pro = Plan::where('slug', 'pro')->firstOrFail();
        $team = Plan::where('slug', 'equipe')->firstOrFail();
        $old = Subscription::create(['user_id' => $u->id, 'plan_id' => $pro->id, 'status' => 'active', 'expires_at' => now()->addMonth()]);
        $this->actingAs($u);
        $charge = $this->postJson('/checkout/process', ['plan_id' => $team->id, 'period' => 'yearly', 'amount' => 1])->assertOk()->assertJsonPath('amount', 49900)->json();
        $this->getJson('/account/entitlements')->assertJsonPath('slug', 'pro');
        $this->postJson($charge['simulate_url'])->assertOk()->assertJsonPath('status', 'paid');
        $this->assertSame('replaced', $old->fresh()->status);
        $features = $this->getJson('/account/entitlements')->assertOk()->assertJsonPath('slug', 'equipe')->json('features');
        foreach (['roles', 'history', 'full_reports', 'team_support'] as $feature) $this->assertContains($feature, $features);
        $this->assertSame($team->id, $u->fresh()->plan_id);
        $this->postJson($charge['simulate_url'])->assertOk();
        $this->assertSame(1, $u->subscriptions()->where('status', 'active')->count());
    }

    public function test_free_and_unknown_plans_cannot_be_purchased(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/checkout/process', ['plan_id' => Plan::where('slug', 'free')->value('id'), 'period' => 'monthly'])->assertUnprocessable();
        $this->get('/assinar/free')->assertNotFound();
        $this->getJson('/checkout/options/unknown')->assertNotFound();
    }
}
