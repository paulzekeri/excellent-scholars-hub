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

    /** @test */
    public function create_admin_command_creates_a_ceo_login()
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

    /** @test */
    public function create_admin_command_creates_an_administrator_login()
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

    /** @test */
    public function create_admin_command_rejects_unsupported_roles()
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

    /** @test */
    public function create_admin_command_rejects_duplicate_emails()
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

    /** @test */
    public function seeder_skips_demo_data_when_disabled()
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

    /** @test */
    public function seeder_creates_demo_data_outside_production_by_default()
    {
        User::where('email', 'ceo@esh.com')->delete();

        $this->seed();

        $this->assertNotNull(User::where('email', 'ceo@esh.com')->first(), 'Local/testing installs seed the demo accounts by default.');
    }
}
