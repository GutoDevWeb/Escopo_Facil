<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DemoNotification;
use Illuminate\Console\Command;

class CreateDemoNotification extends Command
{
    protected $signature = 'dev:notification {email?}';
    protected $description = 'Adiciona uma notificação interna de teste, sem enviar e-mail.';
    public function handle(): int
    {
        if (!app()->environment('local', 'testing')) { $this->error('Disponível somente em desenvolvimento.'); return self::FAILURE; }
        $query = User::whereNotNull('email_verified_at');
        if ($this->argument('email')) $query->where('email', $this->argument('email'));
        $count = 0;
        foreach ($query->get() as $user) {
            if ($user->notifications()->where('type', DemoNotification::class)->exists()) continue;
            $user->notify(new DemoNotification); $count++;
        }
        $this->info("Notificações de teste criadas: {$count}. Sem duplicações e sem envio de e-mail.");
        return self::SUCCESS;
    }
}
