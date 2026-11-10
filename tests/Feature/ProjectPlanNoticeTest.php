<?php

namespace Tests\Feature;

use App\Models\{Plan, Subscription, User};
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPlanNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_notice_is_dismissible_and_returns_on_each_visit_including_after_trial(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create();
        $project = $user->projects()->create(['title' => 'Aviso temporário']);
        $this->actingAs($user);
        foreach ([0, 8] as $days) {
            $this->travel($days)->days();
            for ($visit = 0; $visit < 2; $visit++) {
                $this->get('/projects/'.$project->id)->assertOk()
                    ->assertSee('RECURSOS DESTE PROJETO')->assertSee('data-dismiss-plan-overview', false);
            }
        }
        $this->get('/profile')->assertOk()->assertSee('SEU PLANO')->assertDontSee('data-dismiss-plan-overview', false);
    }

    public function test_paid_accounts_only_have_the_plan_overview_in_settings(): void
    {
        $this->seed(PlanSeeder::class);
        foreach (['pro', 'equipe'] as $slug) {
            $user = User::factory()->create();
            $plan = Plan::where('slug', $slug)->firstOrFail();
            Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'expires_at' => now()->addMonth()]);
            $project = $user->projects()->create(['title' => 'Trabalho sem aviso']);
            $this->actingAs($user)->get('/projects/'.$project->id)->assertOk()
                ->assertDontSee('RECURSOS DESTE PROJETO')->assertDontSee('data-dismiss-plan-overview', false);
            $this->get('/profile')->assertOk()->assertSee('SEU PLANO')->assertSee($plan->name);
        }
    }
}
