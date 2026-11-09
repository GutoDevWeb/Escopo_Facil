<?php

namespace Tests\Feature;

use App\Models\{Plan, Subscription, User};
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cron_rejects_missing_wrong_and_short_tokens_without_changing_subscriptions(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create();
        $sub = Subscription::create(['user_id' => $user->id, 'plan_id' => Plan::where('slug', 'pro')->value('id'), 'status' => 'active', 'expires_at' => now()->subDay()]);
        config(['hosting.cron_secret' => str_repeat('a', 64)]);
        $this->getJson('/internal/maintenance')->assertForbidden();
        $this->withToken('wrong')->getJson('/internal/maintenance')->assertForbidden();
        config(['hosting.cron_secret' => 'short']);
        $this->withToken('short')->getJson('/internal/maintenance')->assertForbidden();
        $this->assertSame('active', $sub->fresh()->status);
    }

    public function test_authorized_cron_expires_only_due_subscriptions_and_is_safe_to_repeat(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create();
        $values = ['user_id' => $user->id, 'plan_id' => Plan::where('slug', 'pro')->value('id'), 'status' => 'active'];
        $expired = Subscription::create($values + ['expires_at' => now()->subDay()]);
        $valid = Subscription::create($values + ['expires_at' => now()->addDay()]);
        config(['hosting.cron_secret' => str_repeat('a', 64)]);
        $this->withToken(str_repeat('a', 64))->getJson('/internal/maintenance')->assertOk()->assertJson(['ok' => true]);
        $this->getJson('/internal/maintenance')->assertOk();
        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('active', $valid->fresh()->status);
    }

    public function test_production_does_not_offer_simulated_checkout_or_fake_footer_contacts(): void
    {
        $this->seed(PlanSeeder::class);
        $this->app['env'] = 'production';
        $this->getJson('/checkout/options/equipe')->assertOk()->assertJsonPath('checkout_available', false);
        $this->get('/')->assertOk()->assertDontSee('WhatsApp demonstrativo')->assertSee('Projeto acadêmico')->assertSee('Ambiente acadêmico de simulação');
        $user = User::factory()->create();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($user)->postJson('/checkout/process', ['plan_id' => Plan::where('slug', 'pro')->value('id'), 'period' => 'monthly'])->assertForbidden();
        $this->assertDatabaseCount('pix_payments', 0);
    }

    public function test_hosting_preserves_academic_documents_and_their_versions(): void
    {
        $this->app['env'] = 'production';
        $this->assertSame('2026-10-22', config('legal.version'));
        $this->get('/termos-de-uso')->assertOk()->assertSee('Projeto acadêmico')->assertSee('Não há cobrança real');
        $this->get('/privacidade')->assertOk()->assertSee('demonstração acadêmica');
        $this->get('/termos-de-uso/2026-10-22')->assertOk()->assertSee('Projeto acadêmico');
    }

    public function test_production_check_reports_unsafe_configuration_without_leaking_credentials(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true, 'mail.mailers.smtp.password' => 'do-not-print-this-secret']);
        $this->artisan('deploy:check --configuration-only')
            ->expectsOutput('APP_DEBUG deve ser false.')
            ->assertFailed();
    }

    public function test_production_check_accepts_shared_storage_and_secure_mail_without_connecting_or_sending(): void
    {
        $this->app['env'] = 'production';
        $connection = config('database.default');
        config([
            'app.debug' => false, 'app.url' => 'https://escopofacil.com.br',
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'database.default' => 'pgsql', 'session.driver' => 'database', 'session.encrypt' => true,
            'session.secure' => true, 'session.http_only' => true, 'cache.default' => 'database',
            'queue.default' => 'sync', 'logging.default' => 'stderr', 'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.resend.com', 'mail.mailers.smtp.username' => 'resend',
            'mail.mailers.smtp.password' => 'private-test-key', 'mail.mailers.smtp.require_tls' => true,
            'mail.from.address' => 'contas@escopofacil.com.br', 'mail.contact_address' => 'suporte@escopofacil.com.br',
            'legal.support_email' => 'suporte@escopofacil.com.br', 'hosting.cron_secret' => str_repeat('b', 64),
        ]);
        try {
            $this->artisan('deploy:check --configuration-only')->assertSuccessful();
        } finally {
            config(['database.default' => $connection]);
        }
    }
}
