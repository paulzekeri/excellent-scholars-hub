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
 * A weekly series can run for years, so the Schedules screen opens on the
 * current week (Monday to Sunday) instead of paginating through the whole
 * history. Sessions are grouped under a day heading, the arrows move between
 * weeks, and "All schedules" still offers the full paginated list.
 */
class ScheduleWeekViewTest extends TestCase
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
            'code' => 'WK-' . strtoupper(uniqid()),
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

    /**
     * One class with a session this week, one last week, one next week and one
     * three years out — the shape of a long weekly series.
     *
     * @return array{0: User, 1: Classes, 2: Tutor}
     */
    private function seedThreeYears(bool $farAway = true): array
    {
        $admin = $this->makeUser('admin', 'Week Admin');
        $tutor = $this->makeTutor('Week Tutor');
        $class = $this->makeClass($tutor, 'Week Class');
        $weekStart = $this->weekStart();

        $this->makeSchedule($class, $tutor, 'This Week Session', $weekStart->copy()->setTime(10, 0));

        if ($farAway) {
            $this->makeSchedule($class, $tutor, 'Last Week Session', $weekStart->copy()->subWeek()->setTime(10, 0));
            $this->makeSchedule($class, $tutor, 'Next Week Session', $weekStart->copy()->addWeek()->setTime(10, 0));
            $this->makeSchedule($class, $tutor, '2031 Session', Carbon::parse('2031-05-05 10:00:00'));
        }

        return [$admin, $class, $tutor];
    }

    public function test_the_schedules_page_opens_on_the_current_week_only(): void
    {
        [$admin] = $this->seedThreeYears();

        $response = $this->actingAs($admin)->get(route('admin.schedules.index'));

        $response->assertOk()
            ->assertSee('This Week Session')
            ->assertSee('Previous week')
            ->assertSee('Next week')
            ->assertSee($this->weekStart()->format('D, M d'))
            // A three-year series no longer floods the page with other weeks.
            ->assertDontSee('Last Week Session')
            ->assertDontSee('Next Week Session')
            ->assertDontSee('2031 Session')
            // A week fits on one screen, so there is no pagination to click through.
            ->assertDontSee('aria-label="Pagination Navigation"', false);
    }

    public function test_the_arrows_move_between_weeks(): void
    {
        [$admin] = $this->seedThreeYears();
        $nextWeek = $this->weekStart()->copy()->addWeek();

        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['week' => $nextWeek->toDateString()]))
            ->assertOk()
            ->assertSee('Next Week Session')
            ->assertDontSee('This Week Session')
            // Away from the current week, a shortcut back is offered and the
            // filter form stays on the week being browsed.
            ->assertSee('This week')
            ->assertSee('name="week"', false)
            ->assertSee('value="' . $nextWeek->toDateString() . '"', false);

        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['week' => $this->weekStart()->copy()->subWeek()->toDateString()]))
            ->assertOk()
            ->assertSee('Last Week Session')
            ->assertDontSee('This Week Session');
    }

    public function test_a_broken_week_value_falls_back_to_the_current_week(): void
    {
        [$admin] = $this->seedThreeYears();

        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['week' => 'not-a-date']))
            ->assertOk()
            ->assertSee('This Week Session')
            ->assertDontSee('Next Week Session');
    }

    public function test_sessions_are_grouped_under_a_day_heading(): void
    {
        [$admin, $class, $tutor] = $this->seedThreeYears(false);
        $monday = $this->weekStart()->copy()->setTime(10, 0);

        $this->makeSchedule($class, $tutor, 'Monday Second Session', $monday->copy()->setTime(14, 0));
        $this->makeSchedule($class, $tutor, 'Wednesday Session', $monday->copy()->addDays(2)->setTime(9, 0));

        $this->actingAs($admin)->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee($monday->format('l, M d, Y'))
            ->assertSee($monday->copy()->addDays(2)->format('l, M d, Y'))
            ->assertSeeText('2 sessions')
            ->assertSeeText('1 session');
    }

    public function test_the_filters_still_narrow_the_week_and_all_schedules_keeps_the_history(): void
    {
        [$admin, $classA] = $this->seedThreeYears();
        $tutorB = $this->makeTutor('Week Tutor B');
        $classB = $this->makeClass($tutorB, 'Week Class B');
        $this->makeSchedule($classB, $tutorB, 'Other Class Session', $this->weekStart()->copy()->setTime(11, 0));

        // The class filter applies inside the displayed week.
        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['class_id' => $classA->id]))
            ->assertOk()
            ->assertSee('This Week Session')
            ->assertDontSee('Other Class Session');

        // "All schedules" restores the full, paginated history for audits.
        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['view' => 'all']))
            ->assertOk()
            ->assertSee('This Week Session')
            ->assertSee('Last Week Session')
            ->assertSee('Next Week Session')
            ->assertSee('2031 Session')
            ->assertDontSee('Previous week')
            ->assertSee('This week');
    }

    public function test_a_week_with_no_sessions_says_so_and_points_at_the_full_history(): void
    {
        $admin = $this->makeUser('admin', 'Empty Week Admin');

        $this->actingAs($admin)->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSeeText('No sessions scheduled this week')
            ->assertSee('Browse all schedules');
    }

    public function test_editing_or_deleting_a_session_returns_to_its_own_week(): void
    {
        [$admin, $class, $tutor] = $this->seedThreeYears();
        $nextWeek = $this->weekStart()->copy()->addWeek()->setTime(10, 0);
        $schedule = $this->makeSchedule($class, $tutor, 'Editable Session', $nextWeek);

        $this->actingAs($admin)
            ->put(route('admin.schedules.update', $schedule), [
                'class_id' => $class->id,
                'tutor_id' => $tutor->id,
                'title' => 'Edited Session',
                'start_time' => $nextWeek->format('Y-m-d H:i:s'),
                'end_time' => $nextWeek->copy()->addHour()->format('Y-m-d H:i:s'),
                'status' => 'scheduled',
            ])
            ->assertRedirect(route('admin.schedules.index', ['week' => $nextWeek->toDateString()]))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->delete(route('admin.schedules.destroy', $schedule))
            ->assertRedirect(route('admin.schedules.index', ['week' => $nextWeek->toDateString()]))
            ->assertSessionHas('success');
    }
}
