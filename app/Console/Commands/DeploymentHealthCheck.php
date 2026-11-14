<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeploymentHealthCheck extends Command
{
    /**
     * @var string
     */
    protected $signature = 'esh:health';

    /**
     * @var string
     */
    protected $description = 'Check PHP, .env, mailer, database, migrations, writable folders and the public storage link after a deployment';

    private $failures = 0;

    private $warnings = 0;

    public function handle()
    {
        $this->line('ESH LMS deployment health check');
        $this->line('----------------------------------');

        $this->checkPhp();
        $this->checkEnvironment();
        $this->checkMail();
        $this->checkDatabase();
        $this->checkFilesystem();
        $this->checkAccounts();

        $this->line('----------------------------------');
        $this->line("Failures: {$this->failures}   Warnings: {$this->warnings}");

        if ($this->failures > 0) {
            $this->error('Deployment is NOT ready. Fix the failures and run `php artisan esh:health` again.');

            return 1;
        }

        $this->info('Deployment looks good.');

        return 0;
    }

    private function checkPhp()
    {
        $version = PHP_VERSION;

        if (version_compare($version, '8.3', '>=') && version_compare($version, '8.6', '<')) {
            $this->pass("PHP {$version}");
        } else {
            $this->recordFailure("PHP {$version}", 'Laravel 13 needs PHP 8.3 - 8.5. Pick 8.4 (ea-php84) in cPanel → MultiPHP Manager.');
        }

        foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'tokenizer', 'xml', 'ctype'] as $extension) {
            if (extension_loaded($extension)) {
                $this->pass("Extension {$extension}");
            } else {
                $this->recordFailure("Extension {$extension}", 'Enable it in cPanel → Select PHP Version → Extensions.');
            }
        }
    }

    private function checkEnvironment()
    {
        if ((string) config('app.key') !== '') {
            $this->pass('APP_KEY is set');
        } else {
            $this->recordFailure('APP_KEY is set', 'Run `php artisan key:generate`.');
        }

        $environment = app()->environment();
        $this->note("APP_ENV = {$environment}");

        if (config('app.debug') && $environment === 'production') {
            $this->caution('APP_DEBUG is on', 'Set APP_DEBUG=false in .env so stack traces are not shown to visitors.');
        }

        $url = (string) config('app.url');

        if (! preg_match('#^https?://#', $url)) {
            $this->recordFailure("APP_URL = '{$url}'", 'APP_URL must be the full site URL, e.g. https://eshglobal.org');
        } elseif (strpos($url, 'localhost') !== false) {
            $this->caution("APP_URL = {$url}", 'Change APP_URL to the deployed domain.');
        } else {
            $this->pass("APP_URL = {$url}");
        }

        $this->note('Timezone = '.config('app.timezone'));
    }

    /**
     * Email is configuration, not code: each environment has its own .env, so a
     * working UAT mailer says nothing about production. The `log`/`array` drivers
     * accept mail without delivering it, which is easy to miss.
     */
    private function checkMail()
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array', ''], true)) {
            $this->caution(
                "MAIL_MAILER = '{$mailer}'",
                'Emails are written to the log, not delivered. Set MAIL_MAILER=smtp and the MAIL_* values in this environment\'s .env, then run `php artisan config:clear` (or `config:cache`).'
            );
        } else {
            $this->pass("MAIL_MAILER = {$mailer}");
        }

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host');

            if ($host === '' || $host === 'smtp.mailgun.org') {
                $this->caution('SMTP host', 'MAIL_HOST is missing or still the placeholder, so emails cannot be delivered.');
            } else {
                $this->note('SMTP host = '.$host.':'.config('mail.mailers.smtp.port'));
            }
        }

        $from = (string) config('mail.from.address');

        if ($from === '') {
            $this->caution('MAIL_FROM_ADDRESS is empty', 'Set a From address on this site\'s own domain so mail is not treated as spoofing.');
        } else {
            $this->note("Mail from = {$from}");
        }
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->recordFailure('Database connection', $e->getMessage());
            $this->line('  Skipping migration and account checks.');

            return;
        }

        $this->pass('Database connection to `'.DB::connection()->getDatabaseName().'`');

        try {
            $this->note('MySQL server = '.DB::selectOne('select version() as version')->version);
        } catch (Throwable $e) {
            $this->caution('MySQL version unavailable', $e->getMessage());
        }

        try {
            $migrator = app('migrator');
            $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff($files, $ran));

            if (empty($pending)) {
                $this->pass('Migrations up to date ('.count($ran).' ran)');
            } else {
                $this->recordFailure(count($pending).' pending migration(s)', 'Run `php artisan migrate --force`. Missing: '.implode(', ', array_slice($pending, 0, 5)));
            }
        } catch (Throwable $e) {
            $this->recordFailure('Migration status', $e->getMessage());
        }
    }

    private function checkFilesystem()
    {
        $paths = [
            'storage/app/public' => storage_path('app/public'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        foreach ($paths as $label => $path) {
            if (! is_dir($path)) {
                $this->recordFailure("{$label} exists", "Missing directory: {$path}");
            } elseif (! is_writable($path)) {
                $this->recordFailure("{$label} is writable", "Run: chmod -R ug+rwX {$path}");
            } else {
                $this->pass("{$label} is writable");
            }
        }

        $link = public_path('storage');

        if (file_exists($link) || is_link($link)) {
            $this->pass('public/storage link exists');
        } else {
            $this->recordFailure('public/storage link exists', 'Run `php artisan storage:link` so materials and submissions download.');
        }

        $upload = (string) ini_get('upload_max_filesize');
        $post = (string) ini_get('post_max_size');
        $limit = 100 * 1024 * 1024;

        if ($this->iniBytes($upload) < $limit || $this->iniBytes($post) < $limit) {
            $this->caution("Upload limits: upload_max_filesize={$upload}, post_max_size={$post}", 'Material uploads validate at 100 MB; raise both in cPanel → MultiPHP INI Editor.');
        } else {
            $this->pass("Upload limits ok ({$upload} / {$post})");
        }
    }

    private function checkAccounts()
    {
        try {
            $this->note('Users = '.User::count().' | Roles = '.Role::count());

            $staff = User::whereHas('role', function ($query) {
                $query->whereIn('slug', ['ceo', 'admin']);
            })->count();

            if ($staff > 0) {
                $this->pass("{$staff} CEO/administrator login(s)");
            } else {
                $this->recordFailure('At least one CEO/administrator login', 'Run `php artisan esh:create-admin --role=ceo`.');
            }

            $demoEmails = ['ceo@esh.com', 'admin@esh.com', 'tutor@esh.com', 'learner@esh.com', 'parent@esh.com'];
            $demo = User::whereIn('email', $demoEmails)->count();

            if ($demo > 0) {
                $this->caution("{$demo} demo account(s) present", 'Every demo login uses the password "password" - remove them before go-live.');
            }
        } catch (Throwable $e) {
            $this->recordFailure('Account check', $e->getMessage());
        }
    }

    private function iniBytes($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        if ($unit === 'g') {
            $number *= 1024 * 1024 * 1024;
        } elseif ($unit === 'm') {
            $number *= 1024 * 1024;
        } elseif ($unit === 'k') {
            $number *= 1024;
        }

        return (int) $number;
    }

    private function pass($label, $detail = '')
    {
        $this->line('  [OK]   '.$label.($detail !== '' ? "  ({$detail})" : ''));
    }

    private function recordFailure($label, $detail = '')
    {
        $this->failures++;
        $this->error('  [FAIL] '.$label.($detail !== '' ? "  {$detail}" : ''));
    }

    private function caution($label, $detail = '')
    {
        $this->warnings++;
        $this->line('  [WARN] '.$label.($detail !== '' ? "  {$detail}" : ''));
    }

    private function note($label)
    {
        $this->line('  [INFO] '.$label);
    }
}

