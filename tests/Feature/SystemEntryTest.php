<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->seed(\Database\Seeders\PlanSeeder::class); }

    public function test_dashboard_requires_an_authenticated_account(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_unverified_login_keeps_the_user_in_the_verification_flow(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJson([
            'screen' => 'verify-email',
            'authenticated' => true,
            'verified' => false,
        ]);

        $this->assertArrayNotHasKey('redirect', $response->json());
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_verified_modal_login_redirects_to_the_protected_dashboard(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJson([
            'redirect' => route('dashboard'),
            'authenticated' => true,
            'verified' => true,
        ]);

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk()->assertViewIs('dashboard');
    }

    public function test_login_preserves_the_checkout_and_billing_period_requested_as_a_guest(): void
    {
        $plan = Plan::firstOrCreate(['slug' => 'pro'], [
            'name' => 'Escopo Pro',
            'slug' => 'pro',
            'price_monthly' => 27.90,
            'price_yearly' => 238.80,
        ]);
        $checkout = route('subscription.checkout', ['plan' => $plan, 'period' => 'yearly']);

        $this->get($checkout)->assertRedirect(route('login'));
        $user = User::factory()->create();

        $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('redirect', $checkout);

        $this->get($checkout)->assertOk()->assertViewIs('welcome')
            ->assertViewHas('checkoutOpen', true)->assertViewHas('checkoutPeriod', 'yearly');
    }

    public function test_invalid_credentials_do_not_return_a_system_redirect(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertArrayNotHasKey('redirect', $response->json());
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_landing_and_direct_login_include_the_existing_system_loading_animation(): void
    {
        foreach (['/', route('login')] as $page) {
            $this->get($page)
                ->assertOk()
                ->assertSee('id="system-entry"', false)
                ->assertSee('assets/system/login-transition.webm', false);
        }
    }
}
