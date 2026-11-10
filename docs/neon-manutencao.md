# Manutenção do Neon pelo Windows

O `.env` local continua usando seu banco de desenvolvimento. A manutenção usa somente `.env.neon.local`, ignorado pelo Git (`.env.*.local`). Não publique nem compartilhe esse arquivo.

1. No painel do Neon, abra **Connect** e selecione o mesmo projeto, branch e banco usados pela Vercel. Copie a URL PostgreSQL. Copie apenas `postgresql://...`, sem `psql`, sem aspas de shell e sem colar a senha no chat.
2. No `.env.neon.local`, substitua `COLE_A_URL_DO_NEON_AQUI` em `DB_URL`, mantendo as aspas duplas do arquivo. Preserve `sslmode=require` na URL. Use a URL direta do Neon para manutenção, se disponível.
3. No terminal PowerShell do VS Code, na pasta do projeto, consulte as migrações:

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\scripts\neon.ps1 -Action status
   ```

   Essa ação verifica a conexão SSL e a estrutura esperada, e lista as migrações sem alterar o banco. Credenciais não aparecem na saída. O bypass vale apenas para esse processo, sem alterar a política do Windows.

4. Para aplicar a migração das notificações:

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\scripts\neon.ps1 -Action migrate
   ```

   Essa ação altera o banco selecionado no arquivo privado. Aplica somente `2026_11_10_180000_add_project_notification_tracking`, sem executar outras migrações pendentes. Repetir o comando não reaplica uma migração já concluída.

O script usa o PHP 8.3.33 do Laragon e carrega `pdo_pgsql` somente durante a execução. Para outra instalação, informe `-PhpPath 'C:\caminho\php.exe'`.

Se existir cache de configuração do Laravel, o script para antes de conectar. No projeto local, execute `php artisan config:clear` e tente novamente. Não execute estes comandos em uma instância de produção compartilhada.

O arquivo privado mínimo pode ser recriado com:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.escopofacil.com.br
DB_CONNECTION=pgsql
DB_URL="COLE_A_URL_DO_NEON_AQUI"
DB_SSLMODE=require
LOG_CHANNEL=stderr
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

Não é necessário copiar a `APP_KEY`, senha SMTP ou outras chaves da aplicação para consultar/aplicar essa migração.
