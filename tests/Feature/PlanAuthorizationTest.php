<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;
use App\Services\PlanAccess;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->seed(PlanSeeder::class); }

    private function account(string $tier = 'free'): User
    {
        $user = User::factory()->create(['plan_id' => Plan::where('slug', $tier)->value('id'), 'created_at' => now()->subDays(8)]);
        if ($tier !== 'free') Subscription::create(['user_id' => $user->id, 'plan_id' => $user->plan_id,
            'status' => 'active', 'expires_at' => now()->addMonth()]);
        return $user;
    }

    private function project(User $owner): Project { return $owner->projects()->create(['title' => 'Projeto de teste']); }

    public function test_paid_pointer_without_valid_subscription_does_not_grant_pro_features(): void
    {
        $user = $this->account();
        $user->update(['plan_id' => Plan::where('slug', 'equipe')->value('id')]);
        $this->actingAs($user)->getJson('/account/entitlements')->assertOk()->assertJsonPath('slug', 'free');
        $this->assertFalse(app(PlanAccess::class)->allows($user, 'history'));
    }

    public function test_expired_cancelled_and_null_expiry_subscriptions_do_not_grant_paid_access(): void
    {
        $user = $this->account('pro');
        $project = $this->project($user);
        foreach ([['status'=>'active', 'expires_at'=>now()], ['status'=>'cancelled', 'expires_at'=>now()->addYear()], ['status'=>'active','expires_at'=>null]] as $state) {
            $user->subscriptions()->update($state);
            $this->actingAs($user)->getJson(route('projects.report', $project))->assertForbidden();
        }
        $this->getJson(route('projects.tasks.index', $project))->assertOk();
    }

    public function test_only_admin_can_access_plan_crud_and_assign_subscriptions(): void
    {
        foreach (['free', 'pro', 'equipe'] as $tier) {
            $user = $this->account($tier);
            $this->actingAs($user)->get('/admin/plans')->assertForbidden();
            $this->getJson('/admin/subscribers')->assertForbidden();
            $this->patchJson('/admin/subscribers/'.$user->id, ['plan_id' => $user->plan_id])->assertForbidden();
        }
        $admin = $this->account();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin/plans')->assertOk();
        $this->get('/admin/subscribers')->assertOk();
    }

    public function test_admin_assigns_team_with_expiry_and_can_revoke_to_free(): void
    {
        $admin = $this->account(); $admin->forceFill(['is_admin' => true])->save();
        $user = $this->account(); $team = Plan::where('slug', 'equipe')->first();
        $this->actingAs($admin)->patchJson('/admin/subscribers/'.$user->id, ['plan_id'=>$team->id])->assertUnprocessable();
        $this->patchJson('/admin/subscribers/'.$user->id, ['plan_id'=>$team->id,'expires_at'=>now()->addDays(20)->toDateTimeString()])->assertOk();
        $this->assertTrue(app(PlanAccess::class)->allows($user, 'history'));
        $this->patchJson('/admin/subscribers/'.$user->id, ['plan_id'=>Plan::where('slug','free')->value('id')])->assertOk();
        $this->assertFalse(app(PlanAccess::class)->allows($user->fresh(), 'history'));
    }

    public function test_free_can_create_tasks_search_and_update_status_but_cannot_submit_paid_fields(): void
    {
        $owner = $this->account(); $project = $this->project($owner);
        $this->actingAs($owner)->postJson(route('projects.tasks.store', $project), ['title'=>'Login'])->assertCreated();
        $task = $project->tasks()->first();
        $this->patchJson(route('projects.tasks.update', [$project,$task]), ['status'=>'doing'])->assertOk()->assertJsonPath('status','doing');
        $this->getJson(route('projects.tasks.index',$project).'?search=Login')->assertOk()->assertJsonPath('total',1);
        $this->postJson(route('projects.tasks.store',$project), ['title'=>'Pago','priority'=>'high'])->assertForbidden();
        $this->patchJson(route('projects.tasks.update',[$project,$task]), ['due_date'=>'2027-01-01'])->assertForbidden();
        $this->getJson(route('projects.report',$project))->assertForbidden();
        $this->getJson(route('projects.history',$project))->assertForbidden();
    }

    public function test_pro_has_priorities_deadlines_and_simple_reports_but_not_team_history(): void
    {
        $owner = $this->account('pro'); $project = $this->project($owner);
        $this->actingAs($owner)->postJson(route('projects.tasks.store',$project), ['title'=>'Entrega','priority'=>'high','due_date'=>'2027-01-01'])->assertCreated();
        $this->getJson(route('projects.report',$project))->assertOk()->assertJsonPath('total',1)->assertJsonMissingPath('by_priority');
        $this->getJson(route('projects.history',$project))->assertForbidden();
        $this->get(route('projects.show',$project))->assertOk();
    }

    public function test_team_history_is_persistent_and_full_reports_are_available(): void
    {
        $owner=$this->account('equipe'); $project=$this->project($owner);
        $this->actingAs($owner)->postJson(route('projects.tasks.store',$project), ['title'=>'Original'])->assertCreated();
        $task=$project->tasks()->first();
        $this->patchJson(route('projects.tasks.update',[$project,$task]),['title'=>'Alterada','status'=>'done'])->assertOk();
        $this->deleteJson(route('projects.tasks.destroy',[$project,$task]))->assertNoContent();
        $this->getJson(route('projects.history',$project))->assertOk()->assertJsonPath('total',3)->assertJsonPath('data.0.action','deleted');
        $this->getJson(route('projects.report',$project))->assertOk()->assertJsonStructure(['by_priority','overdue']);
        $this->get(route('projects.history',$project))->assertOk();
    }

    public function test_project_and_task_ids_do_not_allow_cross_account_or_cross_project_access(): void
    {
        $owner=$this->account('equipe'); $project=$this->project($owner); $task=$project->tasks()->create(['title'=>'Privada']);
        $outsider=$this->account('equipe'); $other=$this->project($outsider);
        $this->actingAs($outsider)->getJson(route('projects.show',$project))->assertForbidden();
        $this->getJson('/projects')->assertJsonPath('total',1);
        $this->patchJson(route('projects.update',$project),['title'=>'Ataque'])->assertForbidden();
        $this->patchJson(route('projects.tasks.update',[$other,$task]),['status'=>'done'])->assertNotFound();
        $this->postJson(route('projects.members.store',$project),['email'=>$outsider->email])->assertForbidden();
        $this->deleteJson(route('projects.destroy',$project))->assertForbidden();
        $this->getJson(route('projects.history',$project))->assertForbidden();
    }

    public function test_roles_are_enforced_and_only_owner_can_appoint_a_manager(): void
    {
        $owner=$this->account('equipe'); $project=$this->project($owner);
        $viewer=$this->account(); $manager=$this->account(); $candidate=$this->account();
        $this->actingAs($owner)->postJson(route('projects.members.store',$project),['email'=>$viewer->email,'role'=>'viewer'])->assertCreated();
        $this->postJson(route('projects.members.store',$project),['email'=>$manager->email,'role'=>'manager'])->assertCreated();
        $this->actingAs($viewer)->getJson(route('projects.show',$project))->assertOk();
        $this->postJson(route('projects.tasks.store',$project),['title'=>'Proibida'])->assertForbidden();
        $this->actingAs($manager)->postJson(route('projects.members.store',$project),['email'=>$candidate->email,'role'=>'manager'])->assertForbidden();
        $this->postJson(route('projects.members.store',$project),['email'=>$candidate->email])->assertCreated();
        $this->patchJson(route('projects.members.update',[$project,$candidate]),['role'=>'manager'])->assertForbidden();
        $this->deleteJson(route('projects.destroy',$project))->assertForbidden();
    }

    public function test_pro_cannot_assign_advanced_roles_and_removed_member_loses_access(): void
    {
        $owner=$this->account('pro'); $project=$this->project($owner); $member=$this->account();
        $this->actingAs($owner)->postJson(route('projects.members.store',$project),['email'=>$member->email,'role'=>'manager'])->assertForbidden();
        $this->postJson(route('projects.members.store',$project),['email'=>$member->email])->assertCreated();
        $this->deleteJson(route('projects.members.destroy',[$project,$member]))->assertNoContent();
        $this->actingAs($member)->getJson(route('projects.tasks.index',$project))->assertForbidden();
    }

    public function test_project_and_member_limits_are_enforced_server_side(): void
    {
        $owner=$this->account('pro'); $owner->plan->update(['limits'=>['projects'=>1,'members'=>2]]);
        $this->actingAs($owner)->postJson('/projects',['title'=>'Primeiro','user_id'=>999])->assertCreated()->assertJsonPath('user_id',$owner->id);
        $this->postJson('/projects',['title'=>'Segundo'])->assertUnprocessable();
        $project=$owner->projects()->first();
        $this->postJson(route('projects.members.store',$project),['email'=>$this->account()->email])->assertCreated();
        $this->postJson(route('projects.members.store',$project),['email'=>$this->account()->email])->assertUnprocessable();
    }

    public function test_checklist_ids_are_scoped_and_archived_projects_cannot_be_changed(): void
    {
        $owner=$this->account(); $project=$this->project($owner);
        $task=$project->tasks()->create(['title'=>'Um']); $other=$project->tasks()->create(['title'=>'Dois']);
        $item=$other->checklistItems()->create(['title'=>'Item']);
        $this->actingAs($owner)->patchJson(route('projects.checklist.update',[$project,$task,$item]),['completed'=>true])->assertNotFound();
        $this->postJson(route('projects.checklist.store',[$project,$task]),['title'=>'Etapa'])->assertCreated();
        $project->update(['archived'=>true]);
        $this->patchJson(route('projects.tasks.update',[$project,$task]),['status'=>'done'])->assertStatus(409);
        $this->get(route('projects.index'))->assertOk();
    }

    public function test_registration_cannot_choose_a_paid_plan_or_become_admin(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->postJson('/register', ['name'=>'Teste', 'email'=>'new@example.test', 'job_role'=>'dev-backend',
            'password'=>'Password123!', 'password_confirmation'=>'Password123!', 'terms'=>true,
            'terms_version'=>config('legal.version'), 'privacy_notice_version'=>config('legal.version'),
            'plan_id'=>Plan::where('slug','equipe')->value('id'), 'is_admin'=>true])->assertOk();
        $user=User::where('email','new@example.test')->first();
        $this->assertSame('free', $user->plan->slug);
        $this->assertFalse($user->is_admin);
        $this->assertDatabaseCount('subscriptions',0);
    }

    public function test_free_continues_after_the_full_trial_without_blocking_profile_or_checkout(): void
    {
        $user=$this->account(); $user->forceFill(['created_at'=>now()->subDays(8)])->save();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        config(['entitlements.free_trial_days'=>7]);
        $this->getJson('/projects')->assertOk();
        $this->get('/profile')->assertOk();
        $this->get('/assinar/pro')->assertOk();
    }

    public function test_owner_plan_controls_shared_project_features_and_viewer_stays_read_only_after_expiry(): void
    {
        $free=$this->account(); $project=$this->project($free); $pro=$this->account('pro');
        $project->members()->attach($pro->id,['role'=>'member']);
        $this->actingAs($pro)->postJson(route('projects.tasks.store',$project),['title'=>'Tarefa','priority'=>'high'])->assertForbidden();
        $this->postJson(route('projects.tasks.store',$project),['title'=>'Tarefa simples'])->assertCreated();
        $team=$this->account('equipe'); $teamProject=$this->project($team); $viewer=$this->account();
        $teamProject->members()->attach($viewer->id,['role'=>'viewer']);
        $team->subscriptions()->update(['expires_at'=>now()->subSecond()]);
        $this->actingAs($viewer)->postJson(route('projects.tasks.store',$teamProject),['title'=>'Ataque'])->assertForbidden();
    }

    public function test_expiry_command_and_team_support_are_protected_by_real_entitlements(): void
    {
        $pro=$this->account('pro');
        $this->actingAs($pro)->getJson('/account/team-support')->assertForbidden();
        $team=$this->account('equipe');
        $this->actingAs($team)->getJson('/account/team-support')->assertOk()->assertJsonPath('email',config('legal.support_email'));
        $this->travel(32)->days();
        $this->artisan('subscriptions:expire')->assertSuccessful();
        $this->assertDatabaseMissing('subscriptions',['status'=>'active']);
        $this->getJson('/account/team-support')->assertForbidden();
        $this->travelBack();
    }
}
