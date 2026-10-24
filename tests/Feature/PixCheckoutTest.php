<?php

namespace Tests\Feature;

use App\Models\PixPayment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PixCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.driver' => 'local']);
        Http::preventStrayRequests();
        $this->plan = Plan::create(['name' => 'Escopo Pro', 'slug' => 'pro', 'price_monthly' => 27.90, 'price_yearly' => 238.80]);
    }

    private function charge(string $period = 'monthly'): array
    {
        return $this->postJson('/checkout/process', ['plan_id' => $this->plan->id, 'period' => $period, 'amount' => 1])->assertOk()->json();
    }

    public function test_creating_a_charge_requires_a_verified_account(): void
    {
        $data = ['plan_id' => $this->plan->id, 'period' => 'yearly'];

        $this->postJson(route('subscription.process'), $data)->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())
            ->postJson(route('subscription.process'), $data)->assertForbidden();

        $this->assertDatabaseCount('pix_payments', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        Http::assertNothingSent();
    }

    public function test_charge_uses_server_price_reuses_pending_payment_and_does_not_activate_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $charge = $this->charge();
        $this->assertSame(2790, $charge['amount']);
        $this->assertSame('pending', $charge['status']);
        $this->assertStringStartsWith('ESCOPO-FACIL:SIMULACAO:', $charge['code']);
        $this->assertSame($charge['id'], $this->charge()['id']);
        $this->assertNull($user->fresh()->plan_id);
        $this->assertDatabaseCount('subscriptions', 0);
        Http::assertNothingSent();
    }

    public function test_annual_confirmation_activates_once_and_does_not_extend_twice(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $charge = $this->charge('yearly');
        $this->assertSame(23880, $charge['amount']);
        $this->postJson($charge['simulate_url'])->assertOk()->assertJsonPath('status', 'paid');
        $expires = Subscription::first()->expires_at;
        $this->postJson($charge['simulate_url'])->assertOk()->assertJsonPath('status', 'paid');
        $this->assertSame($this->plan->id, $user->fresh()->plan_id);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertEquals($expires, Subscription::first()->expires_at);
        $this->assertEqualsWithDelta(now()->addYear()->timestamp, strtotime($expires), 2);
    }

    public function test_other_user_cannot_read_or_confirm_a_charge(): void
    {
        $this->actingAs(User::factory()->create());
        $charge = $this->charge();
        $this->actingAs(User::factory()->create());
        $this->getJson($charge['status_url'])->assertNotFound();
        $this->postJson($charge['simulate_url'])->assertNotFound();
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_expired_charge_cannot_be_paid_and_can_be_replaced(): void
    {
        $this->actingAs(User::factory()->create());
        $charge = $this->charge();
        $this->travel(16)->minutes();
        $this->postJson($charge['simulate_url'])->assertStatus(409);
        $this->getJson($charge['status_url'])->assertJsonPath('status', 'expired');
        $this->assertNotSame($charge['id'], $this->charge()['id']);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_invalid_period_and_custom_quote_plans_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/checkout/process', ['plan_id' => $this->plan->id, 'period' => 'forever'])->assertUnprocessable();
        $team = Plan::create(['name' => 'Equipe', 'slug' => 'custom', 'price_monthly' => 49.90]);
        $this->postJson('/checkout/process', ['plan_id' => $team->id, 'period' => 'monthly'])->assertUnprocessable();
    }

    public function test_simulation_is_blocked_in_production(): void
    {
        $this->actingAs(User::factory()->create());
        $charge = $this->charge();
        $this->app->instance('env', 'production');
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->postJson($charge['simulate_url'])->assertForbidden();
        $this->postJson('/checkout/process', ['plan_id' => $this->plan->id, 'period' => 'monthly'])->assertForbidden();
    }

    public function test_abacate_dev_create_and_confirmation_use_provider_and_not_browser_status(): void
    {
        config(['payments.driver' => 'abacate', 'payments.abacate_key' => 'dev_test']);
        Http::fake([
            '*/transparents/create' => Http::response(['success' => true, 'data' => ['id' => 'pix_test_123', 'amount' => 2790, 'devMode' => true, 'brCode' => 'demo-code', 'expiresAt' => now()->addMinutes(15)->toIso8601String()]]),
            '*/transparents/simulate-payment*' => Http::response(['success' => true, 'data' => ['id' => 'pix_test_123']]),
            '*/transparents/check*' => Http::sequence()
                ->push(['success' => true, 'data' => ['id' => 'pix_test_123', 'status' => 'PENDING']])
                ->push(['success' => true, 'data' => ['id' => 'pix_test_123', 'status' => 'PAID']]),
        ]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $charge = $this->charge();
        $this->getJson($charge['status_url'])->assertJsonPath('status', 'pending');
        $this->assertNull($user->fresh()->plan_id);
        $this->postJson($charge['simulate_url'])->assertOk()->assertJsonPath('status', 'paid');
        $this->assertSame($this->plan->id, $user->fresh()->plan_id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/transparents/create') && $request['method'] === 'PIX' && $request['data']['amount'] === 2790);
    }

    public function test_production_key_and_non_dev_responses_cannot_create_a_usable_charge(): void
    {
        $this->actingAs(User::factory()->create());
        config(['payments.driver' => 'abacate', 'payments.abacate_key' => 'prod_invalid']);
        $this->postJson('/checkout/process', ['plan_id' => $this->plan->id, 'period' => 'monthly'])->assertUnprocessable();
        Http::assertNothingSent();
        config(['payments.abacate_key' => 'dev_test']);
        Http::fake(['*' => Http::response(['success' => true, 'data' => ['id' => 'live', 'devMode' => false, 'amount' => 2790, 'brCode' => 'code', 'expiresAt' => now()->addMinutes(15)->toIso8601String()]])]);
        $this->postJson('/checkout/process', ['plan_id' => $this->plan->id, 'period' => 'monthly'])->assertUnprocessable();
        $this->assertSame(0, PixPayment::whereNotNull('code')->count());
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_gateway_outage_preserves_pending_payment_and_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $charge = $this->charge();
        PixPayment::findOrFail($charge['id'])->update(['driver' => 'abacate', 'provider_id' => 'pix_test']);
        config(['payments.driver' => 'abacate', 'payments.abacate_key' => 'dev_test']);
        Http::fake(['*' => Http::response([], 503)]);
        $this->getJson($charge['status_url'])->assertStatus(503);
        $this->assertDatabaseHas('pix_payments', ['id' => $charge['id'], 'status' => 'pending']);
        $this->assertNull($user->fresh()->plan_id);
    }
}
