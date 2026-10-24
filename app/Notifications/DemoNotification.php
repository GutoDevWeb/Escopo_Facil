<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class DemoNotification extends Notification
{
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Suas notificações estão prontas!',
            'body' => 'Esta é uma notificação de teste do Escopo Fácil. Ao visualizá-la aqui, ela será marcada como lida e a bolinha vermelha desaparecerá quando não houver outras novidades.'];
    }
}
