<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestVerificationMail extends Command
{
    protected $signature = 'mail:test-verification {email}';
    protected $description = 'Envia a confirmação de e-mail real para uma conta de teste não confirmada.';

    public function handle(): int
    {
        if (!app()->environment('local', 'testing')) { $this->error('Use este comando apenas em desenvolvimento.'); return self::FAILURE; }
        if (config('mail.default') !== 'smtp') { $this->error('Configure MAIL_MAILER=smtp para testar entrega SMTP.'); return self::FAILURE; }
        if (config('mail.mailers.smtp.host') === 'smtp.gmail.com' && !config('mail.mailers.smtp.password')) {
            $this->error('Preencha MAIL_PASSWORD com a senha de aplicativo no .env e execute php artisan config:clear.');
            return self::FAILURE;
        }
        $email = mb_strtolower($this->argument('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('E-mail inválido.'); return self::FAILURE; }
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error('Cadastre primeiro essa conta pelo site. O teste não cria contas nem modifica senhas.');
            return self::FAILURE;
        }
        if ($user->hasVerifiedEmail()) { $this->error('A conta já está confirmada. Use uma conta de teste não confirmada.'); return self::FAILURE; }
        try { $user->sendEmailVerificationNotification(); }
        catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->error('O servidor SMTP não aceitou o envio. Confira usuário, senha de aplicativo, remetente e acesso de rede.');
            return self::FAILURE;
        }
        $this->info('Servidor SMTP aceitou a mensagem para '.$email.'. Confira a caixa de entrada e o spam.');
        return self::SUCCESS;
    }
}
