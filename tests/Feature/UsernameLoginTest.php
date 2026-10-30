<?php

namespace Tests\Feature;

use App\Mail\AccountProvisionedMail;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Note 12 — children without a mailbox sign in with a unique username, and the
 * guardian's email is mandatory because it is where details and resets go.
 */
class UsernameLoginTest extends TestCase
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

    public function test_the_login_page_accepts_an_email_or_a_username(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Email or username')
            ->assertSee('name="login"', false);
    }

    public function test_a_child_without_an_email_signs_in_with_their_username(): void
    {
        $guardian = $this->makeUser('parent', 'Guardian One');
        $parent = ParentModel::create(['user_id' => $guardian->id]);

        $child = $this->makeUser('learner', 'Tolu Okafor', [
            'username' => 'tolu_okafor',
            'email' => null,
        ]);
        Learner::create(['user_id' => $child->id, 'parent_id' => $parent->id]);

        $this->post(route('login'), [
            'login' => 'tolu_okafor',
            'password' => 'password123',
        ])->assertRedirect(route('learner.dashboard'));

        $this->assertAuthenticatedAs($child->fresh());
    }

    public function test_a_parent_can_still_sign_in_with_an_email_in_the_same_field(): void
    {
        $parent = $this->makeUser('parent', 'Guardian Email');

        $this->post(route('login'), [
            'login' => $parent->email,
            'password' => 'password123',
        ])->assertRedirect(route('parent.dashboard'));

        $this->assertAuthenticatedAs($parent->fresh());
    }

    public function test_a_parent_can_sign_in_with_their_new_username_too(): void
    {
        $parent = $this->makeUser('parent', 'Guardian Username', ['username' => 'guardian_username']);

        $this->post(route('login'), [
            'login' => 'guardian_username',
            'password' => 'password123',
        ])->assertRedirect(route('parent.dashboard'));

        $this->assertAuthenticatedAs($parent->fresh());
    }

    public function test_an_inactive_account_cannot_sign_in_even_with_a_username(): void
    {
        $this->makeUser('learner', 'Inactive Child', [
            'username' => 'inactive_child',
            'email' => null,
            'is_active' => false,
        ]);

        $this->from(route('login'))->post(route('login'), [
            'login' => 'inactive_child',
            'password' => 'password123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_registration_creates_a_child_with_a_username_and_no_email_and_mails_the_guardian(): void
    {
        Mail::fake();

        $guardianEmail = 'guardian.' . uniqid() . '@example.com';

        $this->post(route('register'), [
            'name' => 'Ada Nwosu',
            'username' => 'ada_nwosu',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('learner'),
            'parent_name' => 'Mr Nwosu',
            'parent_email' => $guardianEmail,
        ])->assertRedirect(route('learner.dashboard'));

        $child = User::where('username', 'ada_nwosu')->firstOrFail();

        $this->assertNull($child->email);
        $this->assertNotNull($child->learner);
        $this->assertNotNull($child->learner->parent_id);
        $this->assertSame($guardianEmail, $child->deliveryEmail());

        // The child chose their own password at registration.
        $this->assertFalse($child->must_change_password);

        // The guardian account is provisioned and must choose its own password.
        $guardian = $child->learner->parent->user;
        $this->assertTrue($guardian->must_change_password);

        Mail::assertSent(AccountProvisionedMail::class, function (AccountProvisionedMail $mail) use ($guardianEmail) {
            return $mail->hasTo($guardianEmail)
                && in_array('ada_nwosu', array_column($mail->linkedAccounts, 'username'), true);
        });
    }

    public function test_registration_without_a_guardian_email_is_refused(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => 'No Guardian',
            'username' => 'no_guardian_child',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('learner'),
            'parent_name' => 'Guardian',
            'parent_email' => '',
        ])->assertSessionHasErrors('parent_email');

        $this->assertDatabaseMissing('users', ['username' => 'no_guardian_child']);
    }

    public function test_a_child_can_register_with_their_own_email_and_it_is_used_for_delivery(): void
    {
        Mail::fake();

        $ownEmail = 'child.' . uniqid() . '@example.com';

        $this->post(route('register'), [
            'name' => 'Child With Email',
            'username' => 'child_with_email',
            'email' => $ownEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('learner'),
            'parent_name' => 'Guardian Of Child',
            'parent_email' => 'guardian.' . uniqid() . '@example.com',
        ])->assertRedirect(route('learner.dashboard'));

        $child = User::where('username', 'child_with_email')->firstOrFail();

        $this->assertSame($ownEmail, $child->email);
        $this->assertSame($ownEmail, $child->deliveryEmail());
        $this->assertNotNull($child->learner->parent_id);
    }

    public function test_two_siblings_can_share_one_guardian_email(): void
    {
        $guardianEmail = 'shared.guardian.' . uniqid() . '@example.com';

        foreach (['sibling_one', 'sibling_two'] as $username) {
            $this->post(route('register'), [
                'name' => 'Sibling ' . $username,
                'username' => $username,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('learner'),
                'parent_name' => 'Shared Guardian',
                'parent_email' => $guardianEmail,
            ])->assertRedirect(route('learner.dashboard'));

            // Registration signs the new child in; the next sibling needs a guest session.
            Auth::logout();
            $this->flushSession();
        }

        $one = User::where('username', 'sibling_one')->firstOrFail();
        $two = User::where('username', 'sibling_two')->firstOrFail();

        $this->assertSame($one->learner->parent_id, $two->learner->parent_id);
        $this->assertNotSame($one->id, $two->id);
    }

    public function test_a_staff_email_cannot_be_used_as_the_guardian_address(): void
    {
        $tutor = $this->makeUser('tutor', 'Staff Tutor');

        $this->from(route('register'))->post(route('register'), [
            'name' => 'Child Of Staff',
            'username' => 'child_of_staff',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('learner'),
            'parent_name' => 'Staff Tutor',
            'parent_email' => $tutor->email,
        ])->assertSessionHasErrors('parent_email');

        $this->assertDatabaseMissing('users', ['username' => 'child_of_staff']);
    }

    public function test_a_username_can_only_be_used_once(): void
    {
        $this->makeUser('learner', 'Existing Child', ['username' => 'taken_name']);

        $this->from(route('register'))->post(route('register'), [
            'name' => 'Another Child',
            'username' => 'taken_name',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('learner'),
            'parent_name' => 'Guardian',
            'parent_email' => 'guardian.' . uniqid() . '@example.com',
        ])->assertSessionHasErrors('username');
    }

    public function test_a_child_email_must_differ_from_the_guardian_email(): void
    {
        $email = 'same.' . uniqid() . '@example.com';

        $this->from(route('register'))->post(route('register'), [
            'name' => 'Same Email Child',
            'username' => 'same_email_child',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('learner'),
            'parent_name' => 'Same Email Guardian',
            'parent_email' => $email,
        ])->assertSessionHasErrors('email');
    }
}
