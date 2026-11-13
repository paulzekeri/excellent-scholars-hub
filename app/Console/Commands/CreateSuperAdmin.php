<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * The maintenance / super-user account: the CEO role (so it can see and do
 * everything) with `is_hidden = true` (so no other user ever sees it in a list,
 * dropdown, contact picker or broadcast, and no mail is sent to it).
 *
 * Run once per environment:
 *
 *   php artisan esh:create-super-admin \
 *     --name="Maintenance" --email="maint@your-school.org" --password="..."
 *
 * Re-running with the same email rotates the password of the existing hidden
 * account. See MAINTENANCE-USER.md.
 */
class CreateSuperAdmin extends Command
{
    /**
     * @var string
     */
    protected $signature = 'esh:create-super-admin
        {--name= : Display name (only this account will ever see it)}
        {--email= : Login email address}
        {--password= : Password (min 8 characters); omit to be prompted}';

    /**
     * @var string
     */
    protected $description = 'Create the hidden maintenance account (full CEO access, invisible to every other user)';

    public function handle()
    {
        $role = Role::firstOrCreate(['slug' => 'ceo'], [
            'name' => 'CEO',
            'description' => 'Chief Executive Officer — full admin access plus CEO-only payments',
        ]);

        $name = trim((string) ($this->option('name') ?: $this->ask('Display name (only you will ever see it)')));
        $email = trim((string) ($this->option('email') ?: $this->ask('Login email address')));

        if ($name === '') {
            $this->error('A display name is required.');

            return 1;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That email address is not valid.');

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

        $existing = User::where('email', $email)->first();

        if ($existing && ! $existing->isHidden()) {
            $this->error("A normal user with the email {$email} already exists — choose another address.");

            return 1;
        }

        if ($existing) {
            $existing->update([
                'name' => $name,
                'password' => Hash::make($password),
                'role_id' => $role->id,
                'is_active' => true,
                'is_hidden' => true,
                'must_change_password' => false,
            ]);

            $this->info("Maintenance account updated: {$existing->email} (id {$existing->id}).");

            return 0;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'is_active' => true,
            'is_hidden' => true,
            'must_change_password' => false,
        ]);

        $this->info("Maintenance account created: {$user->email} (id {$user->id}).");
        $this->line('Full CEO access: admin area, payments, every list and report.');
        $this->line('Hidden from every user list, dropdown, contact picker, broadcast and count.');
        $this->line('Sign in at ' . rtrim((string) config('app.url'), '/') . '/login');
        $this->warn('No email is ever sent to this address. Store the credentials in your password manager.');

        return 0;
    }
}
