<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDeployment extends Command
{
    protected $signature = 'deploy:check {--configuration-only : Do not connect to the database}';
    protected $description = 'Valida a configuração de produção sem exibir credenciais nem enviar e-mails.';

    public function handle(): int
    {
        $errors = [];
        $require = function (bool $condition, string $message) use (&$errors) {
            if (! $condition) { $errors[] = $message; }
        };
        $require(app()->environment('production'), 'APP_ENV deve ser production.');
        $require(! config('app.debug'), 'APP_DEBUG deve ser false.');
        $url = (string) config('app.url');
        $require(str_starts_with($url, 'https://') && (bool) filter_var($url, FILTER_VALIDATE_URL) && ! str_contains($url, '.example'), 'APP_URL deve ser a URL HTTPS pública.');
        $key = (string) config('app.key');
        $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $require(is_string($key) && Encrypter::supported($key, config('app.cipher')), 'APP_KEY deve conter uma chave Laravel válida e permanente.');
        $require(in_array(config('database.default'), ['pgsql', 'mysql'], true), 'Use PostgreSQL ou MySQL externo; SQLite não é persistente na Vercel.');
        $require(config('session.driver') === 'database', 'SESSION_DRIVER deve ser database.');
        $require(config('session.secure') === true && config('session.http_only') === true && config('session.encrypt') === true, 'Ative SESSION_SECURE_COOKIE, SESSION_HTTP_ONLY e SESSION_ENCRYPT.');
        $require(config('cache.default') === 'database', 'CACHE_STORE deve ser database para compartilhar limites e locks.');
        $require(config('queue.default') === 'sync', 'QUEUE_CONNECTION deve ser sync enquanto não houver worker externo.');
        $require(config('logging.default') === 'stderr', 'LOG_CHANNEL deve ser stderr no contêiner.');
        $require(config('mail.default') === 'smtp', 'MAIL_MAILER deve ser smtp para o envio transacional configurado.');
        $smtp = config('mail.mailers.smtp');
        $require(! empty($smtp['host']) && ! empty($smtp['username']) && ! empty($smtp['password']), 'Preencha host, usuário e senha do SMTP nas variáveis privadas.');
        $require(($smtp['scheme'] ?? null) === 'smtps' || ($smtp['require_tls'] ?? false) === true, 'O SMTP deve exigir TLS.');
        foreach (['mail.from.address', 'mail.contact_address', 'legal.support_email'] as $setting) {
            $require((bool) filter_var(config($setting), FILTER_VALIDATE_EMAIL), "Preencha um e-mail válido em {$setting}.");
        }
        $require(strlen((string) config('hosting.cron_secret')) >= 32, 'CRON_SECRET deve ter pelo menos 32 caracteres aleatórios.');

        if (! $this->option('configuration-only') && ! $errors) {
            try {
                DB::connection()->getPdo();
                foreach (['users', 'plans', 'subscriptions', 'sessions', 'cache', 'cache_locks', 'notifications'] as $table) {
                    $require(Schema::hasTable($table), "Execute as migrations: tabela {$table} ausente.");
                }
                if (! $errors) {
                    $require(DB::table('plans')->whereIn('slug', ['free', 'pro', 'equipe'])->count() === 3, 'Execute o PlanSeeder para cadastrar os três planos.');
                    $require(! DB::table('users')->where('is_demo', true)->exists(), 'O banco de produção contém contas demonstrativas. Use um banco novo.');
                }
            } catch (\Throwable $exception) {
                // Database exception strings can contain hostnames and credentials.
                $errors[] = 'Falha ao validar o banco. Confira conexão, TLS, permissões e migrations no ambiente privado.';
            }
        }

        foreach ($errors as $error) { $this->error($error); }
        if ($errors) { return self::FAILURE; }
        $this->info('Validação concluída. A entrega de e-mails e o deploy HTTP ainda devem ser testados no ambiente online.');
        return self::SUCCESS;
    }
}
