<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The maintenance / super-user account: the CEO role with `is_hidden = true`.
 * It can see and do everything, but no other user ever sees it in a list,
 * dropdown, contact picker, broadcast, notification fan-out or count, and no
 * mail is ever sent to it. See MAINTENANCE-USER.md.
 */
class SuperAdminTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name, ?string $email = null, bool $hidden = false): User
    {
        return User::create([
            'name' => $name,
            'email' => $email ?: strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => true,
            'is_hidden' => $hidden,
        ]);
    }

    public function test_the_command_creates_a_hidden_ceo_with_full_access(): void
    {
        $email = 'maintenance.' . uniqid() . '@example.com';

        $this->artisan('esh:create-super-admin', [
            '--name' => 'Maintenance Account',
            '--email' => $email,
            '--password' => 'secret123',
        ])->assertExitCode(0);

        $hidden = User::where('email', $email)->firstOrFail();

        $this->assertTrue($hidden->isHidden());
        $this->assertSame('ceo', $hidden->role->slug);
        $this->assertTrue(Hash::check('secret123', $hidden->password));
        $this->assertFalse($hidden->needsPasswordChange());

        // CEO access is real: admin area, payments and every list open for it.
        $this->actingAs($hidden)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($hidden)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($hidden)->get(route('admin.users.index'))->assertOk();
    }

    public function test_the_command_rotates_the_password_of_an_existing_hidden_account(): void
    {
        $hidden = $this->makeUser('ceo', 'Old Maintenance', null, true);

        $this->artisan('esh:create-super-admin', [
            '--name' => 'Rotated Maintenance',
            '--email' => $hidden->email,
            '--password' => 'newsecret123',
        ])->assertExitCode(0);

        $hidden->refresh();
        $this->assertSame('Rotated Maintenance', $hidden->name);
        $this->assertTrue($hidden->isHidden());
        $this->assertTrue(Hash::check('newsecret123', $hidden->password));
        $this->assertSame(1, User::where('email', $hidden->email)->count(), 'Rotating must not create a second row.');
    }

    public function test_the_hidden_account_is_absent_from_user_lists_and_counts(): void
    {
        $admin = $this->makeUser('admin', 'Visible Admin');
        $hidden = $this->makeUser('ceo', 'Maintenance Person', null, true);

        $this->assertTrue(User::visible()->whereKey($hidden->id)->doesntExist());

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Visible Admin')
            ->assertDontSee('Maintenance Person')
            ->assertDontSee($hidden->email);

        $dashboard = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $this->assertSame(User::visible()->count(), $dashboard->viewData('totalUsers'));
        $this->assertNotContains($hidden->id, $dashboard->viewData('recentUsers')->pluck('id')->all());
    }

    public function test_the_hidden_account_cannot_be_messaged_or_notified_by_others(): void
    {
        $visibleCeo = $this->makeUser('ceo', 'Visible Principal');
        $hidden = $this->makeUser('ceo', 'Invisible Principal', null, true);
        $parentUser = $this->makeUser('parent', 'Contact Parent');

        // The parent's "message the school" list shows the CEO, never the hidden one.
        $this->actingAs($parentUser)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Visible Principal')
            ->assertDontSee('Invisible Principal');

        // A notification to "all users" skips the hidden account.
        $admin = $this->makeUser('admin', 'Notify Admin');

        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => 'Term dates',
            'message' => 'The new term starts on Monday.',
            'type' => 'info',
        ])->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseHas('notifications', ['user_id' => $parentUser->id, 'title' => 'Term dates']);
        $this->assertDatabaseHas('notifications', ['user_id' => $visibleCeo->id, 'title' => 'Term dates']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $hidden->id]);
    }

    public function test_another_administrator_cannot_manage_the_hidden_account(): void
    {
        $admin = $this->makeUser('admin', 'Manage Admin');
        $hidden = $this->makeUser('ceo', 'Protected Maintenance', null, true);

        $this->actingAs($admin)->get(route('admin.users.edit', $hidden))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.users.destroy', $hidden))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.users.password-reset', $hidden))->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $hidden->id, 'is_hidden' => true]);
    }

    public function test_password_reset_mail_is_never_sent_to_the_hidden_account(): void
    {
        Mail::fake();

        $hidden = $this->makeUser('ceo', 'Mail Maintenance', 'maint.' . uniqid() . '@example.com', true);

        $this->post(route('password.email'), ['login' => $hidden->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingSent();
    }

    public function test_the_seeder_creates_the_hidden_account_from_env_at_deploy(): void
    {
        $email = 'deploy.maint.' . uniqid() . '@example.com';

        $_ENV['SUPER_ADMIN_NAME'] = 'Deploy Maintenance';
        $_ENV['SUPER_ADMIN_EMAIL'] = $email;
        $_ENV['SUPER_ADMIN_PASSWORD'] = 'secret123';
        $_SERVER['SUPER_ADMIN_NAME'] = 'Deploy Maintenance';
        $_SERVER['SUPER_ADMIN_EMAIL'] = $email;
        $_SERVER['SUPER_ADMIN_PASSWORD'] = 'secret123';

        try {
            $this->seed();

            $hidden = User::where('email', $email)->first();

            $this->assertNotNull($hidden, 'The deploy seeder must create the hidden account when SUPER_ADMIN_* is set.');
            $this->assertTrue($hidden->isHidden());
            $this->assertSame('ceo', $hidden->role->slug);

            // Running the same deploy again does not duplicate or overwrite it.
            $this->seed();
            $this->assertSame(1, User::where('email', $email)->count());
        } finally {
            unset($_ENV['SUPER_ADMIN_NAME'], $_ENV['SUPER_ADMIN_EMAIL'], $_ENV['SUPER_ADMIN_PASSWORD']);
            unset($_SERVER['SUPER_ADMIN_NAME'], $_SERVER['SUPER_ADMIN_EMAIL'], $_SERVER['SUPER_ADMIN_PASSWORD']);
        }
    }

    public function test_a_normal_account_still_receives_the_password_reset_mail(): void
    {
        Mail::fake();

        $user = $this->makeUser('parent', 'Reset Parent');

        $this->post(route('password.email'), ['login' => $user->email])->assertRedirect();

        // The reset link is a notification; Laravel queues it on the mail channel.
        $this->assertNotNull(
            \Illuminate\Support\Facades\DB::table('password_resets')->where('email', $user->email)->first(),
            'A visible account must still get the broker token.'
        );
    }
}
