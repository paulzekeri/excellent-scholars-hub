<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The in-app guide is role-aware: each account only sees the sections for
 * screens it can actually open. The CEO shares the admin area but also owns the
 * finance screens, so the two guides are not identical.
 */
class UserGuideRolesTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ]);
    }

    private function guide(User $user)
    {
        return $this->actingAs($user)->get(route('help.user-guide'))->assertOk();
    }

    public function test_an_administrator_sees_the_admin_guide_without_finance_or_other_roles(): void
    {
        $this->guide($this->makeUser('admin', 'School Admin'))
            ->assertSee('Showing the guide for your role: Administrator')
            ->assertSee('For Administrators')
            ->assertSee('Categorised messages (broadcasts)')
            ->assertDontSee('5. Verifying Fees')
            ->assertDontSee('Step 1 — record your payment')
            ->assertDontSee('Step 2 — the CEO verifies it against the bank')
            ->assertDontSee('For Tutors')
            ->assertDontSee('For Learners (Students)')
            ->assertDontSee('For Parents/Guardians');
    }

    public function test_the_ceo_sees_admin_and_finance_sections_only(): void
    {
        $this->guide($this->makeUser('ceo', 'Chief Executive'))
            ->assertSee('Showing the guide for your role: CEO')
            ->assertSee('For Administrators &amp; CEO', false)
            ->assertSee('5. Verifying Fees')
            ->assertSee('School bank accounts — add, switch off, delete')
            ->assertSee('Step 2 — the CEO verifies it against the bank')
            ->assertDontSee('Step 1 — record your payment')
            ->assertDontSee('For Tutors')
            ->assertDontSee('For Learners (Students)')
            ->assertDontSee('For Parents/Guardians');
    }

    public function test_a_tutor_only_sees_teaching_sections(): void
    {
        $this->guide($this->makeUser('tutor', 'Class Tutor'))
            ->assertSee('Showing the guide for your role: Tutor')
            ->assertSee('For Tutors')
            ->assertSee('For tutors — uploading a goal')
            ->assertSee('Starting a conversation')
            ->assertDontSee('For Administrators')
            ->assertDontSee('For Learners (Students)')
            ->assertDontSee('For Parents/Guardians')
            ->assertDontSee('Categorised messages (broadcasts)')
            ->assertDontSee('Monthly classes per tutor')
            ->assertDontSee('5. Verifying Fees');
    }

    public function test_a_learner_only_sees_learning_sections(): void
    {
        $this->guide($this->makeUser('learner', 'Young Learner'))
            ->assertSee('Showing the guide for your role: Learner')
            ->assertSee('For Learners (Students)')
            ->assertSee('Now / Up next panel')
            ->assertDontSee('For Administrators')
            ->assertDontSee('For Tutors')
            ->assertDontSee('For Parents/Guardians')
            ->assertDontSee('5. Verifying Fees')
            ->assertDontSee('Monthly classes per tutor')
            ->assertDontSee('Learning goals');
    }

    public function test_a_parent_only_sees_fee_and_child_sections(): void
    {
        $this->guide($this->makeUser('parent', 'School Parent'))
            ->assertSee('Showing the guide for your role: Parent / Guardian')
            ->assertSee('For Parents/Guardians')
            ->assertSee('Step 1 — record your payment')
            ->assertSee('Fees &amp; Payments', false)
            ->assertSee("How do I see my child's grades?", false)
            ->assertDontSee('For Administrators')
            ->assertDontSee('For Tutors')
            ->assertDontSee('For Learners (Students)')
            ->assertDontSee('5. Verifying Fees')
            ->assertDontSee('Step 2 — the CEO verifies it against the bank')
            ->assertDontSee('Categorised messages (broadcasts)')
            ->assertDontSee('Where do I add the bank account parents should pay into?');
    }

    public function test_every_role_keeps_the_shared_sections(): void
    {
        foreach (['admin', 'ceo', 'tutor', 'learner', 'parent'] as $slug) {
            $this->guide($this->makeUser($slug, ucfirst($slug) . ' User'))
                ->assertSee('Getting Started')
                ->assertSee('Messages')
                ->assertSee('Notifications')
                ->assertSee('FAQ & Troubleshooting', false)
                ->assertSee('How do I log out?');
        }
    }
}
