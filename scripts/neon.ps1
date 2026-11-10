param(
    [ValidateSet('status', 'migrate')]
    [string]$Action = 'status',
    [string]$PhpPath = 'C:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path -LiteralPath $PhpPath)) {
    throw 'PHP nao encontrado. Informe o caminho com -PhpPath.'
}

# Load PostgreSQL only for this process; do not modify Laragon/php.ini.
& $PhpPath -d extension=pdo_pgsql (Join-Path $PSScriptRoot 'neon.php') $Action
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
