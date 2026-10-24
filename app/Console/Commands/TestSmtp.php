<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSmtp extends Command
{
    protected $signature = 'mail:test-smtp {email}';
    protected $description = 'Testa o transporte SMTP sem criar ou modificar uma conta.';

    public function handle(): int
    {
        if (!app()->environment('local', 'testing')) { $this->error('Comando exclusivo de desenvolvimento.'); return self::FAILURE; }
        $email = $this->argument('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('E-mail inválido.'); return self::FAILURE; }
        if (config('mail.default') !== 'smtp') { $this->error('Configure MAIL_MAILER=smtp.'); return self::FAILURE; }
        if (config('mail.mailers.smtp.host') === 'smtp.gmail.com' && !config('mail.mailers.smtp.password')) {
            $this->error('MAIL_PASSWORD ainda está vazio. Preencha a senha de aplicativo no .env.'); return self::FAILURE;
        }
        try {
            Mail::raw('Este é um teste de entrega SMTP do Escopo Fácil. Nenhuma conta ou senha foi alterada. O fluxo de confirmação de conta usa este mesmo transporte.',
                fn ($message) => $message->to($email)->subject('Teste de envio SMTP — Escopo Fácil'));
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->error('O servidor SMTP não aceitou o envio. Confira conta Google, senha de aplicativo, remetente e conexão.');
            return self::FAILURE;
        }
        $this->info('SMTP aceitou o e-mail para '.$email.'. Confirme o recebimento na caixa de entrada ou spam.');
        return self::SUCCESS;
    }
}
