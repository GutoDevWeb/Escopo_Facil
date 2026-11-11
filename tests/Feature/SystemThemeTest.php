<?php

namespace Tests\Feature;

use App\Models\{Plan, Subscription, User};
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_system_and_administration_pages_share_the_theme_control(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create(['is_admin' => true, 'created_at' => now()->subDays(8)]);
        $plan = Plan::where('slug', 'equipe')->firstOrFail();
        Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'expires_at' => now()->addMonth()]);
        $project = $user->projects()->create(['title' => 'Tema em todas as telas']);

        foreach (['/dashboard', '/projects', '/profile', '/projects/'.$project->id, '/projects/'.$project->id.'/report', '/projects/'.$project->id.'/history', '/admin', '/admin/subscribers', '/admin/plans', '/admin/plans/create', '/admin/plans/'.$plan->id.'/edit'] as $path) {
            $response = $this->actingAs($user)->get($path)->assertOk();
            $response->assertSee('data-theme-toggle', false)->assertSee('role="switch"', false)
                ->assertSee('Modo escuro')->assertSee('/system/assets/css/theme.css', false)->assertSee('/system/assets/js/theme.js', false)
                ->assertSee('/system/assets/images/logoV2.png', false);
            $this->assertLessThan(strpos($response->getContent(), '/system/assets/css/global.css'), strpos($response->getContent(), '/system/assets/js/theme.js'));
        }
    }

    public function test_free_accounts_have_the_same_theme_control_without_admin_access(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create(['created_at' => now()->subDays(8)]);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('data-theme-toggle', false)->assertSee('Acesse nosso site');
        $this->get('/profile')->assertOk()->assertSee('data-theme-toggle', false);
        $this->get('/admin')->assertForbidden();
    }
}
