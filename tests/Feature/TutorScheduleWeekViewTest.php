<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tutors see their own schedule one week at a time, for the same reason as the
 * admin list: a weekly class that runs for years would otherwise fill the page
 * with hundreds of rows and dozens of pagination links.
 */
class TutorScheduleWeekViewTest extends TestCase
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
     * A tutor login plus the Tutor row the schedules page reads.
     *
     * @return array{0: User, 1: Tutor}
     */
    private function makeTutor(string $name): array
    {
        $user = $this->makeUser('tutor', $name);

        return [$user, Tutor::create(['user_id' => $user->id])];
    }

    private function makeClass(Tutor $tutor, string $name): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'TW-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);
    }

    private function makeSchedule(Classes $class, Tutor $tutor, string $title, Carbon $start): ClassSchedule
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

    /** The Monday that starts the week being displayed. */
    private function weekStart(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public function test_the_tutor_schedule_opens_on_the_current_week(): void
    {
        [$user, $tutor] = $this->makeTutor('Week Tutor');
        $class = $this->makeClass($tutor, 'Week Tutor Class');
        $weekStart = $this->weekStart();

        $this->makeSchedule($class, $tutor, 'Tutor This Week', $weekStart->copy()->setTime(10, 0));
        $this->makeSchedule($class, $tutor, 'Tutor Next Week', $weekStart->copy()->addWeek()->setTime(10, 0));

        $this->actingAs($user)->get(route('tutor.schedules.index'))
            ->assertOk()
            ->assertSee('Tutor This Week')
            ->assertSee('Previous week')
            ->assertSee('Next week')
            ->assertSee($weekStart->format('D, M d'))
            ->assertSee($weekStart->format('l, M d, Y'))
            ->assertDontSee('Tutor Next Week')
            ->assertDontSee('aria-label="Pagination Navigation"', false);
    }

    public function test_the_tutor_arrows_move_between_weeks(): void
    {
        [$user, $tutor] = $this->makeTutor('Week Tutor');
        $class = $this->makeClass($tutor, 'Week Tutor Class');
        $nextWeek = $this->weekStart()->copy()->addWeek();

        $this->makeSchedule($class, $tutor, 'Tutor This Week', $this->weekStart()->copy()->setTime(10, 0));
        $this->makeSchedule($class, $tutor, 'Tutor Next Week', $nextWeek->copy()->setTime(10, 0));

        $this->actingAs($user)
            ->get(route('tutor.schedules.index', ['week' => $nextWeek->toDateString()]))
            ->assertOk()
            ->assertSee('Tutor Next Week')
            ->assertDontSee('Tutor This Week')
            ->assertSee('This week');
    }

    public function test_a_tutor_only_sees_their_own_sessions(): void
    {
        [$user, $tutor] = $this->makeTutor('Own Tutor');
        $class = $this->makeClass($tutor, 'Own Class');

        [, $otherTutor] = $this->makeTutor('Other Tutor');
        $otherClass = $this->makeClass($otherTutor, 'Other Class');

        $this->makeSchedule($class, $tutor, 'Own Session', $this->weekStart()->copy()->setTime(10, 0));
        $this->makeSchedule($otherClass, $otherTutor, 'Other Tutor Session', $this->weekStart()->copy()->setTime(11, 0));

        $this->actingAs($user)->get(route('tutor.schedules.index'))
            ->assertOk()
            ->assertSee('Own Session')
            ->assertDontSee('Other Tutor Session');

        $this->actingAs($user)->get(route('tutor.schedules.index', ['view' => 'all']))
            ->assertOk()
            ->assertSee('Own Session')
            ->assertDontSee('Other Tutor Session');
    }

    public function test_all_schedules_keeps_the_full_history(): void
    {
        [$user, $tutor] = $this->makeTutor('Week Tutor');
        $class = $this->makeClass($tutor, 'Week Tutor Class');

        $this->makeSchedule($class, $tutor, 'Tutor This Week', $this->weekStart()->copy()->setTime(10, 0));
        $this->makeSchedule($class, $tutor, 'Tutor Next Week', $this->weekStart()->copy()->addWeek()->setTime(10, 0));
        $this->makeSchedule($class, $tutor, 'Tutor 2031 Session', Carbon::parse('2031-05-05 10:00:00'));

        $this->actingAs($user)->get(route('tutor.schedules.index', ['view' => 'all']))
            ->assertOk()
            ->assertSee('Tutor This Week')
            ->assertSee('Tutor Next Week')
            ->assertSee('Tutor 2031 Session')
            ->assertDontSee('Previous week')
            ->assertSee('This week');
    }

    public function test_a_week_with_no_sessions_says_so(): void
    {
        [$user] = $this->makeTutor('Empty Tutor');

        $this->actingAs($user)->get(route('tutor.schedules.index'))
            ->assertOk()
            ->assertSeeText('No sessions scheduled this week')
            ->assertSee('Browse all schedules');
    }
}
