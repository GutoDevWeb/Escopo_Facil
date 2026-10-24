<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmtpSetupTest extends TestCase
{
    public function test_gmail_test_requires_the_application_password_without_sending(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.gmail.com', 'mail.mailers.smtp.password' => null]);
        $this->artisan('mail:test-smtp', ['email' => 'example@gmail.com'])->expectsOutput('MAIL_PASSWORD ainda está vazio. Preencha a senha de aplicativo no .env.')->assertFailed();
        Mail::assertNothingOutgoing();
    }

    public function test_mail_diagnostic_commands_are_disabled_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $this->artisan('mail:test-smtp', ['email' => 'example@gmail.com'])->assertFailed();
        $this->artisan('mail:test-verification', ['email' => 'example@gmail.com'])->assertFailed();
    }
}
