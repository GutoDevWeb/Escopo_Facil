<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectActivityNotification extends Notification
{
    public function __construct(public array $data) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->when(config('mail.support_reply_to'), fn ($mail, $address) => $mail->replyTo($address, config('mail.from.name')))
            ->subject($this->data['title'].' - Escopo Fácil')
            ->view('emails.project-activity', [
                'name' => $notifiable->name, 'title' => $this->data['title'],
                'body' => $this->data['body'], 'url' => route('projects.index'),
            ]);
    }
}
