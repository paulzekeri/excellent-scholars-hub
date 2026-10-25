<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * @var string
     */
    protected $signature = 'esh:create-admin
        {--name= : Full name of the account owner}
        {--email= : Login email address}
        {--password= : Password (min 8 characters); omit to be prompted}
        {--role=ceo : Either "ceo" or "admin"}';

    /**
     * @var string
     */
    protected $description = 'Create the first CEO/administrator login on a fresh deployment (demo seeding not required)';

    /**
     * Every role the UI expects to exist. The demo seeder creates these too, but a
     * production install starts with only the "ceo" row inserted by migration 000014.
     */
    private const ROLES = [
        'ceo' => ['name' => 'CEO', 'description' => 'Chief Executive Officer — full admin access plus CEO-only payments'],
        'admin' => ['name' => 'admin', 'description' => 'Administrator'],
        'tutor' => ['name' => 'tutor', 'description' => 'Tutor/Teacher'],
        'learner' => ['name' => 'learner', 'description' => 'Student/Learner'],
        'parent' => ['name' => 'parent', 'description' => 'Parent/Guardian'],
    ];

    public function handle()
    {
        $slug = strtolower((string) $this->option('role'));

        if (! in_array($slug, ['ceo', 'admin'], true)) {
            $this->error('--role must be "ceo" or "admin". Other roles are created from Users → New User.');

            return 1;
        }

        foreach (self::ROLES as $roleSlug => $attributes) {
            Role::firstOrCreate(['slug' => $roleSlug], $attributes);
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Full name')));
        $email = trim((string) ($this->option('email') ?: $this->ask('Email address')));

        if ($name === '') {
            $this->error('A name is required.');

            return 1;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That email address is not valid.');

            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("A user with the email {$email} already exists.");

            return 1;
        }

        $password = (string) $this->option('password');

        if ($password === '') {
            $password = (string) $this->secret('Password (min 8 characters)');
            $confirmation = (string) $this->secret('Confirm password');

            if ($password !== $confirmation) {
                $this->error('The passwords do not match.');

                return 1;
            }
        }

        if (strlen($password) < 8) {
            $this->error('The password must be at least 8 characters.');

            return 1;
        }

        $role = Role::where('slug', $slug)->firstOrFail();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->info("Created {$role->name} account #{$user->id} ({$user->email}).");
        $this->line('Sign in at '.rtrim((string) config('app.url'), '/').'/login');

        return 0;
    }
}
