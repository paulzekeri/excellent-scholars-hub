<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\LearningGoal;
use App\Models\ParentModel;
use App\Models\PaymentAccount;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Second round of client feedback (1 November 2026):
 * role filter on the users page, optional class code, weekly schedules with an
 * automatic video room, long-text learning goals with a free wording assistant,
 * and sharing goals with individual parents, learners and tutors.
 */
class RoundTwoEnhancementsTest extends TestCase
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

    private function makeTutor(string $name): Tutor
    {
        return Tutor::create(['user_id' => $this->makeUser('tutor', $name)->id]);
    }

    private function makeClass(Tutor $tutor, string $name): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'R2-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);
    }

    private function makeLearner(string $name, ?ParentModel $parent = null): Learner
    {
        return Learner::create([
            'user_id' => $this->makeUser('learner', $name)->id,
            'parent_id' => $parent ? $parent->id : null,
            'grade_level' => 'JSS 2',
        ]);
    }

    private function parentWithChild(string $parentName, string $childName): array
    {
        $parent = ParentModel::create(['user_id' => $this->makeUser('parent', $parentName)->id]);
        $learner = $this->makeLearner($childName, $parent);

        return [$parent, $learner];
    }

    private function account(): PaymentAccount
    {
        return PaymentAccount::firstOrCreate(
            ['account_number' => '0099887766'],
            [
                'bank_name' => 'GTBank',
                'account_name' => 'Excellent Scholars Academy',
                'is_active' => true,
            ]
        );
    }

    public function test_the_users_page_filters_by_role(): void
    {
        $admin = $this->makeUser('admin', 'Filter Admin');
        $this->makeTutor('Role Filter Tutor');
        $this->makeLearner('Role Filter Learner');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'tutor']))
            ->assertOk()
            ->assertSee('name="role"', false)
            ->assertSee('Role Filter Tutor')
            ->assertDontSee('Role Filter Learner');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Role Filter Learner']))
            ->assertOk()
            ->assertSee('Role Filter Learner')
            ->assertDontSee('Role Filter Tutor');
    }

    public function test_a_class_code_is_generated_when_left_blank(): void
    {
        $admin = $this->makeUser('admin', 'Code Admin');
        $tutor = $this->makeTutor('Code Tutor');

        $this->actingAs($admin)->get(route('admin.classes.create'))
            ->assertOk()
            ->assertSee('Leave blank to auto-generate');

        $this->actingAs($admin)->post(route('admin.classes.store'), [
            'name' => 'JSS 3 Further Maths',
            'code' => '',
            'tutor_id' => $tutor->id,
        ])->assertRedirect(route('admin.classes.index'))->assertSessionHas('success');

        $class = Classes::where('name', 'JSS 3 Further Maths')->firstOrFail();

        $this->assertNotEmpty($class->code);
        $this->assertStringStartsWith('JSS3FU', $class->code);
    }

    public function test_a_weekly_schedule_repeats_and_generates_a_video_room(): void
    {
        $admin = $this->makeUser('admin', 'Repeat Admin');
        $tutor = $this->makeTutor('Repeat Tutor');
        $class = $this->makeClass($tutor, 'Weekly Maths Class');

        $learner = $this->makeLearner('Repeat Learner');
        $class->learners()->attach($learner->id);

        $start = now()->next('Monday')->setTime(16, 0);

        $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Weekly Algebra',
            'start_time' => $start->format('Y-m-d\TH:i'),
            'end_time' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'meeting_platform' => 'jitsi',
            'meeting_link' => '',
            'repeat' => 'weekly',
            'repeat_until' => $start->copy()->addWeeks(3)->toDateString(),
        ])->assertRedirect(route('admin.schedules.index'))->assertSessionHas('success');

        $sessions = ClassSchedule::where('class_id', $class->id)->orderBy('start_time')->get();

        $this->assertCount(4, $sessions);
        $this->assertSame(1, $sessions->pluck('recurrence_group')->unique()->count());
        $this->assertTrue($sessions->every(function (ClassSchedule $session) {
            return $session->start_time->format('l') === 'Monday' && $session->isRecurring();
        }));

        $this->assertStringStartsWith('https://meet.jit.si/esh-', (string) $sessions->first()->meeting_link);
        $this->assertSame($sessions->first()->meeting_link, $sessions->last()->meeting_link);

        // The class's learners see every Monday automatically.
        $this->actingAs($learner->user)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Weekly Algebra');
    }

    public function test_the_payment_form_lists_every_child_for_one_submission(): void
    {
        $parent = ParentModel::create(['user_id' => $this->makeUser('parent', 'Family Parent')->id]);

        $first = $this->makeLearner('Family Child One', $parent);
        $second = $this->makeLearner('Family Child Two', $parent);

        $tutor = $this->makeTutor('Family Tutor');
        $maths = $this->makeClass($tutor, 'Family Maths');
        $english = $this->makeClass($tutor, 'Family English');
        $first->classes()->attach($maths->id);
        $second->classes()->attach($english->id);

        $this->actingAs($parent->user)
            ->get(route('parent.payments.create'))
            ->assertOk()
            ->assertSee('Payments on this transfer')
            ->assertSee('Family Child One')
            ->assertSee('Family Child Two')
            ->assertSee('name="lines[0][learner_id]"', false)
            ->assertSee('Family Maths')
            ->assertSee('Family English');
    }

    public function test_learning_goals_take_long_text_and_are_shared_with_chosen_people(): void
    {
        $tutorUser = $this->makeUser('tutor', 'Long Goal Tutor');
        $tutor = Tutor::create(['user_id' => $tutorUser->id]);
        $class = $this->makeClass($tutor, 'Long Goal Class');

        [$parent, $learner] = $this->parentWithChild('Long Goal Parent', 'Long Goal Learner');
        $class->learners()->attach($learner->id);

        $this->actingAs($tutorUser)->get(route('tutor.learning-goals.create'))
            ->assertOk()
            ->assertSee('Improve wording (free AI)')
            ->assertSee('<textarea name="title"', false)
            ->assertDontSee('What success looks like')
            ->assertDontSee('name="learner_id"', false);

        $longGoal = trim(str_repeat('Read one chapter aloud each week and explain the main idea in your own words. ', 6));

        $this->actingAs($tutorUser)->post(route('tutor.learning-goals.store'), [
            'title' => $longGoal,
            'class_id' => $class->id,
            'term' => 'First Term',
        ])->assertRedirect(route('tutor.learning-goals.index'))->assertSessionHas('success');

        $goal = LearningGoal::where('tutor_id', $tutor->id)->latest('id')->firstOrFail();

        $this->assertGreaterThan(255, strlen($goal->title));
        $this->assertSame($longGoal, $goal->goalText());

        $admin = $this->makeUser('admin', 'Goal Share Admin');
        $otherTutorUser = $this->makeUser('tutor', 'Chosen Tutor');
        Tutor::create(['user_id' => $otherTutorUser->id]);

        $this->actingAs($admin)->post(route('admin.learning-goals.share', $goal), [
            'share_form' => 1,
            'parent_ids' => [$parent->user_id],
            'learner_ids' => [$learner->user_id],
            'tutor_ids' => [$otherTutorUser->id],
        ])->assertRedirect()->assertSessionHas('success');

        $goal->refresh();

        $this->assertTrue($goal->shared_with_parent);
        $this->assertTrue($goal->shared_with_learner);
        $this->assertTrue($goal->shared_with_tutor);
        $this->assertCount(3, $goal->recipients);

        // The chosen parent sees it on the child's goals tab.
        $this->actingAs($parent->user)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'goals']))
            ->assertOk()
            ->assertSee('Read one chapter aloud');

        // The chosen learner sees it on their own page.
        $this->actingAs($learner->user)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertSee('Read one chapter aloud');

        // The chosen tutor sees it under "Shared with you by the school".
        $this->actingAs($otherTutorUser)
            ->get(route('tutor.learning-goals.index'))
            ->assertOk()
            ->assertSee('Read one chapter aloud');

        // A learner who was not chosen (and is not in the class) does not.
        $stranger = $this->makeLearner('Not Chosen Learner');

        $this->actingAs($stranger->user)
            ->get(route('learner.learning-goals.index'))
            ->assertOk()
            ->assertDontSee('Read one chapter aloud');
    }

    public function test_the_free_wording_assistant_returns_corrected_text(): void
    {
        Http::fake([
            'api.languagetool.org/*' => Http::response([
                'matches' => [[
                    'message' => 'Possible spelling mistake found.',
                    'offset' => 0,
                    'length' => 4,
                    'replacements' => [['value' => 'Read']],
                    'rule' => ['issueType' => 'misspelling'],
                ]],
            ], 200),
        ]);

        $tutor = $this->makeTutor('Wording Tutor');

        $this->actingAs($tutor->user)
            ->postJson(route('tutor.learning-goals.polish'), ['text' => 'raed two chapters each week'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('corrected', 'Read two chapters each week')
            ->assertJsonPath('issues', 1);
    }

    public function test_the_wording_assistant_fails_gracefully_without_internet(): void
    {
        Http::fake([
            'api.languagetool.org/*' => Http::response('Service unavailable', 503),
        ]);

        $tutor = $this->makeTutor('Offline Wording Tutor');

        $this->actingAs($tutor->user)
            ->postJson(route('tutor.learning-goals.polish'), ['text' => 'Finish the workbook'])
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('corrected', 'Finish the workbook');
    }
}
