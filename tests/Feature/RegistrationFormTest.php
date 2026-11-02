<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * "I am a — Learner / Tutor" is the first question on the registration form and
 * it decides how the fields below it are worded, so a learner's own details can
 * never be confused with the parent/guardian block.
 */
class RegistrationFormTest extends TestCase
{
    use DatabaseTransactions;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    public function test_the_registration_form_asks_who_is_registering_before_any_other_field(): void
    {
        $response = $this->get(route('register'))->assertOk();

        $html = $response->getContent();

        $this->assertNotFalse(strpos($html, 'I am a'));
        $this->assertLessThan(
            strpos($html, 'name="name"'),
            strpos($html, 'I am a'),
            'The "I am a" choice should come before the name fields.'
        );

        // Learner is offered first, then tutor, and a parent cannot self-register.
        $this->assertLessThan(
            strpos($html, 'data-role-slug="tutor"'),
            strpos($html, 'data-role-slug="learner"'),
            'Learner should be listed before Tutor.'
        );
        $response->assertSee('name="role_id"', false)
            ->assertSee('data-role-slug="learner"', false)
            ->assertSee('data-role-slug="tutor"', false)
            ->assertDontSee('data-role-slug="parent"', false);
    }

    public function test_the_shared_fields_carry_learner_wording_so_they_are_not_confused_with_the_parent_fields(): void
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
            ->assertSee('Parent/Guardian Email')
            ->assertSee('data-label-learner="Learner Full Name"', false);
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

    public function test_a_tutor_can_still_register_from_the_same_form(): void
    {
        $email = 'self.tutor.' . uniqid() . '@example.com';

        $this->post(route('register'), [
            'name' => 'Self Tutor',
            'username' => 'self_tutor_' . uniqid(),
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('tutor'),
        ])->assertRedirect(route('tutor.dashboard'));

        $tutor = User::where('email', $email)->firstOrFail();

        $this->assertNotNull($tutor->tutor);
    }
}
