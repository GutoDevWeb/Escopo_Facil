<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_contains_three_distinct_sections_and_local_assets(): void
    {
        $this->get('/')->assertOk()->assertSee('id="features"', false)
            ->assertSee('id="beneficios"', false)->assertSee('id="contato"', false)
            ->assertSee('feature-project.jpg')->assertSee('data-contact-form', false);
    }

    public function test_invalid_contact_does_not_send_a_message(): void
    {
        Mail::shouldReceive('raw')->never();
        $this->postJson('/contato', ['email' => 'invalid', 'uses_platform' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'message', 'uses_platform']);
    }

    public function test_contact_uses_configured_recipients_and_reports_local_mode(): void
    {
        config(['mail.default' => 'log', 'mail.contact_address' => 'team@example.test']);
        Mail::shouldReceive('raw')->once()->withArgs(function ($body, $callback) {
            $message = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email);
            $callback($message);
            $email = $message->getSymfonyMessage();
            return str_contains($body, 'Mensagem de teste')
                && $email->getTo()[0]->getAddress() === 'team@example.test'
                && $email->getReplyTo()[0]->getAddress() === 'visitor@example.test';
        });
        $this->postJson('/contato', [
            'name' => 'Visitante', 'email' => 'visitor@example.test',
            'message' => 'Mensagem de teste', 'uses_platform' => 'no',
        ])->assertOk()->assertJsonPath('message', 'Mensagem registrada no ambiente local de testes. Nenhum e-mail externo foi enviado.');
    }

    public function test_missing_contact_destination_does_not_send(): void
    {
        config(['mail.contact_address' => '']);
        Mail::shouldReceive('raw')->never();
        $this->postJson('/contato', [
            'name' => 'Visitante', 'email' => 'visitor@example.test',
            'message' => 'Mensagem de teste', 'uses_platform' => 'no',
        ])->assertStatus(503);
    }
}
