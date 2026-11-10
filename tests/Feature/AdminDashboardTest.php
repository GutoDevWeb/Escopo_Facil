<?php

namespace Tests\Feature;

use App\Models\{Plan, PixPayment, Project, Subscription, User};
use App\Services\UsageMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->seed(\Database\Seeders\PlanSeeder::class); }
    private function admin(): User { $u = User::factory()->create(); $u->forceFill(['is_admin' => true])->save(); return $u; }

    public function test_admin_dashboard_and_navigation_are_restricted(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertDontSee('Administração')->assertSee('Seu plano');
        $this->actingAs($this->admin())->get('/dashboard')->assertOk()->assertSee('Administração')->assertSee('sidebar-admin-label', false);
        $this->get('/admin')->assertOk()->assertSee('Visão geral do Escopo Fácil')->assertSee('Usuários cadastrados');
    }

    public function test_sessions_and_effective_plan_are_reported_without_exposing_session_secrets(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['plan_id' => Plan::where('slug', 'pro')->value('id')]); // No valid subscription: still Free.
        DB::table('sessions')->insert(['id' => 'secret-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Test', 'payload' => 'secret-payload', 'last_activity' => now()->timestamp]);
        $response = $this->actingAs($this->admin())->get('/admin')->assertOk()->assertDontSee('secret-session')->assertDontSee('secret-payload');
        $row = $response->viewData('rows')->firstWhere('user.id', $user->id);
        $this->assertTrue($row['online']);
        $this->assertSame('free', $row['plan']['slug']);
        DB::table('sessions')->where('id', 'secret-session')->update(['last_activity' => now()->subHours(5)->timestamp]);
        $response = $this->get('/admin');
        $this->assertFalse($response->viewData('rows')->firstWhere('user.id', $user->id)['session']);
    }

    public function test_paid_test_payments_are_separated_from_subscription_counts(): void
    {
        $admin = $this->admin(); $plan = Plan::where('slug', 'pro')->firstOrFail();
        Subscription::create(['user_id' => $admin->id, 'plan_id' => $plan->id, 'status' => 'active', 'expires_at' => now()->addMonth()]);
        PixPayment::create(['user_id' => $admin->id, 'plan_id' => $plan->id, 'period' => 'monthly', 'amount' => 2790, 'driver' => 'local', 'status' => 'paid', 'expires_at' => now()->addHour()]);
        PixPayment::create(['user_id' => $admin->id, 'plan_id' => $plan->id, 'period' => 'monthly', 'amount' => 9999, 'driver' => 'local', 'status' => 'pending', 'expires_at' => now()->addHour()]);
        $r = $this->actingAs($admin)->get('/admin')->assertOk(); $metrics = $r->viewData('metrics');
        $this->assertEquals(27.9, $metrics['simulated_amount']); $this->assertSame(1, $metrics['purchases']); $this->assertSame(1, $metrics['active_subscriptions']);
    }

    public function test_login_and_successful_use_are_counted(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->get('/dashboard')->assertOk();
        $row = DB::table('usage_metrics')->where('user_id', $user->id)->first();
        $this->assertSame(1, $row->logins); $this->assertGreaterThanOrEqual(1, $row->requests);
    }

    public function test_development_admin_command_is_idempotent_and_disabled_in_production(): void
    {
        config(['development.admin_email' => 'admin@escopofacil.test', 'development.admin_password' => 'Dev-test-password!2026']);
        $this->artisan('dev:create-admin')->assertSuccessful();
        $admin = User::where('email', 'admin@escopofacil.test')->firstOrFail();
        $hash = $admin->password; $this->assertTrue($admin->is_admin); $this->assertTrue($admin->hasVerifiedEmail());
        $this->artisan('dev:create-admin')->assertSuccessful(); $this->assertSame($hash, $admin->fresh()->password);
        app()->detectEnvironment(fn () => 'production');
        $this->artisan('dev:create-admin')->assertFailed();
    }
}
