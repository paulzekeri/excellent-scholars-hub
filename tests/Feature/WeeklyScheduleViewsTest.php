<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Learner and parent schedule panels open on the current week.
 *
 * "All sessions" keeps the full list one click away, and the week arrows move
 * through the timetable a week at a time.
 */
class WeeklyScheduleViewsTest extends TestCase
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
        return Tutor::create([
            'user_id' => $this->makeUser('tutor', $name)->id,
            'is_active' => true,
        ]);
    }

    private function makeClass(Tutor $tutor, string $name, string $subject): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'WK-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'subject' => $subject,
            'is_active' => true,
        ]);
    }

    private function makeLearner(string $name, ?ParentModel $parent = null): Learner
    {
        return Learner::create([
            'user_id' => $this->makeUser('learner', $name)->id,
            'parent_id' => $parent ? $parent->id : null,
            'grade_level' => 'SS2',
        ]);
    }

    private function makeSchedule(Tutor $tutor, Classes $class, string $title, Carbon $start): ClassSchedule
    {
        return ClassSchedule::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => $title,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'status' => 'scheduled',
        ]);
    }

    /** Monday 00:00 of the current week. */
    private function weekStart(): Carbon
    {
        return now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public function test_the_learner_dashboard_lists_only_this_weeks_sessions(): void
    {
        $tutor = $this->makeTutor('Weekly Learner Tutor');
        $learner = $this->makeLearner('Weekly Learner');
        $class = $this->makeClass($tutor, 'Science A', 'Mathematics');
        $class->learners()->attach($learner->id);

        $week = $this->weekStart();

        $this->makeSchedule($tutor, $class, 'Monday lesson', $week->copy()->addDay()->setTime(10, 0));
        $this->makeSchedule($tutor, $class, 'Thursday lesson', $week->copy()->addDays(3)->setTime(14, 0));
        $this->makeSchedule($tutor, $class, 'Next week lesson', $week->copy()->addDays(8)->setTime(10, 0));

        $this->actingAs($learner->user)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee("This Week's Sessions")
            ->assertSee('Monday lesson')
            ->assertSee('Thursday lesson')
            ->assertDontSee('Next week lesson')
            // "View All" is the way to the full list.
            ->assertSee('view=all', false);
    }

    public function test_the_learner_classes_page_defaults_to_this_week_and_has_an_all_view(): void
    {
        $tutor = $this->makeTutor('Weekly Classes Tutor');
        $learner = $this->makeLearner('Weekly Classes Learner');
        $class = $this->makeClass($tutor, 'Science B', 'Mathematics');
        $class->learners()->attach($learner->id);

        $week = $this->weekStart();
        $nextWeekDate = $week->copy()->addDays(8);

        $this->makeSchedule($tutor, $class, 'This week lesson', $week->copy()->addDay()->setTime(10, 0));
        $this->makeSchedule($tutor, $class, 'Next week lesson', $nextWeekDate->copy()->setTime(10, 0));

        // Default: current week only.
        $this->actingAs($learner->user)
            ->get(route('learner.classes'))
            ->assertOk()
            ->assertSee('This week lesson')
            ->assertDontSee('Next week lesson')
            ->assertSee('All sessions');

        // "All sessions" shows the full timetable.
        $this->actingAs($learner->user)
            ->get(route('learner.classes', ['view' => 'all']))
            ->assertOk()
            ->assertSee('This week lesson')
            ->assertSee('Next week lesson');

        // Any week can be opened directly.
        $this->actingAs($learner->user)
            ->get(route('learner.classes', ['week' => $nextWeekDate->toDateString()]))
            ->assertOk()
            ->assertSee('Next week lesson')
            ->assertDontSee('This week lesson');
    }

    public function test_the_parent_child_card_previews_only_this_weeks_sessions(): void
    {
        $parentUser = $this->makeUser('parent', 'Weekly Parent');
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $learner = $this->makeLearner('Weekly Parent Child', $parent);

        $tutor = $this->makeTutor('Weekly Parent Tutor');
        $mathsClass = $this->makeClass($tutor, 'Science A', 'Mathematics');
        $physicsClass = $this->makeClass($tutor, 'Science B', 'Physics');
        $mathsClass->learners()->attach($learner->id);
        $physicsClass->learners()->attach($learner->id);

        $week = $this->weekStart();
        $this->makeSchedule($tutor, $mathsClass, 'Maths this week', $week->copy()->addDay()->setTime(10, 0));
        $this->makeSchedule($tutor, $physicsClass, 'Physics next week', $week->copy()->addDays(8)->setTime(10, 0));

        $response = $this->actingAs($parentUser)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('This week')
            ->assertSee('Mathematics')
            ->assertDontSee('Physics');

        $stats = $response->viewData('childStats')->get($learner->id);

        $this->assertSame(1, $stats['weekSessions']);
        $this->assertSame(2, $stats['enrolledClasses']);
        $this->assertSame(1, $response->viewData('childWeekSessions')->get($learner->id)->count());
    }

    public function test_the_parent_schedule_page_opens_on_the_current_week_with_an_all_sessions_view(): void
    {
        $parentUser = $this->makeUser('parent', 'Schedule Parent');
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $learner = $this->makeLearner('Schedule Parent Child', $parent);

        $tutor = $this->makeTutor('Schedule Parent Tutor');
        $class = $this->makeClass($tutor, 'Science C', 'Mathematics');
        $class->learners()->attach($learner->id);

        $week = $this->weekStart();
        $nextWeekDate = $week->copy()->addDays(8);

        $this->makeSchedule($tutor, $class, 'Current week session', $week->copy()->addDays(2)->setTime(10, 0));
        $this->makeSchedule($tutor, $class, 'Following week session', $nextWeekDate->copy()->setTime(10, 0));

        $response = $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule']))
            ->assertOk()
            ->assertSee('Current week session')
            ->assertDontSee('Following week session')
            ->assertSee('All sessions')
            ->assertSee("This Week's Sessions");

        $this->assertSame('week', $response->viewData('scheduleScope'));
        $this->assertTrue($response->viewData('scheduleWeekStart')->isCurrentWeek());

        // "All sessions" includes the following week.
        $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule', 'view' => 'all']))
            ->assertOk()
            ->assertSee('Current week session')
            ->assertSee('Following week session');

        // A specific week can be opened directly.
        $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule', 'week' => $nextWeekDate->toDateString()]))
            ->assertOk()
            ->assertSee('Following week session')
            ->assertDontSee('Current week session');
    }
}
