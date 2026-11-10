<?php

namespace Tests\Feature;

use App\Models\{Project, User};
use App\Notifications\ProjectActivityNotification;
use App\Services\{ProjectActivity, TaskDeadlineNotifications};
use Carbon\CarbonImmutable;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\{DB, Event, Notification};
use Tests\TestCase;

class ProjectActivityTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    private function team(): array
    {
        $owner = User::factory()->create(['name' => 'Ana']);
        $member = User::factory()->create(['name' => 'Bruno']);
        $project = $owner->projects()->create(['title' => 'Site institucional']);
        $project->members()->attach($member, ['role' => 'member']);
        return [$owner, $member, $project];
    }

    public function test_invitation_persists_and_sends_an_email_with_actor_and_project_without_duplicates(): void
    {
        [$owner, $member, $project] = $this->team();
        $invitee = User::factory()->create(['name' => 'Carla']);
        $messages = [];
        Event::listen(MessageSent::class, function ($event) use (&$messages) { $messages[] = $event->message; });
        $this->actingAs($owner)->postJson(route('projects.members.store', $project), ['email' => $invitee->email])->assertCreated();
        $notice = $invitee->notifications()->firstOrFail();
        $this->assertStringContainsString('Ana convidou você', $notice->data['body']);
        $this->assertStringContainsString('Site institucional', $notice->data['body']);
        $this->assertNotNull($notice->mail_sent_at);
        $this->assertFalse((bool) $notice->mail_pending);
        $this->assertCount(1, $messages);
        $this->assertSame($invitee->email, $messages[0]->getTo()[0]->getAddress());
        $this->assertStringContainsString('Ana convidou', $messages[0]->getHtmlBody());
        $this->assertSame(1, $member->notifications()->count());
        $this->assertSame(0, $owner->notifications()->count());
        $this->postJson(route('projects.members.store', $project), ['email' => $invitee->email])->assertUnprocessable();
        $this->assertSame(1, $invitee->notifications()->count());
        $this->assertCount(1, $messages);
    }

    public function test_mail_failure_does_not_undo_membership_and_is_retried_without_duplicating_the_notice(): void
    {
        [$owner, , $project] = $this->team();
        $invitee = User::factory()->create();
        Notification::shouldReceive('sendNow')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->actingAs($owner)->postJson(route('projects.members.store', $project), ['email' => $invitee->email])->assertCreated();
        $this->assertTrue($project->members()->where('users.id', $invitee->id)->exists());
        $notice = $invitee->notifications()->firstOrFail();
        $this->assertTrue((bool) $notice->mail_pending);
        $this->assertNull($notice->mail_sent_at);
        $this->travel(16)->minutes();
        Notification::fake();
        app(ProjectActivity::class)->retryMail();
        Notification::assertSentTo($invitee, ProjectActivityNotification::class);
        $this->assertNotNull($notice->fresh()->mail_sent_at);
        $this->assertSame(1, $invitee->notifications()->count());
    }

    public function test_reading_the_bell_does_not_clear_project_updates_but_opening_tasks_does_and_new_updates_return(): void
    {
        [$owner, $member, $project] = $this->team();
        $other = User::factory()->create();
        $this->actingAs($owner)->postJson(route('projects.tasks.store', $project), ['title' => 'Revisar layout'])->assertCreated();
        $task = $project->tasks()->firstOrFail();
        $this->actingAs($member)->getJson('/notifications')->assertJsonPath('project_updates.0', $project->id)->assertJsonPath('unread_count', 1);
        $notice = $member->notifications()->firstOrFail();
        $this->postJson('/notifications/read', ['ids' => [$notice->id]])->assertOk();
        $this->getJson('/notifications')->assertJsonPath('unread_count', 0)->assertJsonPath('project_updates.0', $project->id);
        $this->get('/projects')->assertSee('data-project-update-dot', false);
        $this->get(route('projects.show', $project))->assertOk();
        $this->getJson('/notifications')->assertJsonCount(0, 'project_updates');
        $this->assertNotNull($notice->fresh()->project_seen_at);
        $this->actingAs($owner)->patchJson(route('projects.tasks.update', [$project, $task]), ['status' => 'done'])->assertOk();
        $this->actingAs($member)->getJson('/notifications')->assertJsonPath('project_updates.0', $project->id)->assertJsonPath('unread_count', 1);
        $this->actingAs($other)->getJson('/notifications')->assertJsonCount(0, 'items')->assertJsonCount(0, 'project_updates');
        $this->get(route('projects.show', $project))->assertForbidden();
        $this->assertSame(1, $member->notifications()->whereNull('project_seen_at')->count());
    }

    public function test_removed_member_keeps_a_clear_notice_and_cannot_access_further_updates(): void
    {
        [$owner, $member, $project] = $this->team();
        $this->actingAs($owner)->deleteJson(route('projects.members.destroy', [$project, $member]))->assertNoContent();
        $this->assertSame('Ana removeu você do projeto "Site institucional". Você não tem mais acesso a esse projeto.', $member->notifications()->first()->data['body']);
        $this->postJson(route('projects.tasks.store', $project), ['title' => 'Privada'])->assertCreated();
        $this->assertSame(1, $member->notifications()->count());
        $this->actingAs($member)->get(route('projects.show', $project))->assertForbidden();
        $this->getJson('/notifications')->assertJsonCount(1, 'items')->assertJsonCount(0, 'project_updates');
    }

    public function test_project_managers_can_delete_but_members_viewers_and_site_admins_without_membership_cannot(): void
    {
        [$owner, $member, $project] = $this->team();
        $manager = User::factory()->create(['name' => 'Carla']);
        $viewer = User::factory()->create();
        $outsider = User::factory()->create(['is_admin' => true]);
        $project->members()->attach($manager, ['role' => 'manager']);
        $project->members()->attach($viewer, ['role' => 'viewer']);
        $task = $project->tasks()->create(['title' => 'Entrega']);
        $task->checklistItems()->create(['title' => 'Revisar']);
        foreach ([$member, $viewer, $outsider] as $user) $this->actingAs($user)->deleteJson(route('projects.destroy', $project))->assertForbidden();
        $this->actingAs($manager)->get('/projects')->assertSee('"can_delete":true', false)->assertSee('Excluir projeto')->assertSee('Esta ação não pode ser desfeita.');
        $this->deleteJson(route('projects.destroy', $project))->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseCount('checklist_items', 0);
        $this->assertSame('Carla excluiu o projeto "Site institucional" e suas tarefas.', $owner->notifications()->first()->data['body']);
        $this->assertSame(1, $member->notifications()->count());
    }

    public function test_project_task_checklist_and_role_changes_notify_other_participants_and_ignore_no_ops(): void
    {
        [$owner, $member, $project] = $this->team();
        $this->actingAs($owner)->patchJson(route('projects.update', $project), ['description' => 'Novo escopo'])->assertOk();
        $this->patchJson(route('projects.update', $project), ['description' => 'Novo escopo'])->assertOk();
        $this->assertSame(1, $member->notifications()->count());
        $this->postJson(route('projects.tasks.store', $project), ['title' => 'Entrega'])->assertCreated();
        $task = $project->tasks()->firstOrFail();
        $this->postJson(route('projects.checklist.store', [$project, $task]), ['title' => 'Revisão'])->assertCreated();
        $item = $task->checklistItems()->firstOrFail();
        $this->patchJson(route('projects.checklist.update', [$project, $task, $item]), ['completed' => true])->assertOk();
        $this->deleteJson(route('projects.checklist.destroy', [$project, $task, $item]))->assertNoContent();
        $this->patchJson(route('projects.members.update', [$project, $member]), ['role' => 'manager'])->assertOk();
        $this->deleteJson(route('projects.tasks.destroy', [$project, $task]))->assertNoContent();
        $this->assertSame(7, $member->notifications()->count());
        $this->assertSame(0, $owner->notifications()->count());
        $this->assertStringContainsString('para Admin', $member->notifications()->where('data->kind', 'member_role')->first()->data['body']);
    }

    public function test_deadlines_use_the_last_24_hours_of_the_brazilian_due_date_and_are_deduplicated(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 02:59:58', 'UTC')); // 10/11 23:59:58 in Brazil
        [$owner, $member, $project] = $this->team();
        $today = $project->tasks()->create(['title' => 'Hoje', 'due_date' => '2026-11-10']);
        $tomorrow = $project->tasks()->create(['title' => 'Amanhã', 'due_date' => '2026-11-11']);
        $project->tasks()->create(['title' => 'Concluída', 'status' => 'done', 'due_date' => '2026-11-10']);
        $project->tasks()->create(['title' => 'Vencida', 'due_date' => '2026-11-09']);
        $project->tasks()->create(['title' => 'Sem prazo']);
        $archived = $owner->projects()->create(['title' => 'Arquivado', 'archived' => true]);
        $archived->tasks()->create(['title' => 'Ignorada', 'due_date' => '2026-11-10']);
        app(TaskDeadlineNotifications::class)->send();
        app(TaskDeadlineNotifications::class)->send();
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame($today->id, $member->notifications()->first()->data['task_id']);
        $this->travel(2)->seconds(); // Tomorrow is now within the 24-hour window.
        $this->actingAs($member)->getJson('/notifications')->assertOk();
        $this->assertSame(2, $member->notifications()->count());
        $this->assertSame(1, $owner->notifications()->count()); // Browser check is scoped to the current user.
        $this->artisan('notifications:deliver')->assertSuccessful();
        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame(2, $member->notifications()->count());
        $tomorrow->update(['due_date' => '2026-11-12']);
        $this->travel(1)->days();
        app(TaskDeadlineNotifications::class)->send();
        $this->assertSame(3, $member->notifications()->count());
    }

    public function test_rolled_back_changes_do_not_send_or_persist_notifications(): void
    {
        [$owner, $member, $project] = $this->team();
        Notification::fake();
        DB::beginTransaction();
        app(ProjectActivity::class)->publish($project, $owner, 'member_added', 'Convite', $member, 'Convite', true);
        DB::rollBack();
        Notification::assertNothingSent();
        $this->assertSame(0, $member->notifications()->count());
    }

    public function test_creating_a_task_due_today_alerts_everyone_without_waiting_for_the_daily_cron(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 16:00:00', 'America/Sao_Paulo'));
        [$owner, $member, $project] = $this->team();
        $this->actingAs($owner)->postJson(route('projects.tasks.store', $project), ['title' => 'Entrega hoje', 'due_date' => '2026-11-10'])->assertCreated();
        $this->assertSame(1, $owner->notifications()->where('data->kind', 'deadline')->count());
        $this->assertSame(1, $member->notifications()->where('data->kind', 'deadline')->count());
        $this->assertSame(1, $member->notifications()->where('data->kind', 'task_created')->count());
        $this->getJson('/notifications')->assertOk();
        $this->artisan('notifications:deliver')->assertSuccessful();
        $this->assertSame(1, $member->notifications()->where('data->kind', 'deadline')->count());
    }

    public function test_a_pending_invitation_email_is_not_sent_after_the_member_is_removed(): void
    {
        [$owner, $member, $project] = $this->team();
        DB::beginTransaction();
        app(ProjectActivity::class)->publish($project, $owner, 'member_added', 'Convite', $member, 'Convite', true);
        $project->members()->detach($member);
        Notification::fake();
        DB::commit();
        app(ProjectActivity::class)->retryMail();
        Notification::assertNothingSent();
        $this->assertFalse((bool) $member->notifications()->first()->mail_pending);
    }

    public function test_compatibility_routes_notify_about_self_departure_and_protect_removed_members(): void
    {
        [$owner, $member, $project] = $this->team();
        $this->actingAs($member)->postJson('/system/actions/membros/remover.php', ['projeto_id' => $project->id, 'membro_id' => $member->id])->assertOk();
        $this->assertSame('Bruno saiu do projeto "Site institucional".', $owner->notifications()->first()->data['body']);
        $this->postJson('/system/actions/projetos/editar.php', ['projeto_id' => $project->id, 'titulo' => 'Inválido', 'descricao' => 'Sem acesso'])->assertForbidden();
        $this->assertSame(1, $owner->notifications()->count());
    }
}
