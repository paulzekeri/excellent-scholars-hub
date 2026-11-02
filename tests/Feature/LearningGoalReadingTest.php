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
 * A goal can be 5,000 characters long, so the list screens show an excerpt with
 * a "Read the full goal" toggle — the admin, the tutor who wrote it and the
 * people it was shared with must all be able to read the whole text.
 */
class LearningGoalReadingTest extends TestCase
{
    use DatabaseTransactions;

    /** Marker that only appears at the very end of the long goal. */
    private const TAIL = 'FINAL LINE OF THE GOAL';

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

    private function longGoalText(): string
    {
        return "Master variables and expressions.\n"
            . str_repeat('Simplify expressions by combining like terms and applying the distributive property. ', 45)
            . self::TAIL;
    }

    /**
     * A tutor, their class, a parent and a learner — plus a long goal that is
     * shared with the parent and the learner.
     *
     * @return array{admin: User, tutorUser: User, tutor: Tutor, parent: ParentModel, learner: Learner, goal: LearningGoal}
     */
    private function scenario(): array
    {
        $admin = $this->makeUser('admin', 'Reading Admin');

        $tutorUser = $this->makeUser('tutor', 'Reading Tutor');
        $tutor = Tutor::create(['user_id' => $tutorUser->id]);
        $class = Classes::create([
            'name' => 'Reading Class',
            'code' => 'RG-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);

        $parent = ParentModel::create(['user_id' => $this->makeUser('parent', 'Reading Parent')->id]);
        $learnerUser = $this->makeUser('learner', 'Reading Learner');
        $learner = Learner::create([
            'user_id' => $learnerUser->id,
            'parent_id' => $parent->id,
            'grade_level' => 'JSS 1',
        ]);
        $class->learners()->attach($learner->id);

        $goal = LearningGoal::create([
            'tutor_id' => $tutor->id,
            'learner_id' => $learner->id,
            'class_id' => $class->id,
            'title' => $this->longGoalText(),
            'status' => 'active',
            'shared_with_parent' => true,
            'shared_with_learner' => true,
            'shared_by' => $admin->id,
            'shared_at' => now(),
        ]);

        return compact('admin', 'tutorUser', 'tutor', 'parent', 'learner', 'goal');
    }

    public function test_the_admin_can_open_a_long_goal_and_read_all_of_it(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['admin'])
            ->get(route('admin.learning-goals.index', ['tutor_id' => $data['tutor']->id]))
            ->assertOk()
            ->assertSee('Master variables and expressions')
            ->assertSee('Read the full goal')
            ->assertSee(self::TAIL);
    }

    public function test_the_tutor_who_wrote_a_long_goal_can_read_all_of_it(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['tutorUser'])
            ->get(route('tutor.learning-goals.index'))
            ->assertOk()
            ->assertSee('Master variables and expressions')
            ->assertSee('Read the full goal')
            ->assertSee(self::TAIL);
    }

    public function test_the_people_a_long_goal_was_shared_with_read_it_in_full(): void
    {
        $data = $this->scenario();

        // The dashboard preview stays short, but links to the full goal.
        $this->actingAs($data['learner']->user)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Master variables and expressions')
            ->assertSee(route('learner.learning-goals.index'), false);

        // The learner's goals page shows the whole text (no excerpt).
        $this->actingAs($data['learner']->user)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertSee('Master variables and expressions')
            ->assertSee(self::TAIL);

        // So does the parent's goals tab for the child.
        $this->actingAs($data['parent']->user)
            ->get(route('parent.child.progress', ['learner' => $data['learner'], 'tab' => 'goals']))
            ->assertOk()
            ->assertSee('Master variables and expressions')
            ->assertSee(self::TAIL);
    }

    public function test_a_short_goal_shows_no_read_more_toggle(): void
    {
        $data = $this->scenario();

        $data['goal']->update(['title' => 'Read for twenty minutes every day.']);

        $this->actingAs($data['admin'])
            ->get(route('admin.learning-goals.index', ['tutor_id' => $data['tutor']->id]))
            ->assertOk()
            ->assertSee('Read for twenty minutes every day.')
            ->assertDontSee('Read the full goal');
    }
}
