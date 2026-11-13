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
 * The Schedules page takes a class, a tutor and a single day of start times, so
 * the school can drill into "everything on Wednesday 4 November" in one step.
 */
class ScheduleFiltersTest extends TestCase
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
            'code' => 'SF-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);
    }

    private function makeSchedule(Classes $class, Tutor $tutor, string $title, string $start, string $status = 'scheduled'): ClassSchedule
    {
        return ClassSchedule::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => $title,
            'start_time' => $start,
            'end_time' => Carbon::parse($start)->addHour(),
            'status' => $status,
        ]);
    }

    /** Two classes, two tutors and two days of sessions to filter through. */
    private function seedTwoDays(): array
    {
        $admin = $this->makeUser('admin', 'Filter Admin');
        $tutorA = $this->makeTutor('Filter Tutor A');
        $tutorB = $this->makeTutor('Filter Tutor B');
        $classA = $this->makeClass($tutorA, 'Filter Class A');
        $classB = $this->makeClass($tutorB, 'Filter Class B');

        // Far-future dates so real sessions in the database cannot collide.
        $this->makeSchedule($classA, $tutorA, 'Filtered Session One', '2031-05-05 10:00:00');
        $this->makeSchedule($classB, $tutorB, 'Filtered Session Two', '2031-05-06 14:00:00');

        return [$admin, $tutorA, $tutorB, $classA, $classB];
    }

    public function test_the_schedules_page_filters_by_a_single_day_of_start_time(): void
    {
        [$admin] = $this->seedTwoDays();

        $this->actingAs($admin)->get(route('admin.schedules.index', ['date' => '2031-05-05']))
            ->assertOk()
            ->assertSee('Start Time (day)')
            ->assertSee('name="date"', false)
            ->assertSee('Filtered Session One')
            ->assertDontSee('Filtered Session Two');

        // The same day picked from the date box only matches that day.
        $this->actingAs($admin)->get(route('admin.schedules.index', ['date' => '2031-05-06']))
            ->assertOk()
            ->assertSee('Filtered Session Two')
            ->assertDontSee('Filtered Session One');
    }

    public function test_the_schedules_page_filters_by_class_and_by_tutor(): void
    {
        [$admin, $tutorA, $tutorB, $classA, $classB] = $this->seedTwoDays();

        // "view=all" asks for the full history; the default page is one week.
        $this->actingAs($admin)->get(route('admin.schedules.index', ['class_id' => $classA->id, 'view' => 'all']))
            ->assertOk()
            ->assertSee('Filtered Session One')
            ->assertDontSee('Filtered Session Two');

        $this->actingAs($admin)->get(route('admin.schedules.index', ['tutor_id' => $tutorB->id, 'view' => 'all']))
            ->assertOk()
            ->assertSee('Filtered Session Two')
            ->assertDontSee('Filtered Session One');
    }

    public function test_the_filters_combine_and_report_how_many_sessions_matched(): void
    {
        [$admin, $tutorA, $tutorB, $classA] = $this->seedTwoDays();

        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['class_id' => $classA->id, 'date' => '2031-05-05']))
            ->assertOk()
            ->assertSee('Filtered Session One')
            ->assertDontSee('Filtered Session Two')
            ->assertSeeText('Showing 1 of');

        // A filter that matches nothing shows the empty state, not an error.
        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['class_id' => $classA->id, 'status' => 'completed']))
            ->assertOk()
            ->assertSeeText('No sessions match these filters');
    }

    public function test_a_hand_typed_or_broken_date_is_ignored_instead_of_erroring(): void
    {
        [$admin] = $this->seedTwoDays();

        $this->actingAs($admin)->get(route('admin.schedules.index', ['date' => 'not-a-date']))
            ->assertOk()
            ->assertDontSee('No sessions match these filters');
    }

    public function test_the_filter_form_keeps_the_filters_on_the_page_and_offers_a_reset(): void
    {
        [$admin, $tutorA, , $classA] = $this->seedTwoDays();

        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['class_id' => $classA->id, 'date' => '2031-05-05']))
            ->assertOk()
            ->assertSee('name="class_id"', false)
            ->assertSee('name="tutor_id"', false)
            ->assertSee('name="status"', false)
            ->assertSee('value="' . $classA->id . '" selected', false)
            ->assertSee('value="2031-05-05"', false)
            ->assertSee(route('admin.schedules.index'), false);
    }
}
