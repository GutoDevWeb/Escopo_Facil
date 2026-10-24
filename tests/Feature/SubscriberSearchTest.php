<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_subscribers_by_name_or_email_and_clear_search(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $target = User::factory()->create(['name' => 'Assinante Especial', 'email' => 'cliente@example.test']);
        $other = User::factory()->create();

        foreach (['Especial', 'cliente@example.test'] as $search) {
            $response = $this->actingAs($admin)->get('/admin/subscribers?search='.urlencode($search))->assertOk();
            $this->assertSame([$target->id], $response->viewData('users')->pluck('id')->all());
        }
        $this->get('/admin/subscribers?search=inexistente')->assertOk()->assertSee('Nenhum usuário encontrado.');
        $response = $this->get('/admin/subscribers?search=')->assertOk();
        $this->assertSame(3, $response->viewData('users')->total());
    }

    public function test_search_is_preserved_on_pagination_and_requires_admin(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        User::factory()->count(26)->create(['name' => 'Cliente pesquisado']);
        $response = $this->actingAs($admin)->get('/admin/subscribers?search=Cliente')->assertOk();
        $this->assertSame(26, $response->viewData('users')->total());
        $this->assertStringContainsString('search=Cliente', $response->viewData('users')->nextPageUrl());
        $this->getJson('/admin/subscribers?search='.str_repeat('a', 201))->assertUnprocessable();
        $this->actingAs(User::factory()->create())->get('/admin/subscribers?search=Cliente')->assertForbidden();
    }
}
