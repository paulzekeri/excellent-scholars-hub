<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\LearningGoal;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the additions agreed at the client meeting:
 * part payments + receipts, optional class capacity, assignment pictures,
 * categorised messages, learning-goal sharing, the learner "up next" panel and
 * the monthly classes-per-tutor report.
 */
class ClientAdditionsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ], $extra));
    }

    /**
     * A real 1x1 PNG so the `image` validation rule passes without needing the
     * GD extension (which UploadedFile::fake()->image() relies on).
     */
    private function fakePng(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'esh-img');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function makeClassWithLearner(Tutor $tutor, ?Learner $learner, array $extra = []): Classes
    {
        $class = Classes::create(array_merge([
            'name' => 'Maths ' . uniqid(),
            'code' => 'MTH-' . uniqid(),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ], $extra));

        if ($learner) {
            $class->learners()->attach($learner->id);
        }

        return $class;
    }

    private function makeTutor(): Tutor
    {
        return Tutor::create(['user_id' => $this->makeUser('tutor', 'Tutor ' . uniqid())->id]);
    }

    private function makeLearner(?ParentModel $parent = null): Learner
    {
        return Learner::create([
            'user_id' => $this->makeUser('learner', 'Learner ' . uniqid())->id,
            'parent_id' => $parent ? $parent->id : null,
            'grade_level' => 'JSS 2',
        ]);
    }

    public function test_part_payments_share_one_running_balance(): void
    {
        $learner = $this->makeLearner();

        Payment::create([
            'user_id' => $learner->user_id,
            'learner_id' => $learner->id,
            'payment_type' => 'tuition',
            'term' => 'First Term',
            'amount' => 40000,
            'total_fee' => 100000,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        Payment::create([
            'user_id' => $learner->user_id,
            'learner_id' => $learner->id,
            'payment_type' => 'tuition',
            'term' => 'First Term',
            'amount' => 10000,
            'total_fee' => 100000,
            'status' => 'pending',
            'payment_date' => now(),
        ]);

        $summary = Payment::summaryFor($learner->id, 'First Term');

        $this->assertSame(100000.0, $summary['due']);
        $this->assertSame(40000.0, $summary['paid'], 'Pending installments must not reduce the balance.');
        $this->assertSame(60000.0, $summary['balance']);
        $this->assertSame(40, $summary['percent']);
        $this->assertSame(1, $summary['installments']);
    }

    public function test_administrators_cannot_reach_the_ceo_payment_screen(): void
    {
        $admin = $this->makeUser('admin', 'School Admin');

        $this->actingAs($admin)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_a_class_without_a_maximum_can_always_be_enrolled(): void
    {
        $admin = $this->makeUser('admin', 'School Admin');
        $tutor = $this->makeTutor();
        $class = $this->makeClassWithLearner($tutor, null, ['max_students' => null]);
        $learner = $this->makeLearner();

        $this->assertNull($class->max_students);
        $this->assertTrue($class->hasCapacity());

        $this->actingAs($admin)->post(route('admin.classes.enroll', $class), [
            'learner_id' => $learner->id,
        ])->assertRedirect();

        $this->assertTrue($class->learners()->where('learner_id', $learner->id)->exists());
    }

    public function test_a_tutor_can_attach_pictures_to_an_assignment(): void
    {
        Storage::fake('public');

        $tutor = $this->makeTutor();
        $class = $this->makeClassWithLearner($tutor, null);

        $this->actingAs($tutor->user)->post(route('tutor.assignments.store'), [
            'class_id' => $class->id,
            'title' => 'Diagram practice',
            'description' => 'Label the diagram.',
            'due_date' => now()->addWeek()->toDateString(),
            'total_points' => 20,
            'images' => [
                $this->fakePng('one.png'),
                $this->fakePng('two.png'),
            ],
        ])->assertRedirect(route('tutor.assignments.index'));

        $assignment = Assignment::where('title', 'Diagram practice')->firstOrFail();
        $this->assertCount(2, $assignment->imagePaths());

        foreach ($assignment->imagePaths() as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_an_administrator_can_broadcast_to_parents_only(): void
    {
        $admin = $this->makeUser('admin', 'School Admin');
        $parentOne = $this->makeUser('parent', 'Parent One');
        $parentTwo = $this->makeUser('parent', 'Parent Two');
        $learnerUser = $this->makeUser('learner', 'Learner One');

        $this->actingAs($admin)->post(route('admin.messages.store'), [
            'audience' => Message::AUDIENCE_PARENTS,
            'body' => 'Fees for next term are now due.',
        ])->assertRedirect(route('admin.messages.index'));

        $this->assertSame(2, Message::whereIn('receiver_id', [$parentOne->id, $parentTwo->id])
            ->where('audience', Message::AUDIENCE_PARENTS)
            ->count());
        $this->assertDatabaseHas('messages', ['receiver_id' => $parentOne->id, 'is_read' => false]);
        $this->assertDatabaseHas('messages', ['receiver_id' => $parentTwo->id, 'is_read' => false]);
        $this->assertDatabaseMissing('messages', ['receiver_id' => $learnerUser->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $parentOne->id, 'is_read' => false]);
        $this->assertDatabaseHas('notifications', ['user_id' => $parentTwo->id, 'is_read' => false]);
    }

    public function test_a_learning_goal_is_only_visible_to_the_parent_once_shared(): void
    {
        $tutor = $this->makeTutor();
        $parent = ParentModel::create(['user_id' => $this->makeUser('parent', 'Goal Parent')->id]);
        $learner = $this->makeLearner($parent);
        $class = $this->makeClassWithLearner($tutor, $learner);

        $goal = LearningGoal::create([
            'tutor_id' => $tutor->id,
            'learner_id' => $learner->id,
            'class_id' => $class->id,
            'title' => 'Read aloud weekly',
            'target' => 'Four out of five weeks',
            'term' => 'First Term',
        ]);

        $this->actingAs($parent->user)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'goals']))
            ->assertOk()
            ->assertDontSee('Read aloud weekly');

        $admin = $this->makeUser('admin', 'School Admin');

        $this->actingAs($admin)->post(route('admin.learning-goals.share', $goal), [
            'shared_with_parent' => 1,
            'shared_with_tutor' => 0,
        ])->assertRedirect();

        $goal->refresh();
        $this->assertTrue($goal->shared_with_parent);
        $this->assertSame($admin->id, $goal->shared_by);

        $this->actingAs($parent->user)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'goals']))
            ->assertOk()
            ->assertSee('Read aloud weekly');
    }

    public function test_the_learner_dashboard_highlights_the_next_class(): void
    {
        $tutor = $this->makeTutor();
        $learner = $this->makeLearner();
        $class = $this->makeClassWithLearner($tutor, $learner);

        ClassSchedule::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Algebra revision',
            'start_time' => now()->addHours(3),
            'end_time' => now()->addHours(4),
            'status' => 'scheduled',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $this->actingAs($learner->user)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Up next')
            ->assertSee('Algebra revision')
            ->assertSee('Join class');
    }

    public function test_the_sidebar_groups_the_menu_for_each_role(): void
    {
        $admin = $this->makeUser('admin', 'Sidebar Admin');
        $ceo = $this->makeUser('ceo', 'Sidebar Ceo');
        $tutor = $this->makeTutor();
        $parent = $this->makeUser('parent', 'Sidebar Parent');
        $learner = $this->makeLearner();

        // Sidebar shell + grouped admin menu, without the CEO-only payment link.
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('esh-sidebar', false)
            ->assertSee('Users & Roles')
            ->assertSee('Assignment Review')
            ->assertSee('Broadcasts')
            ->assertDontSee(route('admin.payments.index'), false);

        $this->actingAs($ceo)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Finance')
            ->assertSee(route('admin.payments.index'), false);

        // The page the user is on is highlighted in the sidebar.
        $this->actingAs($ceo)->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('bg-white/15 text-white', false);

        $this->actingAs($tutor->user)->get(route('tutor.dashboard'))
            ->assertOk()
            ->assertSee('Teaching')
            ->assertSee('Learning Goals')
            ->assertDontSee(route('admin.users.index'), false);

        $this->actingAs($parent)->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Communication')
            ->assertSee('Finance')
            ->assertSee(route('parent.payments.index'), false)
            ->assertDontSee('Assignment Review');

        $this->actingAs($learner->user)->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('My Learning')
            ->assertSee('My Classes');
    }

    public function test_the_new_screens_render_for_their_roles(): void
    {
        $admin = $this->makeUser('admin', 'Render Admin');
        $tutor = $this->makeTutor();
        $learner = $this->makeLearner();
        $this->makeClassWithLearner($tutor, $learner);
        $ceo = $this->makeUser('ceo', 'Render Ceo');

        $this->actingAs($admin)->get(route('admin.learning-goals.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.messages.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.messages.create'))->assertOk();
        $this->actingAs($ceo)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($tutor->user)->get(route('tutor.learning-goals.index'))->assertOk();
        $this->actingAs($tutor->user)->get(route('tutor.learning-goals.create'))->assertOk();
        $this->actingAs($learner->user)->get(route('messages.index'))->assertOk();
        $this->actingAs($learner->user)->get(route('help.user-guide'))->assertOk();
    }

    public function test_the_admin_dashboard_reports_classes_per_tutor_for_a_month(): void
    {
        $admin = $this->makeUser('admin', 'School Admin');
        $tutor = $this->makeTutor();
        $learner = $this->makeLearner();
        $class = $this->makeClassWithLearner($tutor, $learner);

        ClassSchedule::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Monthly session',
            'start_time' => now()->startOfMonth()->addDays(2),
            'end_time' => now()->startOfMonth()->addDays(2)->addHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Classes per tutor')
            ->assertSee($tutor->user->name)
            ->assertSeeText('0 completed of 1 scheduled');
    }
}
