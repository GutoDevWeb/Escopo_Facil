<?php

namespace Tests\Feature;

use App\Models\{User, Project, Task};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp(); $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->artisan('dev:plan-demos')->assertSuccessful();
    }

    public function test_examples_are_idempotent_and_keep_edited_data(): void
    {
        $this->assertDatabaseCount('users', 5); $this->assertDatabaseCount('projects', 3); $this->assertDatabaseCount('tasks', 9);
        $task = Task::firstOrFail(); $task->update(['title' => 'Meu teste persistente']);
        $this->artisan('dev:plan-demos')->assertSuccessful();
        $this->assertDatabaseCount('projects', 3); $this->assertDatabaseCount('tasks', 9);
        $this->assertSame('Meu teste persistente', $task->fresh()->title);
    }

    public function test_each_demo_has_real_plan_permissions_and_visible_comparison(): void
    {
        foreach (['free', 'pro', 'equipe'] as $tier) {
            $u = User::where('email', "demo.{$tier}@escopofacil.test")->firstOrFail();
            $p = $u->projects()->firstOrFail(); $task = $p->tasks()->firstOrFail();
            $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('plan-feature-grid', false)->assertSee('Conta de demonstração');
            $this->getJson('/account/entitlements')->assertOk()->assertJsonPath('slug', $tier);
            $this->get(route('projects.show', $p))->assertOk()->assertSee('RECURSOS DESTE PROJETO');
            $payload = ['priority' => 'high', 'due_date' => today()->addWeek()->toDateString()];
            $this->patchJson(route('projects.tasks.update', [$p, $task]), $payload)->assertStatus($tier === 'free' ? 403 : 200);
            $this->getJson(route('projects.report', $p))->assertStatus($tier === 'free' ? 403 : 200);
            $this->getJson(route('projects.history', $p))->assertStatus($tier === 'equipe' ? 200 : 403);
        }
    }

    public function test_team_viewer_really_cannot_change_tasks(): void
    {
        $viewer = User::where('email', 'demo.leitor@escopofacil.test')->firstOrFail();
        $project = Project::whereHas('owner', fn ($q) => $q->where('email', 'demo.equipe@escopofacil.test'))->firstOrFail();
        $this->actingAs($viewer)->get(route('projects.show', $project))->assertOk()->assertSee('somente leitura');
        $this->patchJson(route('projects.tasks.update', [$project, $project->tasks()->first()]), ['title' => 'Não deve salvar'])->assertForbidden();
    }

    public function test_demo_setup_is_unavailable_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $this->artisan('dev:plan-demos')->assertFailed();
    }
}
