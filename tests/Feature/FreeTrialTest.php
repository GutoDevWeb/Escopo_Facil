<?php

namespace Tests\Feature;

use App\Models\{Plan, User};
use App\Services\PlanAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeTrialTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed(\Database\Seeders\PlanSeeder::class); }

    public function test_new_free_account_has_all_features_without_administration(): void
    {
        $user = User::factory()->create(['created_at' => now()]);
        $project = $user->projects()->create(['title' => 'Teste de sete dias']);
        $this->actingAs($user)->getJson('/account/entitlements')->assertOk()->assertJsonPath('slug', 'free')->assertJsonPath('trial_active', true);
        foreach (config('entitlements.features.equipe') as $feature) $this->assertTrue(app(PlanAccess::class)->allows($user, $feature), $feature);
        $this->postJson(route('projects.tasks.store', $project), ['title' => 'Todos os recursos', 'priority' => 'high', 'due_date' => today()->addDay()->toDateString()])->assertCreated();
        $this->getJson(route('projects.report', $project))->assertOk()->assertJsonStructure(['by_priority', 'overdue']);
        $this->getJson(route('projects.history', $project))->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_exact_expiry_locks_premium_features_but_preserves_data_and_free_access(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create(['created_at' => now()]);
        $project = $user->projects()->create(['title' => 'Persistente']);
        $this->actingAs($user);
        $task = $this->postJson(route('projects.tasks.store', $project), ['title' => 'Prazo do teste', 'priority' => 'high', 'due_date' => today()->addDays(10)->toDateString()])->assertCreated()->json();
        $this->travel(7)->days();
        $this->getJson('/account/entitlements')->assertOk()->assertJsonPath('trial_active', false)->assertJsonPath('active', true)->assertJsonPath('slug', 'free');
        $this->get('/dashboard')->assertOk(); $this->get('/profile')->assertOk();
        $this->getJson(route('projects.report', $project))->assertForbidden();
        $this->getJson(route('projects.history', $project))->assertForbidden();
        $this->patchJson(route('projects.tasks.update', [$project, $task['id']]), ['priority' => 'low'])->assertForbidden();
        $this->patchJson(route('projects.tasks.update', [$project, $task['id']]), ['status' => 'done'])->assertOk();
        $this->assertDatabaseHas('tasks', ['id' => $task['id'], 'title' => 'Prazo do teste', 'priority' => 'high']);
        $this->assertSame(0, $user->subscriptions()->count());
    }

    public function test_plan_panel_is_in_settings_and_the_banner_is_free_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('plan-feature-grid', false)->assertSee('sidebar-banner', false);
        $this->get('/profile')->assertOk()->assertSee('plan-feature-grid', false)->assertSee('Teste completo ativo');
        \App\Models\Subscription::create(['user_id' => $user->id, 'plan_id' => Plan::where('slug', 'pro')->value('id'), 'status' => 'active', 'expires_at' => now()->addMonth()]);
        $this->get('/dashboard')->assertOk()->assertDontSee('class="sidebar-banner"', false);
        $this->get('/profile')->assertOk()->assertDontSee('Teste completo ativo');
    }
}
