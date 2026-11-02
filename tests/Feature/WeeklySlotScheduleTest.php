<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A class can be scheduled on several days at different times - 10:00 Monday,
 * 14:00 Wednesday and 18:00 Friday - and each of those days repeats weekly at
 * its own time.
 */
class WeeklySlotScheduleTest extends TestCase
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
            'code' => 'WS-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);
    }

    public function test_an_admin_can_schedule_a_class_on_three_days_at_different_times(): void
    {
        $admin = $this->makeUser('admin', 'Slot Admin');
        $tutor = $this->makeTutor('Slot Tutor');
        $class = $this->makeClass($tutor, 'Slot Maths Class');

        // Monday 2 November 2026, repeated for two weeks.
        $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Maths on three days',
            'meeting_platform' => 'jitsi',
            'meeting_link' => '',
            'repeat' => 'weekly',
            'repeats_from' => '2026-11-02',
            'repeat_until' => '2026-11-15',
            'slots' => [
                ['day' => 1, 'start' => '10:00', 'end' => '11:00'],   // Monday
                ['day' => 3, 'start' => '14:00', 'end' => '15:30'],   // Wednesday
                ['day' => 5, 'start' => '18:00', 'end' => '19:00'],   // Friday
            ],
        ])->assertRedirect(route('admin.schedules.index'))->assertSessionHas('success');

        $sessions = ClassSchedule::where('class_id', $class->id)->orderBy('start_time')->get();

        // Two weeks x three days.
        $this->assertCount(6, $sessions);
        $this->assertSame(1, $sessions->pluck('recurrence_group')->unique()->count());

        $this->assertSame([
            'Mon 10:00', 'Wed 14:00', 'Fri 18:00',
            'Mon 10:00', 'Wed 14:00', 'Fri 18:00',
        ], $sessions->map(function (ClassSchedule $session) {
            return $session->start_time->format('D H:i');
        })->all());

        // Each day keeps its own length: 60, 90 and 60 minutes.
        $this->assertSame([
            '11:00', '15:30', '19:00',
            '11:00', '15:30', '19:00',
        ], $sessions->map(function (ClassSchedule $session) {
            return $session->end_time->format('H:i');
        })->all());

        // One series: same room link and same end date on every row.
        $this->assertStringStartsWith('https://meet.jit.si/esh-', (string) $sessions->first()->meeting_link);
        $this->assertSame($sessions->first()->meeting_link, $sessions->last()->meeting_link);
        $this->assertTrue($sessions->every(function (ClassSchedule $session) {
            return $session->isRecurring()
                && $session->recurrence_until->toDateString() === '2026-11-15';
        }));

        $this->assertSame(
            'Every Mon 10:00 am, Wed 2:00 pm, Fri 6:00 pm',
            $sessions->first()->seriesLabel()
        );
    }

    public function test_a_tutor_can_schedule_the_same_class_on_two_days(): void
    {
        $tutor = $this->makeTutor('Two Day Tutor');
        $class = $this->makeClass($tutor, 'Two Day Class');

        $this->actingAs($tutor->user)->post(route('tutor.schedules.store'), [
            'class_id' => $class->id,
            'title' => 'Two day class',
            'meeting_platform' => 'jitsi',
            'meeting_link' => '',
            'repeat' => 'weekly',
            'repeats_from' => '2026-11-02',
            'repeat_until' => '2026-11-08',
            'slots' => [
                ['day' => 1, 'start' => '09:00', 'end' => '10:00'],
                ['day' => 4, 'start' => '17:00', 'end' => '18:30'],
            ],
        ])->assertRedirect(route('tutor.schedules.index'))->assertSessionHas('success');

        $sessions = ClassSchedule::where('class_id', $class->id)->orderBy('start_time')->get();

        $this->assertCount(2, $sessions);
        $this->assertSame(['Mon 09:00', 'Thu 17:00'], $sessions->map(function (ClassSchedule $session) {
            return $session->start_time->format('D H:i');
        })->all());
        $this->assertTrue($sessions->every(function (ClassSchedule $session) use ($tutor) {
            return $session->tutor_id === $tutor->id;
        }));
    }

    public function test_a_slot_must_end_after_it_starts(): void
    {
        $admin = $this->makeUser('admin', 'Backwards Admin');
        $tutor = $this->makeTutor('Backwards Tutor');
        $class = $this->makeClass($tutor, 'Backwards Class');

        $this->actingAs($admin)->from(route('admin.schedules.create'))->post(route('admin.schedules.store'), [
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Backwards lesson',
            'repeat' => 'weekly',
            'repeats_from' => '2026-11-02',
            'repeat_until' => '2026-11-15',
            'slots' => [
                ['day' => 3, 'start' => '14:00', 'end' => '13:00'],
            ],
        ])->assertSessionHasErrors('slots');

        $this->assertSame(0, ClassSchedule::where('class_id', $class->id)->count());
    }

    public function test_a_weekly_repeat_needs_at_least_one_day_and_time(): void
    {
        $admin = $this->makeUser('admin', 'Empty Admin');
        $tutor = $this->makeTutor('Empty Tutor');
        $class = $this->makeClass($tutor, 'Empty Class');

        $this->actingAs($admin)->from(route('admin.schedules.create'))->post(route('admin.schedules.store'), [
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'No days',
            'repeat' => 'weekly',
            'repeats_from' => '2026-11-02',
            'repeat_until' => '2026-11-15',
            'slots' => [
                ['day' => '', 'start' => '', 'end' => ''],
            ],
        ])->assertSessionHasErrors('slots.0.day');

        $this->assertSame(0, ClassSchedule::where('class_id', $class->id)->count());
    }

    public function test_the_repeat_end_date_cannot_precede_the_first_lesson(): void
    {
        $admin = $this->makeUser('admin', 'Early Admin');
        $tutor = $this->makeTutor('Early Tutor');
        $class = $this->makeClass($tutor, 'Early Class');

        $this->actingAs($admin)->from(route('admin.schedules.create'))->post(route('admin.schedules.store'), [
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Too early to end',
            'repeat' => 'weekly',
            'repeats_from' => '2026-11-02',
            'repeat_until' => '2026-11-04',   // before Friday 6 November
            'slots' => [
                ['day' => 5, 'start' => '18:00', 'end' => '19:00'],
            ],
        ])->assertSessionHasErrors('repeat_until');

        $this->assertSame(0, ClassSchedule::where('class_id', $class->id)->count());
    }

    public function test_the_create_form_offers_the_day_and_time_builder(): void
    {
        $admin = $this->makeUser('admin', 'Form Admin');

        $this->actingAs($admin)->get(route('admin.schedules.create'))
            ->assertOk()
            ->assertSee('Every week on the days', false)
            ->assertSee('Repeats from')
            ->assertSee('Add another day')
            ->assertSee('name="slots[0][day]"', false)
            ->assertSee('name="slots[0][start]"', false)
            ->assertSee('name="slots[0][end]"', false)
            ->assertSee('>Monday<', false)
            ->assertSee('>Friday<', false);
    }
}
