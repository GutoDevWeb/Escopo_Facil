<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProductionMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactional_sender_does_not_require_a_reply_mailbox(): void
    {
        config(['mail.default' => 'array', 'mail.from.address' => 'nao-responda@escopo.example', 'mail.support_reply_to' => '']);
        Mail::purge();
        $messages = [];
        Event::listen(MessageSent::class, function ($event) use (&$messages) {
            $messages[] = $event->message;
        });
        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();
        $user->notify(new ResetPassword('test-token'));
        $this->assertCount(2, $messages);
        foreach ($messages as $message) {
            $this->assertSame('nao-responda@escopo.example', $message->getFrom()[0]->getAddress());
            $this->assertEmpty($message->getReplyTo());
        }
    }

    public function test_account_messages_go_only_to_the_user_with_the_configured_sender(): void
    {
        config([
            'mail.default' => 'array',
            'mail.from.address' => 'conta@escopo.example',
            'mail.from.name' => 'Escopo Fácil',
            'mail.support_reply_to' => 'suporte@escopo.example',
        ]);
        Mail::purge();
        $messages = [];
        Event::listen(MessageSent::class, function ($event) use (&$messages) {
            $messages[] = $event->message;
        });
        $user = User::factory()->unverified()->create(['email' => 'cliente@example.test']);
        $user->sendEmailVerificationNotification();
        $user->notify(new ResetPassword('test-token'));

        $this->assertCount(2, $messages);
        foreach ($messages as $message) {
            $this->assertSame(['cliente@example.test'], array_map(fn ($address) => $address->getAddress(), $message->getTo()));
            $this->assertSame('conta@escopo.example', $message->getFrom()[0]->getAddress());
            $this->assertSame('suporte@escopo.example', $message->getReplyTo()[0]->getAddress());
            $this->assertEmpty($message->getCc());
            $this->assertEmpty($message->getBcc());
            $this->assertStringContainsString('src="cid:', $message->getHtmlBody());
            $this->assertStringNotContainsString('/assets/landing/logo.png', $message->getHtmlBody());
            $attachments = $message->getAttachments();
            $this->assertCount(1, $attachments);
            $this->assertSame('inline', $attachments[0]->getDisposition());
            $this->assertSame('image', $attachments[0]->getMediaType());
            $this->assertSame('png', $attachments[0]->getMediaSubtype());
            $this->assertSame(hash_file('sha256', public_path('assets/landing/logo.png')), hash('sha256', base64_decode($attachments[0]->bodyToString(), true)));
        }
    }
}
