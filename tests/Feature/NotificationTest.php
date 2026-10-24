<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\DemoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_require_a_verified_session(): void
    {
        $this->getJson('/notifications')->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())->getJson('/notifications')->assertForbidden();
    }

    public function test_listing_does_not_mark_notifications_read_and_never_shows_another_account(): void
    {
        $a = User::factory()->create(); $b = User::factory()->create();
        $a->notify(new DemoNotification); $b->notify(new DemoNotification);
        $this->actingAs($a)->getJson('/notifications')->assertOk()->assertJsonPath('unread_count', 1)->assertJsonCount(1, 'items')->assertJsonPath('items.0.read', false);
        $this->assertSame(1, $a->unreadNotifications()->count());
        $this->getJson('/notifications')->assertDontSee($b->notifications()->first()->id);
    }

    public function test_only_the_current_users_visible_items_can_be_marked_read_and_the_state_persists(): void
    {
        $a = User::factory()->create(); $b = User::factory()->create();
        $a->notify(new DemoNotification); $b->notify(new DemoNotification);
        $own = $a->notifications()->first()->id; $other = $b->notifications()->first()->id;
        $this->actingAs($a)->postJson('/notifications/read', ['ids' => [$other]])->assertOk()->assertJsonPath('unread_count', 1)->assertJsonCount(0, 'read_ids');
        $this->postJson('/notifications/read', ['ids' => [$own]])->assertOk()->assertJsonPath('unread_count', 0)->assertJsonPath('read_ids.0', $own);
        $this->getJson('/notifications')->assertJsonPath('unread_count', 0)->assertJsonPath('items.0.read', true);
        $this->postJson('/notifications/read', ['ids' => [$own]])->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertSame(1, $b->unreadNotifications()->count());
    }

    public function test_invalid_identifiers_are_rejected_and_deleting_the_account_removes_notifications(): void
    {
        $user = User::factory()->create(); $user->notify(new DemoNotification);
        $this->actingAs($user)->postJson('/notifications/read', ['ids' => ['invalid']])->assertUnprocessable();
        $user->delete(); $this->assertDatabaseCount('notifications', 0);
    }

    public function test_demo_command_does_not_duplicate_or_reopen_notifications(): void
    {
        $u = User::factory()->create();
        $this->artisan('dev:notification', ['email' => $u->email])->assertSuccessful();
        $u->unreadNotifications()->update(['read_at' => now()]);
        $this->artisan('dev:notification', ['email' => $u->email])->assertSuccessful();
        $this->assertSame(1, $u->notifications()->count()); $this->assertSame(0, $u->unreadNotifications()->count());
    }
}
