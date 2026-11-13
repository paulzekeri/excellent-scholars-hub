<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeploymentToolingTest extends TestCase
{
    use DatabaseTransactions;
    public function test_create_admin_command_creates_a_ceo_login()
    {
        $email = 'uat.owner.'.uniqid().'@example.com';

        $this->artisan('esh:create-admin', [
            '--name' => 'UAT Owner',
            '--email' => $email,
            '--password' => 'secret-pass-123',
            '--role' => 'ceo',
        ])->assertExitCode(0);

        $user = User::where('email', $email)->first();

        $this->assertNotNull($user, 'The CEO account was not created.');
        $this->assertSame('ceo', $user->role->slug);
        $this->assertTrue((bool) $user->is_active);
        $this->assertTrue(Hash::check('secret-pass-123', $user->password));
    }
    public function test_create_admin_command_creates_an_administrator_login()
    {
        $email = 'uat.admin.'.uniqid().'@example.com';

        $this->artisan('esh:create-admin', [
            '--name' => 'UAT Admin',
            '--email' => $email,
            '--password' => 'secret-pass-123',
            '--role' => 'admin',
        ])->assertExitCode(0);

        $this->assertSame('admin', User::where('email', $email)->first()->role->slug);
    }
    public function test_create_admin_command_rejects_unsupported_roles()
    {
        $email = 'uat.tutor.'.uniqid().'@example.com';

        $this->artisan('esh:create-admin', [
            '--name' => 'UAT Tutor',
            '--email' => $email,
            '--password' => 'secret-pass-123',
            '--role' => 'tutor',
        ])->assertExitCode(1);

        $this->assertNull(User::where('email', $email)->first(), 'A tutor login must not be created by this command.');
    }
    public function test_create_admin_command_rejects_duplicate_emails()
    {
        $email = 'uat.duplicate.'.uniqid().'@example.com';

        $this->artisan('esh:create-admin', [
            '--name' => 'First Owner',
            '--email' => $email,
            '--password' => 'secret-pass-123',
        ])->assertExitCode(0);

        $this->artisan('esh:create-admin', [
            '--name' => 'Second Owner',
            '--email' => $email,
            '--password' => 'secret-pass-123',
        ])->assertExitCode(1);

        $this->assertSame(1, User::where('email', $email)->count());
    }
    public function test_seeder_skips_demo_data_when_disabled()
    {
        $demo = User::where('email', 'ceo@esh.com')->first();

        if ($demo) {
            $demo->delete();
        }

        $usersBefore = User::count();
        $classesBefore = Classes::count();

        $_ENV['SEED_DEMO_ACCOUNTS'] = 'false';
        $_SERVER['SEED_DEMO_ACCOUNTS'] = 'false';

        try {
            $this->seed();
        } finally {
            unset($_ENV['SEED_DEMO_ACCOUNTS'], $_SERVER['SEED_DEMO_ACCOUNTS']);
        }

        $this->assertNull(User::where('email', 'ceo@esh.com')->first(), 'Demo accounts must be skipped when SEED_DEMO_ACCOUNTS=false.');
        $this->assertSame($usersBefore, User::count());
        $this->assertSame($classesBefore, Classes::count());
        $this->assertSame(5, Role::count(), 'Role rows must still exist after demo seeding is skipped.');
    }
    public function test_seeder_creates_demo_data_outside_production_by_default()
    {
        User::where('email', 'ceo@esh.com')->delete();

        $this->seed();

        $this->assertNotNull(User::where('email', 'ceo@esh.com')->first(), 'Local/testing installs seed the demo accounts by default.');
    }
    public function test_cpanel_deployment_files_target_php_84_and_keep_routes_cacheable()
    {
        $root = base_path();

        $this->assertFileExists($root.'/.htaccess');
        $this->assertFileExists($root.'/.user.ini');
        $this->assertFileExists($root.'/.github/workflows/deploy-cpanel.yml');

        $htaccess = file_get_contents($root.'/.htaccess');
        $this->assertStringContainsString('RewriteRule ^(.*)$ public/$1 [L]', $htaccess);
        $this->assertStringContainsString('Require all denied', $htaccess);

        $workflow = file_get_contents($root.'/.github/workflows/deploy-cpanel.yml');
        $this->assertStringContainsString("--exclude='.env'", $workflow, 'The pipeline must never overwrite the server .env.');
        $this->assertStringContainsString('secrets.CPANEL_HOST', $workflow);
        $this->assertStringContainsString('secrets.CPANEL_USER', $workflow);
        $this->assertStringContainsString('secrets.CPANEL_PASSWORD', $workflow);
        $this->assertStringNotContainsString('vars.', $workflow, 'Only the three required secrets are configured; no optional variables.');
        $this->assertStringNotContainsString('ENV_CONTENT', $workflow, 'The .env is created by hand; the workflow never writes it.');
        $this->assertStringContainsString("'public_html/'", $workflow, 'Production deploys to the primary domain document root.');
        $this->assertStringContainsString('Report remote layout', $workflow, 'A wrong remote directory must be visible in the log.');
        $this->assertStringContainsString('artisan storage:link', $workflow);
        $this->assertStringContainsString('artisan esh:health', $workflow);
        $this->assertStringContainsString('artisan config:clear', $workflow, 'A cached config would hide edits to .env.');
        $this->assertStringContainsString("PHP_VERSION: '8.4'", $workflow, 'Laravel 13 runs on PHP 8.3 - 8.5; 8.4 matches the cPanel domain.');

        // The UAT pipeline is the same work on a separate branch, domain and secrets.
        $uatWorkflow = file_get_contents($root.'/.github/workflows/deploy-cpanel-uat.yml');
        $this->assertStringContainsString('secrets.UAT_CPANEL_HOST', $uatWorkflow);
        $this->assertStringContainsString('secrets.UAT_CPANEL_PASSWORD', $uatWorkflow);
        $this->assertStringNotContainsString('vars.', $uatWorkflow, 'Only the three required secrets are configured; no optional variables.');
        $this->assertStringNotContainsString('ENV_CONTENT', $uatWorkflow, 'The .env is created by hand; the workflow never writes it.');
        $this->assertStringContainsString("'public_html/uat.eshglobal.org/'", $uatWorkflow);
        $this->assertStringContainsString("      - uat", $uatWorkflow, 'The UAT pipeline must listen to the uat branch, not main.');
        $this->assertStringNotContainsString("      - main", $uatWorkflow);

        // Laravel 13 *can* cache routes — the pipeline may even run `artisan optimize`.
        // That only holds while no route *action* is a closure, because closures
        // cannot be serialised into the route cache.
        $webRoutes = file_get_contents($root.'/routes/web.php');
        $this->assertStringNotContainsString("Route::get('/', function", $webRoutes, 'Closure route actions cannot be cached; use a controller action.');
        $this->assertStringContainsString("\\App\\Http\\Controllers\\HomeController::class, 'index'", $webRoutes);
        $this->assertStringContainsString("\\App\\Http\\Controllers\\HomeController::class, 'userGuide'", $webRoutes);
        $this->assertStringContainsString('ea-php84', file_get_contents($root.'/.cpanel.yml'), 'The cPanel Git pipeline must select the PHP 8.4 binary.');
    }
}
