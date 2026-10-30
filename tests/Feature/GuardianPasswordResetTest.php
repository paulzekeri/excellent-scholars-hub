<?php

namespace Tests\Feature;

use App\Mail\LearnerPasswordResetMail;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Note 12 — a child without a mailbox resets by username and the one-time link
 * is emailed to the parent/guardian. Parents can also send the link to
 * themselves from the parent dashboard.
 */
class GuardianPasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    private function makeUser(string $roleSlug, string $name, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->roleId($roleSlug),
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @return array{0: User, 1: ParentModel, 2: User}  guardian user, parent row, child user
     */
    private function makeFamily(bool $childHasEmail = false, ?ParentModel $existingParent = null): array
    {
        $guardian = $this->makeUser('parent', 'Guardian ' . uniqid());
        $parent = $existingParent ?: ParentModel::create(['user_id' => $guardian->id]);

        $child = $this->makeUser('learner', 'Child ' . uniqid(), [
            'username' => 'child_' . uniqid(),
            'email' => $childHasEmail ? 'child.' . uniqid() . '@example.com' : null,
            'password' => Hash::make('old-child-password'),
        ]);

        Learner::create(['user_id' => $child->id, 'parent_id' => $parent->id]);

        return [$guardian, $parent, $child];
    }

    /** Pull the token (path) and uid (query) out of a reset mail. */
    private function tokenFromMail(LearnerPasswordResetMail $mail): array
    {
        $path = (string) parse_url($mail->resetUrl, PHP_URL_PATH);
        parse_str((string) parse_url($mail->resetUrl, PHP_URL_QUERY), $query);

        return [basename($path), (int) ($query['uid'] ?? 0)];
    }

    public function test_the_forgot_form_asks_for_an_email_or_a_username(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Email or username')
            ->assertSee('name="login"', false);
    }

    public function test_forgot_password_accepts_a_username_and_emails_the_guardian(): void
    {
        Mail::fake();

        [$guardian, $parent, $child] = $this->makeFamily();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['login' => $child->username])
            ->assertSessionHas('status');

        Mail::assertSent(LearnerPasswordResetMail::class, function (LearnerPasswordResetMail $mail) use ($guardian) {
            return $mail->hasTo($guardian->email);
        });

        $this->assertSame(1, DB::table('password_resets')->where('email', 'noemail:' . $child->id)->count());
    }

    public function test_the_guardian_link_sets_the_password_and_signs_the_child_in(): void
    {
        Mail::fake();

        [$guardian, $parent, $child] = $this->makeFamily();

        $this->post(route('password.email'), ['login' => $child->username]);

        $mail = Mail::sent(LearnerPasswordResetMail::class)->first();
        [$token, $uid] = $this->tokenFromMail($mail);

        $this->assertSame($child->id, $uid);

        $this->get(route('password.reset', ['token' => $token, 'uid' => $uid]))
            ->assertOk()
            ->assertSee($child->username);

        $this->post(route('password.update'), [
            'token' => $token,
            'uid' => $uid,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('learner.dashboard'));

        $child->refresh();

        $this->assertTrue(Hash::check('brand-new-password', $child->password));
        $this->assertFalse(Hash::check('old-child-password', $child->password));
        $this->assertFalse($child->must_change_password);
        $this->assertNotNull($child->password_changed_at);
        $this->assertAuthenticatedAs($child);

        $this->assertSame(0, DB::table('password_resets')->where('email', 'noemail:' . $child->id)->count());
    }

    public function test_a_guardian_link_cannot_be_replayed(): void
    {
        Mail::fake();

        [$guardian, $parent, $child] = $this->makeFamily();

        $this->post(route('password.email'), ['login' => $child->username]);
        [$token, $uid] = $this->tokenFromMail(Mail::sent(LearnerPasswordResetMail::class)->first());

        $this->post(route('password.update'), [
            'token' => $token,
            'uid' => $uid,
            'password' => 'first-new-password',
            'password_confirmation' => 'first-new-password',
        ])->assertRedirect(route('learner.dashboard'));

        \Illuminate\Support\Facades\Auth::logout();
        $this->flushSession();

        $this->from(route('password.request'))
            ->post(route('password.update'), [
                'token' => $token,
                'uid' => $uid,
                'password' => 'second-new-password',
                'password_confirmation' => 'second-new-password',
            ])->assertSessionHasErrors('token');

        $this->assertTrue(Hash::check('first-new-password', $child->fresh()->password));
    }

    public function test_an_expired_guardian_link_is_rejected(): void
    {
        Mail::fake();

        [$guardian, $parent, $child] = $this->makeFamily();

        $this->post(route('password.email'), ['login' => $child->username]);
        [$token, $uid] = $this->tokenFromMail(Mail::sent(LearnerPasswordResetMail::class)->first());

        DB::table('password_resets')
            ->where('email', 'noemail:' . $child->id)
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->from(route('password.request'))
            ->post(route('password.update'), [
                'token' => $token,
                'uid' => $uid,
                'password' => 'too-late-password',
                'password_confirmation' => 'too-late-password',
            ])->assertSessionHasErrors('token');

        $this->assertTrue(Hash::check('old-child-password', $child->fresh()->password));
    }

    public function test_sibling_reset_links_are_independent(): void
    {
        Mail::fake();

        [$guardian, $parent, $firstChild] = $this->makeFamily();
        [, , $secondChild] = $this->makeFamily(false, $parent);

        $this->post(route('password.email'), ['login' => $firstChild->username]);
        $this->post(route('password.email'), ['login' => $secondChild->username]);

        $tokensByUser = [];

        foreach (Mail::sent(LearnerPasswordResetMail::class) as $mail) {
            [$token, $uid] = $this->tokenFromMail($mail);
            $tokensByUser[$uid] = $token;
        }

        $this->assertArrayHasKey($firstChild->id, $tokensByUser);
        $this->assertArrayHasKey($secondChild->id, $tokensByUser);
        $this->assertNotSame($tokensByUser[$firstChild->id], $tokensByUser[$secondChild->id]);

        // A token only works for the child it was issued to.
        $this->from(route('password.request'))
            ->post(route('password.update'), [
                'token' => $tokensByUser[$firstChild->id],
                'uid' => $secondChild->id,
                'password' => 'not-my-token-password',
                'password_confirmation' => 'not-my-token-password',
            ])->assertSessionHasErrors('token');

        $this->post(route('password.update'), [
            'token' => $tokensByUser[$firstChild->id],
            'uid' => $firstChild->id,
            'password' => 'first-child-password',
            'password_confirmation' => 'first-child-password',
        ])->assertRedirect(route('learner.dashboard'));

        $this->assertTrue(Hash::check('first-child-password', $firstChild->fresh()->password));
        $this->assertTrue(Hash::check('old-child-password', $secondChild->fresh()->password));
    }

    public function test_a_child_with_their_own_email_uses_the_standard_link(): void
    {
        Notification::fake();
        Mail::fake();

        [$guardian, $parent, $child] = $this->makeFamily(true);

        $this->post(route('password.email'), ['login' => $child->username])
            ->assertSessionHas('status');

        Notification::assertSentTo($child->fresh(), ResetPassword::class);
        Mail::assertNotSent(LearnerPasswordResetMail::class);
    }

    public function test_a_parent_can_send_their_child_a_reset_link(): void
    {
        Mail::fake();

        [$guardian, $parent, $child] = $this->makeFamily();

        $this->actingAs($guardian)
            ->from(route('parent.dashboard'))
            ->post(route('parent.children.password-reset', $child->learner))
            ->assertRedirect(route('parent.dashboard'))
            ->assertSessionHas('success');

        Mail::assertSent(LearnerPasswordResetMail::class, function (LearnerPasswordResetMail $mail) use ($guardian) {
            return $mail->hasTo($guardian->email);
        });

        $this->assertSame(1, DB::table('password_resets')->where('email', 'noemail:' . $child->id)->count());
    }

    public function test_a_parent_cannot_reset_another_familys_child(): void
    {
        Mail::fake();

        [, , $child] = $this->makeFamily();

        $stranger = $this->makeUser('parent', 'Stranger Parent');
        ParentModel::create(['user_id' => $stranger->id]);

        $this->actingAs($stranger)
            ->from(route('parent.dashboard'))
            ->post(route('parent.children.password-reset', $child->learner))
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_the_reset_page_does_not_reveal_a_username_without_a_valid_token(): void
    {
        [, , $child] = $this->makeFamily();

        $this->followingRedirects()
            ->get(route('password.reset', ['token' => 'not-a-real-token', 'uid' => $child->id]))
            ->assertOk()
            ->assertSee('Forgot your password?')
            ->assertDontSee($child->username);
    }

    public function test_an_admin_hands_a_childs_reset_link_to_the_guardian_when_mail_is_off(): void
    {
        // phpunit runs the array mailer, like UAT's log driver: nothing is delivered.
        [, , $child] = $this->makeFamily();
        $admin = $this->makeUser('admin', 'Reset Admin');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.password-reset', $child))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('password_reset_link');

        $link = session('password_reset_link');

        $this->assertStringContainsString('/password/reset/', $link);
        $this->assertStringContainsString('uid=', $link);
    }

    public function test_an_admin_reset_for_a_child_emails_the_guardian_when_mail_is_configured(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::fake();

        [$guardian, , $child] = $this->makeFamily();
        $admin = $this->makeUser('admin', 'Reset Mail Admin');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.password-reset', $child))
            ->assertSessionHas('success');

        Mail::assertSent(LearnerPasswordResetMail::class, function (LearnerPasswordResetMail $mail) use ($guardian) {
            return $mail->hasTo($guardian->email);
        });
    }
}
