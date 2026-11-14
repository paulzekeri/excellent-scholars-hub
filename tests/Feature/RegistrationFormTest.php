<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The public "Create your account" form registers learners only. Tutors are
 * staff, so an administrator or the CEO adds them from Users > Add user, and a
 * crafted tutor role_id is refused.
 */
class RegistrationFormTest extends TestCase
{
    use DatabaseTransactions;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    public function test_the_registration_form_is_a_learner_only_notice_with_no_role_switch(): void
    {
        $response = $this->get(route('register'))->assertOk();

        $html = $response->getContent();

        // A plain notice explains what the form does — there is no role picker.
        $this->assertNotFalse(strpos($html, 'Register to learn'));
        $this->assertLessThan(
            strpos($html, 'name="name"'),
            strpos($html, 'Register to learn'),
            'The learner notice should come before the name fields.'
        );

        // Only a learner may self-register. Tutors are added by an administrator
        // or the CEO, and a parent cannot self-register at all.
        $response->assertSee('name="role_id"', false)
            ->assertSee('data-role-slug="learner"', false)
            ->assertDontSee('type="radio"', false)
            ->assertDontSee('data-role-slug="tutor"', false)
            ->assertDontSee('data-role-slug="parent"', false);

        // The learner role travels with the form in a hidden field.
        $this->assertMatchesRegularExpression(
            '/<input type="hidden" name="role_id" value="\d+"/',
            $html,
            'The learner role should be posted with the form.'
        );
    }

    public function test_the_fields_use_learner_wording_so_they_are_not_confused_with_the_parent_fields(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Learner Details')
            ->assertSee('Learner Full Name')
            ->assertSee('Learner Username')
            ->assertSee('Learner Email')
            ->assertSee('Learner Phone')
            ->assertSee('Learner Password')
            ->assertSee('Parent/Guardian Details')
            ->assertSee('Parent/Guardian Full Name')
            ->assertSee('Parent/Guardian Email');
    }

    public function test_a_parent_cannot_register_themselves_through_the_public_form(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => 'Sneaky Parent',
            'email' => 'sneaky.parent.' . uniqid() . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('parent'),
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['name' => 'Sneaky Parent']);
    }

    public function test_a_tutor_cannot_register_through_the_public_form(): void
    {
        $email = 'self.tutor.' . uniqid() . '@example.com';

        $this->from(route('register'))->post(route('register'), [
            'name' => 'Self Tutor',
            'username' => 'self_tutor_' . uniqid(),
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('tutor'),
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }
}
