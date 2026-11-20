<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Forgot-password flow: a user can request a one-time link by email, choose a
 * new password and is signed in to their own dashboard. Administrators can
 * generate the link for someone whose email is not reachable (UAT uses the log
 * mailer), because otherwise a forgotten password would lock the account.
 */
class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name, ?string $email = null, bool $active = true): User
    {
        return User::create([
            'name' => $name,
            'email' => $email ?: strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('old-password'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => $active,
        ]);
    }

    private function resetToken(User $user): string
    {
        return Password::broker()->createToken($user);
    }

    public function test_the_login_page_links_to_the_forgot_password_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Forgot your password?')
            ->assertSee(route('password.request'), false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot your password?')
            ->assertSee('Email reset link');
    }

    public function test_a_user_can_request_a_reset_link_by_email(): void
    {
        Notification::fake();

        $user = $this->makeUser('learner', 'Forgetful Learner');

        $this->post(route('password.email'), ['login' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertSame(1, DB::table('password_resets')->where('email', $user->email)->count());
    }

    public function test_an_unknown_identifier_gets_the_same_neutral_response(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['login' => 'nobody@example.com'])
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertSame(0, DB::table('password_resets')->count());
    }

    public function test_the_reset_link_page_opens_with_a_token(): void
    {
        $user = $this->makeUser('parent', 'Reset Parent');
        $token = $this->resetToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee($user->email);
    }

    public function test_a_valid_token_changes_the_password_and_signs_the_user_in(): void
    {
        $user = $this->makeUser('learner', 'Reset Learner');
        $token = $this->resetToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('learner.dashboard'));

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
        $this->assertAuthenticatedAs($user->fresh());

        // The token is consumed so the link cannot be replayed.
        $this->assertSame(0, DB::table('password_resets')->where('email', $user->email)->count());

        Auth::logout();
        $this->flushSession();

        $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'brand-new-password',
        ])->assertRedirect(route('learner.dashboard'));
    }

    public function test_an_emailed_reset_also_clears_the_forced_password_change(): void
    {
        $user = $this->makeUser('parent', 'Provisioned Parent');
        $user->forceFill(['must_change_password' => true])->save();

        $token = $this->resetToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'chosen-by-owner',
            'password_confirmation' => 'chosen-by-owner',
        ])->assertRedirect(route('parent.dashboard'));

        $fresh = $user->fresh();

        $this->assertFalse($fresh->must_change_password);
        $this->assertNotNull($fresh->password_changed_at);
        $this->assertTrue(Hash::check('chosen-by-owner', $fresh->password));

        // The "temporary password" screen must not stand in the way after a reset.
        $this->get(route('parent.dashboard'))
            ->assertOk()
            ->assertDontSee('Choose your own password');
    }

    public function test_a_used_token_cannot_be_replayed(): void
    {
        $user = $this->makeUser('parent', 'Replay Parent');
        $token = $this->resetToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'first-new-password',
            'password_confirmation' => 'first-new-password',
        ])->assertRedirect(route('parent.dashboard'));

        Auth::logout();
        $this->flushSession();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'second-new-password',
            'password_confirmation' => 'second-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('first-new-password', $user->fresh()->password));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = $this->makeUser('tutor', 'Slow Tutor');
        $token = $this->resetToken($user);

        Carbon::setTestNow(Carbon::now()->addMinutes(61));

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'too-late-password',
                'password_confirmation' => 'too-late-password',
            ])->assertSessionHasErrors('email');

        Carbon::setTestNow();

        $this->assertFalse(Hash::check('too-late-password', $user->fresh()->password));
    }

    public function test_an_administrator_can_generate_a_reset_link_when_mail_is_not_configured(): void
    {
        config(['mail.default' => 'log']);

        $admin = $this->makeUser('admin', 'School Admin');
        $target = $this->makeUser('learner', 'Offline Learner');

        $link = null;

        $response = $this->actingAs($admin)->post(route('admin.users.password-reset', $target));
        $response->assertRedirect();
        $response->assertSessionHas('password_reset_link', function ($value) use (&$link) {
            $link = $value;

            return true;
        });

        $this->assertNotNull($link);
        $this->assertStringContainsString('/password/reset/', $link);

        $path = (string) parse_url($link, PHP_URL_PATH);
        $token = basename($path);
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $this->assertSame($target->email, $query['email']);
        $this->assertNotEmpty($token);

        // The link the administrator handed over actually works.
        Auth::logout();
        $this->flushSession();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $target->email,
            'password' => 'shared-link-password',
            'password_confirmation' => 'shared-link-password',
        ])->assertRedirect(route('learner.dashboard'));

        $this->assertTrue(Hash::check('shared-link-password', $target->fresh()->password));
    }

    public function test_the_admin_reset_emails_the_user_when_mail_is_configured(): void
    {
        Notification::fake();
        config(['mail.default' => 'smtp']);

        $admin = $this->makeUser('ceo', 'Chief Executive');
        $target = $this->makeUser('parent', 'Email Parent');

        $this->actingAs($admin)
            ->post(route('admin.users.password-reset', $target))
            ->assertSessionHas('success');

        Notification::assertSentTo($target, ResetPassword::class);
    }

    public function test_only_school_administration_can_reset_passwords_and_never_a_ceo_from_an_admin(): void
    {
        $admin = $this->makeUser('admin', 'School Admin');
        $tutor = $this->makeUser('tutor', 'Class Tutor');
        $parent = $this->makeUser('parent', 'School Parent');
        $target = $this->makeUser('learner', 'Target Learner');
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $this->actingAs($tutor)->post(route('admin.users.password-reset', $target))->assertForbidden();
        $this->actingAs($parent)->post(route('admin.users.password-reset', $target))->assertForbidden();

        // A regular administrator must not be able to take over the CEO account.
        $this->actingAs($admin)->post(route('admin.users.password-reset', $ceo))->assertForbidden();
    }

    public function test_an_inactive_account_cannot_be_sent_a_reset_link(): void
    {
        $admin = $this->makeUser('admin', 'School Admin');
        $inactive = $this->makeUser('learner', 'Inactive Learner', null, false);

        $this->actingAs($admin)
            ->post(route('admin.users.password-reset', $inactive))
            ->assertSessionHas('error');

        $this->assertSame(0, DB::table('password_resets')->where('email', $inactive->email)->count());
    }
}

