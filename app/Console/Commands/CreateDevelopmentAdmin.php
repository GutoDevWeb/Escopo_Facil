<?php

namespace App\Console\Commands;

use App\Models\{User, Plan};
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateDevelopmentAdmin extends Command
{
    protected $signature = 'dev:create-admin';
    protected $description = 'Cria uma conta dedicada de administração somente no ambiente local.';

    public function handle(): int
    {
        if (!app()->environment('local', 'testing')) { $this->error('Comando disponível somente em desenvolvimento.'); return self::FAILURE; }
        $email = mb_strtolower(config('development.admin_email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('DEV_ADMIN_EMAIL inválido.'); return self::FAILURE; }
        if (User::where('email', $email)->exists()) { $this->warn('Conta já existe; senha e permissões preservadas.'); return self::SUCCESS; }
        $password = config('development.admin_password') ?: Str::password(24);
        if (strlen($password) < 12) { $this->error('DEV_ADMIN_PASSWORD deve conter pelo menos 12 caracteres.'); return self::FAILURE; }
        $u = new User(['name' => 'Admin de Testes', 'email' => $email, 'password' => $password,
            'plan_id' => Plan::where('slug', 'free')->value('id')]);
        $u->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();
        $this->info('Admin de desenvolvimento criado: '.$email);
        if (!config('development.admin_password')) $this->line('Senha gerada (guarde em local seguro): '.$password);
        return self::SUCCESS;
    }
}
