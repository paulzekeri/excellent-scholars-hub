<?php

namespace Tests\Feature;

use App\Mail\AccountProvisionedMail;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Admin-provisioned accounts (note 12): the school enters a username, an email
 * and a temporary password; the owner is emailed the details and must choose
 * their own password before using the app.
 */
class AccountProvisioningTest extends TestCase
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

    public function test_an_admin_creates_a_parent_with_username_email_and_temporary_password(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Provisioning Admin');
        $email = 'mrs.ada.' . uniqid() . '@example.com';

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Mrs Ada',
            'username' => 'mrs_ada',
            'email' => $email,
            'password' => 'temp-pass-123',
            'role_id' => $this->roleId('parent'),
        ])->assertRedirect(route('admin.users.index'));

        $parent = User::where('username', 'mrs_ada')->firstOrFail();

        $this->assertSame('MRS_ADA', strtoupper($parent->username));
        $this->assertNotNull($parent->parent);
        $this->assertTrue($parent->must_change_password);
        $this->assertTrue(Hash::check('temp-pass-123', $parent->password));

        Mail::assertSent(AccountProvisionedMail::class, function (AccountProvisionedMail $mail) use ($email) {
            return $mail->hasTo($email) && $mail->username === 'mrs_ada';
        });

        // First sign-in is blocked until the password is replaced.
        $this->actingAs($parent->fresh())
            ->get(route('parent.dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_an_admin_creates_a_tutor_and_the_login_details_are_emailed(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Tutor Admin');
        $email = 'new.tutor.' . uniqid() . '@example.com';

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Tutor',
            'username' => 'new_tutor',
            'email' => $email,
            'password' => 'tutor-pass-123',
            'role_id' => $this->roleId('tutor'),
        ])->assertRedirect(route('admin.users.index'));

        $tutor = User::where('email', $email)->firstOrFail();

        $this->assertNotNull($tutor->tutor);
        // The school chose this password, so the tutor keeps it until they change it.
        $this->assertFalse($tutor->must_change_password);
        $this->assertTrue(Hash::check('tutor-pass-123', $tutor->password));

        Mail::assertSent(AccountProvisionedMail::class, function (AccountProvisionedMail $mail) use ($email) {
            return $mail->hasTo($email) && $mail->username === 'new_tutor';
        });
    }

    public function test_an_admin_adds_a_child_to_a_parent_and_the_guardian_receives_the_details(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Child Admin');
        $guardian = $this->makeUser('parent', 'Guardian Parent', ['username' => 'guardian_parent']);
        $parent = ParentModel::create(['user_id' => $guardian->id]);
        $childEmail = 'junior.' . uniqid() . '@example.com';

        $this->actingAs($admin)->post(route('admin.users.children.store', $guardian), [
            'name' => 'Junior Okafor',
            'username' => 'junior_okafor',
            'email' => $childEmail,
            'password' => 'temp-pass-123',
        ])->assertRedirect(route('admin.users.edit', $guardian));

        $child = User::where('username', 'junior_okafor')->firstOrFail();

        $this->assertSame($parent->id, $child->learner->parent_id);
        $this->assertTrue($child->must_change_password);
        $this->assertTrue(Hash::check('temp-pass-123', $child->password));

        // Guardian always gets the details; the child's own address too.
        Mail::assertSent(AccountProvisionedMail::class, function (AccountProvisionedMail $mail) use ($guardian) {
            return $mail->hasTo($guardian->email) && $mail->guardianOf === 'Junior Okafor';
        });
        Mail::assertSent(AccountProvisionedMail::class, function (AccountProvisionedMail $mail) use ($childEmail) {
            return $mail->hasTo($childEmail);
        });
        $this->assertCount(2, Mail::sent(AccountProvisionedMail::class));
    }

    public function test_a_learner_cannot_be_created_without_a_guardian(): void
    {
        $admin = $this->makeUser('admin', 'Guardian Rule Admin');
        $email = 'orphan.' . uniqid() . '@example.com';

        $this->from(route('admin.users.create'))->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Orphan Learner',
            'username' => 'orphan_learner',
            'email' => $email,
            'password' => 'password123',
            'role_id' => $this->roleId('learner'),
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_a_duplicate_username_is_refused_case_insensitively(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Duplicate Admin');
        $this->makeUser('parent', 'Existing Parent', ['username' => 'mrs_ada']);

        $this->from(route('admin.users.create'))->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Another Parent',
            'username' => 'MRS_ADA',
            'email' => 'another.parent.' . uniqid() . '@example.com',
            'password' => 'password123',
            'role_id' => $this->roleId('parent'),
        ])->assertSessionHasErrors('username');
    }

    public function test_a_provisioned_child_is_sent_to_the_change_screen_on_first_sign_in(): void
    {
        $guardian = $this->makeUser('parent', 'Guardian For Temp Child');
        $parent = ParentModel::create(['user_id' => $guardian->id]);

        $child = $this->makeUser('learner', 'Temp Child', [
            'username' => 'temp_child',
            'email' => null,
            'password' => Hash::make('temp-pass-123'),
            'must_change_password' => true,
        ]);
        $child->learner()->create(['parent_id' => $parent->id]);

        $this->actingAs($child)
            ->get(route('learner.dashboard'))
            ->assertRedirect(route('password.change'));

        $this->actingAs($child)
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('Choose your own password');
    }

    public function test_a_flagged_user_cannot_reach_another_page_by_deep_link(): void
    {
        $parent = $this->makeUser('parent', 'Flagged Parent', [
            'username' => 'flagged_parent',
            'must_change_password' => true,
        ]);
        ParentModel::create(['user_id' => $parent->id]);

        $this->actingAs($parent)
            ->get(route('parent.payments.index'))
            ->assertRedirect(route('password.change'));
    }

    public function test_the_current_password_must_be_correct(): void
    {
        $child = $this->makeUser('learner', 'Wrong Current Child', [
            'username' => 'wrong_current_child',
            'email' => null,
            'password' => Hash::make('temp-pass-123'),
            'must_change_password' => true,
        ]);

        $this->actingAs($child)
            ->from(route('password.change'))
            ->post(route('password.change.update'), [
                'current_password' => 'not-the-temp-password',
                'password' => 'my-new-password',
                'password_confirmation' => 'my-new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($child->fresh()->must_change_password);
        $this->assertTrue(Hash::check('temp-pass-123', $child->fresh()->password));
    }

    public function test_the_new_password_must_differ_from_the_temporary_one(): void
    {
        $child = $this->makeUser('learner', 'Same Password Child', [
            'username' => 'same_password_child',
            'email' => null,
            'password' => Hash::make('temp-pass-123'),
            'must_change_password' => true,
        ]);

        $this->actingAs($child)
            ->from(route('password.change'))
            ->post(route('password.change.update'), [
                'current_password' => 'temp-pass-123',
                'password' => 'temp-pass-123',
                'password_confirmation' => 'temp-pass-123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($child->fresh()->must_change_password);
    }

    public function test_changing_the_password_unlocks_the_rest_of_the_app(): void
    {
        $guardian = $this->makeUser('parent', 'Guardian Unlock');
        $parent = ParentModel::create(['user_id' => $guardian->id]);

        $child = $this->makeUser('learner', 'Unlock Child', [
            'username' => 'unlock_child',
            'email' => null,
            'password' => Hash::make('temp-pass-123'),
            'must_change_password' => true,
        ]);
        $child->learner()->create(['parent_id' => $parent->id]);

        $this->actingAs($child)
            ->post(route('password.change.update'), [
                'current_password' => 'temp-pass-123',
                'password' => 'my-own-password',
                'password_confirmation' => 'my-own-password',
            ])
            ->assertRedirect(route('learner.dashboard'));

        $child->refresh();

        $this->assertFalse($child->must_change_password);
        $this->assertNotNull($child->password_changed_at);
        $this->assertTrue(Hash::check('my-own-password', $child->password));
        $this->assertFalse(Hash::check('temp-pass-123', $child->password));

        $this->actingAs($child)
            ->get(route('learner.dashboard'))
            ->assertOk();
    }

    public function test_logout_stays_reachable_for_a_flagged_user(): void
    {
        $child = $this->makeUser('learner', 'Flagged Logout Child', [
            'username' => 'flagged_logout_child',
            'email' => null,
            'must_change_password' => true,
        ]);

        $this->actingAs($child)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_the_admin_sees_the_credentials_when_email_cannot_deliver(): void
    {
        // phpunit.xml sets MAIL_MAILER=array, which cannot deliver — the same
        // situation as UAT's log driver.
        $admin = $this->makeUser('admin', 'Fallback Admin');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Unreachable Parent',
            'username' => 'unreachable_parent',
            'email' => 'unreachable.' . uniqid() . '@example.com',
            'password' => 'temp-pass-123',
            'role_id' => $this->roleId('parent'),
        ])->assertSessionHas('provisioned_credentials')->assertSessionHas('error');

        $provisioned = session('provisioned_credentials');

        $this->assertSame('unreachable_parent', $provisioned['username']);
        $this->assertSame('temp-pass-123', $provisioned['password']);
        $this->assertTrue($provisioned['must_change_password']);

        // The panel replaces the misleading "emailed" claim.
        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Login details for Unreachable Parent')
            ->assertSee('temp-pass-123');
    }

    public function test_a_child_added_without_a_working_mailer_shows_the_credentials_on_the_edit_page(): void
    {
        $admin = $this->makeUser('admin', 'Child Fallback Admin');
        $guardian = $this->makeUser('parent', 'Fallback Guardian', ['username' => 'fallback_guardian']);
        ParentModel::create(['user_id' => $guardian->id]);

        $response = $this->actingAs($admin)->post(route('admin.users.children.store', $guardian), [
            'name' => 'Fallback Child',
            'username' => 'fallback_child',
            'password' => 'temp-pass-456',
        ]);

        $response->assertRedirect(route('admin.users.edit', $guardian));
        $response->assertSessionHas('provisioned_credentials')->assertSessionHas('error');

        // The edit screen is where the administrator lands, so the panel must be there.
        $this->actingAs($admin)->get(route('admin.users.edit', $guardian))
            ->assertOk()
            ->assertSee('Login details for Fallback Child')
            ->assertSee('temp-pass-456');
    }
}
