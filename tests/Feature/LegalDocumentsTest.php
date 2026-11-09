<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LegalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Pessoa de teste', 'email' => 'legal@example.test', 'job_role' => 'qa-testes',
            'password' => 'teste-legal-123', 'password_confirmation' => 'teste-legal-123',
            'terms' => true, 'terms_version' => config('legal.version'), 'privacy_notice_version' => config('legal.version'),
        ], $overrides);
    }

    public function test_documents_are_public_and_identify_the_academic_project_and_contacts(): void
    {
        foreach (['legal.terms' => 'Termos e Condições de Uso', 'legal.privacy' => 'Aviso de Privacidade'] as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title)->assertSee('Centro Universitário Senac')
                ->assertSee('Guilherme Augusto Tavares Cavalcante')->assertSee('Nicollas Ferreira')
                ->assertSee(config('legal.support_email'))
                ->assertDontSee('Guilhermeaugusto@escopofacil.com.br')->assertDontSee('Nicollasramos@escopofacil.com.br')->assertDontSee('Natanoliveira@escopofacil.com.br')
                ->assertSee(config('legal.version'));
            $this->get(route($route, ['version' => config('legal.version')]))->assertOk();
        }
        $this->get(route('legal.terms'))->assertSee('Não há cobrança real');
        $this->get(route('legal.privacy'))->assertSee('não têm uma data de encerramento definida');
        $this->assertGuest();
    }

    public function test_modal_receives_a_document_fragment_without_reloading_the_auth_page(): void
    {
        $response = $this->getJson(route('legal.terms'))->assertOk()->assertJson([
            'title' => 'Termos e Condições de Uso', 'version' => config('legal.version'),
        ]);
        $this->assertStringContainsString('legal-document', $response->json('html'));
        $this->assertStringNotContainsString('<!DOCTYPE', $response->json('html'));
        $this->get('/register')->assertOk()->assertSee('id="legal-modal"', false)
            ->assertSee('name="terms_version"', false)->assertSee('name="privacy_notice_version"', false);
        $this->get('/')->assertSee('lgpd-informativo.svg')->assertSee('data-legal-document', false);
    }

    public function test_unknown_document_versions_are_not_accepted_or_rendered(): void
    {
        $this->get('/termos-de-uso/1900-01-01')->assertNotFound();
        $this->get('/privacidade/not-a-view')->assertNotFound();
        $this->postJson('/register', $this->registration(['terms_version' => '1900-01-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('terms_version');
        $this->postJson('/register', $this->registration(['privacy_notice_version' => '1900-01-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('privacy_notice_version');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_legal_acceptance_requires_the_checkbox_and_versions(): void
    {
        $this->postJson('/register', $this->registration(['terms' => false]))->assertUnprocessable()->assertJsonValidationErrors('terms');
        $data = $this->registration();
        unset($data['terms_version'], $data['privacy_notice_version']);
        $this->postJson('/register', $data)->assertUnprocessable()->assertJsonValidationErrors(['terms_version', 'privacy_notice_version']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_records_the_actual_versions_and_acceptance_time_only_for_new_accounts(): void
    {
        Notification::fake();
        $existing = User::factory()->create();
        $this->postJson('/register', $this->registration())->assertOk();
        $new = User::where('email', 'legal@example.test')->firstOrFail();
        $this->assertSame(config('legal.version'), $new->terms_version);
        $this->assertSame(config('legal.version'), $new->privacy_notice_version);
        $this->assertNotNull($new->terms_accepted_at);
        $this->assertNull($existing->fresh()->terms_version);
        $this->assertNull($existing->fresh()->privacy_notice_version);
    }
}
