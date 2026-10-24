<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.driver' => 'local']);
        Http::preventStrayRequests();
    }

    private function pro(): Plan
    {
        return Plan::create([
            'name' => 'Escopo Pro',
            'slug' => 'pro',
            'price_monthly' => 27.90,
            'price_yearly' => 238.80,
        ]);
    }

    public function test_guest_can_open_the_checkout_over_the_landing_without_a_plan_record(): void
    {
        $this->get(route('subscription.start', ['period' => 'yearly']))
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertViewHas('checkoutOpen', true)
            ->assertViewHas('checkoutPeriod', 'yearly')
            ->assertSee('id="checkout-modal"', false)
            ->assertSee('assets/landing/checkout-modal.css', false)
            ->assertSee(route('subscription.options'), false)
            ->assertSee('id="login-modal"', false);

        $this->assertGuest();
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('pix_payments', 0);
        Http::assertNothingSent();
    }

    public function test_plan_options_return_a_friendly_service_error_instead_of_a_missing_page(): void
    {
        $response = $this->getJson(route('subscription.options'))
            ->assertServiceUnavailable()
            ->assertJsonStructure(['message']);

        $this->assertNotEmpty($response->json('message'));
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('pix_payments', 0);
        Http::assertNothingSent();
    }

    public function test_guest_can_read_server_prices_without_creating_a_payment_or_exposing_an_account(): void
    {
        $plan = $this->pro();

        $this->getJson(route('subscription.options'))->assertOk()->assertJson([
            'plan' => [
                'id' => $plan->id,
                'name' => 'Escopo Pro',
                'monthly' => 2790,
                'yearly' => 23880,
            ],
            'authenticated' => false,
            'verified' => false,
        ])->assertJsonStructure(['csrf']);

        $this->assertDatabaseCount('pix_payments', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_options_follow_server_prices_and_account_verification_state(): void
    {
        $plan = $this->pro();
        $plan->update(['price_monthly' => 31.25, 'price_yearly' => 275.50]);
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $this->getJson(route('subscription.options', ['amount' => 1]))
            ->assertOk()->assertJson([
                'plan' => ['monthly' => 3125, 'yearly' => 27550],
                'authenticated' => true,
                'verified' => false,
                'email' => $user->email,
            ]);

        $user->markEmailAsVerified();
        $this->getJson(route('subscription.options'))->assertOk()->assertJsonPath('verified', true);
        $this->assertDatabaseCount('pix_payments', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        Http::assertNothingSent();
    }

    public function test_direct_authenticated_checkout_opens_the_same_modal_with_the_requested_period(): void
    {
        $plan = $this->pro();
        $this->actingAs(User::factory()->create());

        $this->get(route('subscription.checkout', ['plan' => $plan, 'period' => 'yearly']))
            ->assertOk()->assertViewIs('welcome')
            ->assertViewHas('checkoutOpen', true)
            ->assertViewHas('checkoutPeriod', 'yearly')
            ->assertSee('id="checkout-modal"', false);

        $this->assertDatabaseCount('pix_payments', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        Http::assertNothingSent();
    }

    public function test_custom_quote_plan_does_not_open_a_purchase_checkout(): void
    {
        $team = Plan::create(['name' => 'Escopo Equipe', 'slug' => 'custom', 'price_monthly' => 49.90]);

        $this->actingAs(User::factory()->create())
            ->get(route('subscription.checkout', ['plan' => $team]))->assertNotFound();

        $this->assertDatabaseCount('pix_payments', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }
}
