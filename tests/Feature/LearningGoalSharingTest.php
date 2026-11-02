<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\LearningGoal;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Picking people by name must never look like "every parent / learner / tutor".
 * The three "every …" ticks are recorded on their own columns so the screen can
 * show exactly what the school chose.
 */
class LearningGoalSharingTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    /**
     * An admin, a goal written by a tutor for a learner, and one parent, learner
     * and second tutor the goal can be shared with by name.
     *
     * @return array{0: User, 1: LearningGoal, 2: array<string, User>}
     */
    private function scenario(): array
    {
        $admin = $this->makeUser('admin', 'Share Admin');

        $tutorUser = $this->makeUser('tutor', 'Goal Tutor One');
        $tutor = Tutor::create(['user_id' => $tutorUser->id]);
        $class = Classes::create([
            'name' => 'Share Class',
            'code' => 'LS-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);

        $parentUser = $this->makeUser('parent', 'Goal Parent One');
        $parent = ParentModel::create(['user_id' => $parentUser->id]);

        $learnerUser = $this->makeUser('learner', 'Goal Learner One');
        $learner = Learner::create([
            'user_id' => $learnerUser->id,
            'parent_id' => $parent->id,
            'grade_level' => 'JSS 1',
        ]);
        $class->learners()->attach($learner->id);

        $secondTutor = $this->makeUser('tutor', 'Goal Tutor Two');

        $goal = LearningGoal::create([
            'tutor_id' => $tutor->id,
            'learner_id' => $learner->id,
            'class_id' => $class->id,
            'title' => 'Master variables and expressions',
            'status' => 'active',
        ]);

        return [$admin, $goal, [
            'parent' => $parentUser,
            'learner' => $learnerUser,
            'tutor' => $secondTutor,
            'author_tutor' => $tutor,
        ]];
    }

    public function test_choosing_people_by_name_does_not_tick_the_everyone_boxes(): void
    {
        [$admin, $goal, $people] = $this->scenario();

        $this->actingAs($admin)->post(route('admin.learning-goals.share', $goal), [
            'share_form' => 1,
            'parent_ids' => [$people['parent']->id],
            'learner_ids' => [$people['learner']->id],
            'tutor_ids' => [$people['tutor']->id],
        ])->assertRedirect()->assertSessionHas('success');

        $goal->refresh();

        // The explicit "every …" tick stays off for all three roles.
        $this->assertFalse($goal->sharesWithAll('parent'));
        $this->assertFalse($goal->sharesWithAll('learner'));
        $this->assertFalse($goal->sharesWithAll('tutor'));

        // Sharing still counts for the filters, and the names are kept.
        $this->assertTrue($goal->shared_with_parent);
        $this->assertTrue($goal->shared_with_learner);
        $this->assertTrue($goal->shared_with_tutor);
        $this->assertCount(3, $goal->recipients);

        $this->flushSession();

        // Filter to this goal's tutor so no other goal on the screen can answer
        // the "All …" assertions for us.
        $this->actingAs($admin)
            ->get(route('admin.learning-goals.index', ['tutor_id' => $people['author_tutor']->id]))
            ->assertOk()
            ->assertSee('Goal Parent One')
            ->assertSee('Goal Tutor Two')
            ->assertDontSee('data-share-chip="all-parent"', false)
            ->assertDontSee('data-share-chip="all-learner"', false)
            ->assertDontSee('data-share-chip="all-tutor"', false)
            ->assertDontSee('name="share_all_parent" value="1" checked', false)
            ->assertDontSee('name="share_all_learner" value="1" checked', false)
            ->assertDontSee('name="share_all_tutor" value="1" checked', false);
    }

    public function test_ticking_every_learner_records_only_that_role(): void
    {
        [$admin, $goal, $people] = $this->scenario();

        $this->actingAs($admin)->post(route('admin.learning-goals.share', $goal), [
            'share_form' => 1,
            'share_all_parent' => 0,
            'share_all_learner' => 1,
            'share_all_tutor' => 0,
        ])->assertRedirect()->assertSessionHas('success');

        $goal->refresh();

        $this->assertTrue($goal->sharesWithAll('learner'));
        $this->assertFalse($goal->sharesWithAll('parent'));
        $this->assertFalse($goal->sharesWithAll('tutor'));
        $this->assertTrue($goal->shared_with_learner);
        $this->assertFalse($goal->shared_with_parent);

        $this->flushSession();

        $this->actingAs($admin)
            ->get(route('admin.learning-goals.index', ['tutor_id' => $people['author_tutor']->id]))
            ->assertOk()
            ->assertSee('data-share-chip="all-learner"', false)
            ->assertDontSee('data-share-chip="all-parent"', false)
            ->assertDontSee('data-share-chip="all-tutor"', false)
            ->assertSee('name="share_all_learner" value="1" checked', false)
            ->assertDontSee('name="share_all_parent" value="1" checked', false);
    }

    public function test_a_named_tutor_can_be_added_on_top_of_every_parent(): void
    {
        [$admin, $goal, $people] = $this->scenario();

        $this->actingAs($admin)->post(route('admin.learning-goals.share', $goal), [
            'share_form' => 1,
            'share_all_parent' => 1,
            'tutor_ids' => [$people['tutor']->id],
        ])->assertRedirect()->assertSessionHas('success');

        $goal->refresh();

        $this->assertTrue($goal->sharesWithAll('parent'));
        $this->assertFalse($goal->sharesWithAll('tutor'));
        $this->assertCount(1, $goal->recipients);
        $this->assertSame($people['tutor']->id, $goal->recipients->first()->id);

        $this->flushSession();

        $this->actingAs($admin)
            ->get(route('admin.learning-goals.index', ['tutor_id' => $people['author_tutor']->id]))
            ->assertOk()
            ->assertSee('data-share-chip="all-parent"', false)
            ->assertDontSee('data-share-chip="all-tutor"', false)
            ->assertSee('Goal Tutor Two');
    }

    public function test_clearing_the_selection_withdraws_the_goal(): void
    {
        [$admin, $goal, $people] = $this->scenario();

        $this->actingAs($admin)->post(route('admin.learning-goals.share', $goal), [
            'share_form' => 1,
            'parent_ids' => [$people['parent']->id],
        ])->assertRedirect();

        $this->assertTrue($goal->fresh()->isShared());

        $this->actingAs($admin)->post(route('admin.learning-goals.share', $goal), [
            'share_form' => 1,
            'share_all_parent' => 0,
            'share_all_learner' => 0,
            'share_all_tutor' => 0,
        ])->assertRedirect()->assertSessionHas('success');

        $goal->refresh();

        $this->assertFalse($goal->isShared());
        $this->assertFalse($goal->shared_with_parent);
        $this->assertFalse($goal->shared_with_learner);
        $this->assertFalse($goal->shared_with_tutor);
        $this->assertFalse($goal->sharesWithAll('parent'));
        $this->assertSame(0, $goal->recipients()->count());
        $this->assertNull($goal->shared_at);
    }
}
