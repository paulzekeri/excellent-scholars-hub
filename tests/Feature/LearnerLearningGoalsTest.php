<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Learner;
use App\Models\LearningGoal;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Note 7 — administrators can share a learning goal with the learner, who then
 * sees it on their dashboard and "My Learning Goals" page.
 */
class LearnerLearningGoalsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    /** @return array{0: User, 1: Tutor} */
    private function makeTutor(string $name): array
    {
        $user = $this->makeUser('tutor', $name);
        $tutor = Tutor::create(['user_id' => $user->id]);

        return [$user, $tutor];
    }

    private function makeClass(Tutor $tutor, string $name): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'LG-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
        ]);
    }

    private function makeLearner(string $name, ?Classes $class = null): array
    {
        $user = $this->makeUser('learner', $name);
        $learner = Learner::create(['user_id' => $user->id]);

        if ($class) {
            $class->learners()->attach($learner->id);
        }

        return [$user, $learner];
    }

    private function makeGoal(Tutor $tutor, array $overrides = []): LearningGoal
    {
        return LearningGoal::create(array_merge([
            'tutor_id' => $tutor->id,
            'title' => 'Goal ' . uniqid(),
            'description' => 'Work through two pages each day.',
            'target' => 'Finish the term workbook',
            'status' => 'active',
        ], $overrides));
    }

    public function test_a_goal_shared_with_the_learner_appears_on_their_dashboard_and_page(): void
    {
        [, $tutor] = $this->makeTutor('Goal Tutor');
        [$learnerUser, $learner] = $this->makeLearner('Goal Learner');

        $goal = $this->makeGoal($tutor, [
            'learner_id' => $learner->id,
            'title' => 'Read for twenty minutes daily',
            'shared_with_learner' => true,
            'shared_by' => $learnerUser->id,
            'shared_at' => now(),
        ]);

        $this->actingAs($learnerUser)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Goals shared with you')
            ->assertSee('Read for twenty minutes daily');

        $this->actingAs($learnerUser)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertSee('My Learning Goals')
            ->assertSee('Read for twenty minutes daily');

        $this->assertTrue($goal->isVisibleToLearner($learner));
    }

    public function test_a_goal_not_shared_with_the_learner_stays_hidden(): void
    {
        [, $tutor] = $this->makeTutor('Hidden Goal Tutor');
        [$learnerUser, $learner] = $this->makeLearner('Hidden Goal Learner');

        $this->makeGoal($tutor, [
            'learner_id' => $learner->id,
            'title' => 'Parent-only goal',
            'shared_with_parent' => true,
            'shared_with_learner' => false,
        ]);

        $this->actingAs($learnerUser)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertDontSee('Parent-only goal');

        $this->actingAs($learnerUser)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertDontSee('Parent-only goal');
    }

    public function test_class_wide_goals_reach_only_the_learners_in_that_class(): void
    {
        [, $tutor] = $this->makeTutor('Class Goal Tutor');
        $classA = $this->makeClass($tutor, 'Class A');
        $classB = $this->makeClass($tutor, 'Class B');

        [$learnerAUser] = $this->makeLearner('Class A Learner', $classA);
        [$learnerBUser] = $this->makeLearner('Class B Learner', $classB);

        $goal = $this->makeGoal($tutor, [
            'class_id' => $classA->id,
            'title' => 'Class A shared goal',
            'shared_with_learner' => true,
            'shared_at' => now(),
        ]);

        $this->actingAs($learnerAUser)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Class A shared goal');

        $this->actingAs($learnerBUser)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertDontSee('Class A shared goal');

        $this->actingAs($learnerBUser)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertDontSee('Class A shared goal');

        $this->actingAs($learnerBUser)
            ->get(route('learner.learning-goals.download', $goal))
            ->assertForbidden();
    }

    public function test_a_learner_cannot_download_a_goal_that_was_not_shared_with_them(): void
    {
        [, $tutor] = $this->makeTutor('Download Guard Tutor');
        [$learnerAUser] = $this->makeLearner('No Download Learner A');
        [, $learnerB] = $this->makeLearner('No Download Learner B');

        $goal = $this->makeGoal($tutor, [
            'learner_id' => $learnerB->id,
            'shared_with_learner' => true,
            'file_path' => 'learning-goals/secret.pdf',
            'file_name' => 'secret.pdf',
        ]);

        $this->actingAs($learnerAUser)
            ->get(route('learner.learning-goals.download', $goal))
            ->assertForbidden();
    }

    public function test_the_attachment_downloads_for_the_learner_it_was_shared_with(): void
    {
        Storage::fake('public');

        [, $tutor] = $this->makeTutor('Attachment Tutor');
        [$learnerUser, $learner] = $this->makeLearner('Attachment Learner');

        Storage::disk('public')->put('learning-goals/plan.pdf', 'pdf');

        $goal = $this->makeGoal($tutor, [
            'learner_id' => $learner->id,
            'shared_with_learner' => true,
            'file_path' => 'learning-goals/plan.pdf',
            'file_name' => 'plan.pdf',
        ]);

        $this->actingAs($learnerUser)
            ->get(route('learner.learning-goals.download', $goal))
            ->assertDownload('plan.pdf');
    }

    public function test_the_admin_share_form_controls_the_learner_switch(): void
    {
        $admin = $this->makeUser('admin', 'Share Switch Admin');
        [, $tutor] = $this->makeTutor('Share Switch Tutor');
        [$learnerUser, $learner] = $this->makeLearner('Share Switch Learner');

        $goal = $this->makeGoal($tutor, [
            'learner_id' => $learner->id,
            'title' => 'Toggle for learner',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.learning-goals.index'))
            ->post(route('admin.learning-goals.share', $goal), [
                'shared_with_learner' => '1',
            ])
            ->assertSessionHas('success');

        $goal->refresh();
        $this->assertTrue($goal->shared_with_learner);
        $this->assertSame($admin->id, $goal->shared_by);
        $this->assertNotNull($goal->shared_at);

        $this->actingAs($learnerUser)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertSee('Toggle for learner');

        $this->actingAs($admin)
            ->from(route('admin.learning-goals.index'))
            ->post(route('admin.learning-goals.share', $goal), [
                'shared_with_learner' => '0',
            ])
            ->assertSessionHas('success');

        $this->assertFalse($goal->fresh()->shared_with_learner);

        // Drop the admin's flash message so the page only contains the goals.
        $this->flushSession();

        $this->actingAs($learnerUser)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertDontSee('Toggle for learner');
    }

    public function test_only_learners_can_open_the_learner_goal_screen(): void
    {
        foreach (['tutor', 'parent', 'admin'] as $role) {
            $user = $this->makeUser($role, 'No Learner Goals ' . $role);

            $this->actingAs($user)
                ->get(route('learner.learning-goals.index'))
                ->assertForbidden();
        }

        \Illuminate\Support\Facades\Auth::logout();
        $this->flushSession();

        $this->get(route('learner.learning-goals.index'))->assertRedirect(route('login'));
    }
}
