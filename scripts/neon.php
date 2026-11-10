<?php

// Private CLI maintenance; never include this file in an HTTP route.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root = dirname(__DIR__);
$fail = function (string $message): never { fwrite(STDERR, $message.PHP_EOL); exit(1); };
$action = $argv[1] ?? 'status';
if (!in_array($action, ['status', 'migrate'], true)) $fail('Acao invalida. Use status ou migrate.');
if (!extension_loaded('pdo_pgsql')) $fail('O PHP precisa da extensao pdo_pgsql. Use scripts/neon.ps1.');
if (!is_file($root.'/.env.neon.local')) $fail('Crie o arquivo privado .env.neon.local conforme docs/neon-manutencao.md.');
if (is_file($root.'/bootstrap/cache/config.php')) $fail('Existe um cache de configuracao local. Remova-o com php artisan config:clear antes de usar a configuracao separada.');

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->loadEnvironmentFrom('.env.neon.local');

try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    if (config('database.default') !== 'pgsql') $fail('A configuracao selecionada nao usa PostgreSQL. Nenhuma migracao executada.');
    $url = config('database.connections.pgsql.url');
    if (!is_string($url) || !str_starts_with($url, 'postgres')) $fail('Preencha DB_URL no arquivo .env.neon.local com a URL do Neon.');
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || !str_ends_with(strtolower($host), '.neon.tech')) $fail('DB_URL deve apontar para o servidor Neon. Confira o arquivo privado.');
    // Laravel parses URL query parameters as connection options. Never permit an insecure override.
    // Avoid server-side prepared statement lifetimes across a pooled maintenance connection.
    config(['database.connections.pgsql.options' => [PDO::ATTR_EMULATE_PREPARES => true]]);
    $connection = Illuminate\Support\Facades\DB::connection();
    if (!in_array($connection->getConfig('sslmode'), ['require', 'verify-ca', 'verify-full'], true)) $fail('A conexao precisa usar SSL: mantenha sslmode=require na URL do Neon.');
    $schema = $connection->getSchemaBuilder();
    foreach (['migrations', 'users', 'projects', 'tasks', 'notifications'] as $table) {
        if (!$schema->hasTable($table)) $fail('Banco sem a estrutura esperada do Escopo Facil. Confira banco e branch antes de continuar.');
    }
    // Record and verify provenance: this helper is only for the current application database.
    if (!$connection->table('migrations')->where('migration', '2026_10_23_160000_create_workspace_tables')->exists()) {
        $fail('Historico de migracoes diferente do esperado. Confira a conexao selecionada.');
    }
    echo "Conexao SSL com o Neon verificada. Arquivo: .env.neon.local".PHP_EOL;
    if ($action === 'status') {
        $code = Illuminate\Support\Facades\Artisan::call('migrate:status');
    } else {
        // Migrate only the reviewed notification schema; do not run unrelated pending migrations.
        $code = Illuminate\Support\Facades\Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_11_10_180000_add_project_notification_tracking.php',
            '--force' => true,
        ]);
    }
    echo Illuminate\Support\Facades\Artisan::output();
    exit($code);
} catch (Throwable $e) {
    if ($e instanceof Illuminate\Database\QueryException) {
        $state = $e->errorInfo[0] ?? null;
        if (is_string($state) && preg_match('/^[A-Z0-9]{5}$/', $state)) {
            fwrite(STDERR, 'Codigo PostgreSQL (sem credenciais): '.$state.PHP_EOL);
        }
        if (preg_match('/^(alter table "notifications"|create (unique )?index|savepoint|rollback)/i', $e->getSql())) {
            fwrite(STDERR, 'Operacao de esquema: '.$e->getSql().PHP_EOL);
        }
    }
    // Connection exceptions may contain the full URL/password; keep credentials out of terminal logs.
    $fail('Nao foi possivel concluir a operacao ('.$e::class.'). Confira a URL, banco/branch, credenciais e acesso de rede. A conexao nao sera exibida.');
}
