<?php

namespace Tests\Feature;

use App\Models\{User, Project, Task, Plan, Subscription};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemInterfaceTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed(\Database\Seeders\PlanSeeder::class); }

    public function test_original_system_opens_with_real_empty_data(): void
    {
        $user = User::factory()->create(['created_at' => now()->subDays(8)]);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('sidebar-logo')->assertSee('Projetos Ativos')->assertSee('Nenhuma tarefa ainda.');
        $this->get('/projects')->assertOk()->assertSee('projetos-grid')->assertSee('Criar projeto');
        $this->get('/profile')->assertOk()->assertSee('Dados pessoais')->assertSee('Salvar avatar');
        $this->get('/system/index.php?page=projetos')->assertRedirect('/projects');
    }

    public function test_original_modals_create_project_task_and_checklist_on_free(): void
    {
        $user = User::factory()->create(['created_at' => now()->subDays(8)]);
        $this->actingAs($user)->post('/system/actions/projetos/criar.php', ['titulo' => 'Meu projeto', 'descricao' => 'Sem dados antigos'])->assertRedirect('/projects');
        $p = Project::firstOrFail();
        $this->get('/projects/'.$p->id)->assertOk()->assertSee('kanban')->assertSee('Meu projeto');
        $this->postJson('/system/actions/tarefas/criar.php', ['projeto_id' => $p->id, 'titulo' => 'Minha tarefa', 'descricao' => 'Real', 'status' => 1, 'prioridade' => 1, 'prazo' => ''])->assertOk()->assertJson(['success' => true]);
        $t = Task::firstOrFail();
        $this->postJson('/system/actions/checklist/criar.php', ['tarefa_id' => $t->id, 'texto' => 'Revisar'])->assertOk()->assertJsonPath('item.texto', 'Revisar');
        $this->getJson('/system/actions/checklist/listar.php?tarefa_id='.$t->id)->assertOk()->assertJsonCount(1, 'items');
        $item = $t->checklistItems()->firstOrFail();
        $this->postJson('/system/actions/checklist/toggle.php', ['tarefa_id' => $t->id, 'item_id' => $item->id])->assertOk()->assertJsonPath('concluido', 1);
        $this->get('/dashboard')->assertOk()->assertSee('Minha tarefa');
    }

    public function test_bridge_cannot_bypass_ownership_or_paid_features(): void
    {
        $owner = User::factory()->create(['created_at' => now()->subDays(8)]); $other = User::factory()->create(['created_at' => now()->subDays(8)]);
        $p = $owner->projects()->create(['title' => 'Private']);
        $payload = ['projeto_id' => $p->id, 'titulo' => 'Task', 'status' => 1, 'prioridade' => 3, 'prazo' => ''];
        $this->actingAs($other)->postJson('/system/actions/tarefas/criar.php', $payload)->assertForbidden();
        $this->actingAs($owner)->postJson('/system/actions/tarefas/criar.php', $payload)->assertForbidden();
        $payload['prioridade'] = 1; $payload['prazo'] = '2027-01-01';
        $this->postJson('/system/actions/tarefas/criar.php', $payload)->assertForbidden();
        $this->actingAs($other)->getJson('/system/actions/membros/buscar.php?projeto_id='.$p->id.'&q=abc')->assertForbidden();
    }

    public function test_pro_and_team_actions_use_the_project_owners_subscription(): void
    {
        $u = User::factory()->create(['created_at' => now()->subDays(8)]); $p = $u->projects()->create(['title' => 'Pro']);
        Subscription::create(['user_id' => $u->id, 'plan_id' => Plan::where('slug', 'pro')->value('id'), 'status' => 'active', 'expires_at' => now()->addMonth()]);
        $this->actingAs($u)->postJson('/system/actions/tarefas/criar.php', ['projeto_id' => $p->id, 'titulo' => 'Prioridade', 'status' => 2, 'prioridade' => 3, 'prazo' => '2027-01-01'])->assertOk();
        $this->assertDatabaseHas('tasks', ['priority' => 'high', 'status' => 'doing', 'due_date' => '2027-01-01']);
    }

    public function test_avatar_is_saved_and_unsafe_values_are_rejected(): void
    {
        $u = User::factory()->create(['created_at' => now()->subDays(8)]);
        $this->actingAs($u)->post('/system/actions/usuario/atualizar-avatar.php', ['avatar' => 'grad-ocean:rocket'])->assertRedirect('/profile');
        $this->assertSame('grad-ocean:rocket', $u->fresh()->avatar);
        $this->postJson('/system/actions/usuario/atualizar-avatar.php', ['avatar' => '#ffffff:url(evil)'])->assertUnprocessable();
    }

    public function test_native_forms_keep_validation_redirects_and_csrf_inside_the_form(): void
    {
        $u = User::factory()->create(['created_at' => now()->subDays(8)]);
        $response = $this->actingAs($u)->get('/projects')->assertOk();
        $this->assertStringContainsString('action="'.url('/system').'/actions/projetos/criar.php"', $response->getContent());
        $this->from('/projects')->post('/system/actions/projetos/criar.php', ['titulo' => ''])->assertRedirect('/projects')->assertSessionHasErrors('title');
    }

    public function test_free_cannot_clear_a_premium_deadline_through_the_rest_endpoint(): void
    {
        $u = User::factory()->create(['created_at' => now()->subDays(8)]); $p = $u->projects()->create(['title' => 'Free']);
        $t = $p->tasks()->make(['title' => 'Prazo existente', 'due_date' => '2027-01-01']); $t->user_id = $u->id; $t->save();
        $this->actingAs($u)->patchJson(route('projects.tasks.update', [$p, $t]), ['due_date' => null])->assertForbidden();
        $this->assertSame('2027-01-01', $t->fresh()->due_date->format('Y-m-d'));
    }
}
