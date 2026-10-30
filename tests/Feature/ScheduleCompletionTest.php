<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Classes;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Note 5 — an administrator/CEO can tick a class as completed, and the monthly
 * tutor report counts classes actually taken (not just scheduled).
 */
class ScheduleCompletionTest extends TestCase
{
    use DatabaseTransactions;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->roleId($roleSlug),
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

    private function makeClass(Tutor $tutor, string $name = 'JSS1 Maths'): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'CLS-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'subject' => 'Maths',
            'grade_level' => 'JSS1',
            'is_active' => true,
        ]);
    }

    private function makeSchedule(Tutor $tutor, Classes $class, array $overrides = []): ClassSchedule
    {
        return ClassSchedule::create(array_merge([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Session ' . uniqid(),
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
            'status' => 'scheduled',
        ], $overrides));
    }

    public function test_an_administrator_marks_a_past_session_completed(): void
    {
        $admin = $this->makeUser('admin', 'Completion Admin');
        [, $tutor] = $this->makeTutor('Completion Tutor');
        $class = $this->makeClass($tutor);
        $schedule = $this->makeSchedule($tutor, $class);

        $this->actingAs($admin)
            ->from(route('admin.schedules.index'))
            ->post(route('admin.schedules.complete', $schedule))
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('success');

        $schedule->refresh();

        $this->assertSame('completed', $schedule->status);
        $this->assertNotNull($schedule->completed_at);
        $this->assertSame($admin->id, $schedule->completed_by);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'schedule.completed',
            'model_id' => $schedule->id,
        ]);
    }

    public function test_the_ceo_can_mark_a_session_completed_too(): void
    {
        $ceo = $this->makeUser('ceo', 'Completion CEO');
        [, $tutor] = $this->makeTutor('CEO Completion Tutor');
        $class = $this->makeClass($tutor);
        $schedule = $this->makeSchedule($tutor, $class);

        $this->actingAs($ceo)
            ->from(route('admin.schedules.show', $schedule))
            ->post(route('admin.schedules.complete', $schedule))
            ->assertSessionHas('success');

        $this->assertSame('completed', $schedule->fresh()->status);
        $this->assertSame($ceo->id, $schedule->fresh()->completed_by);
    }

    public function test_a_future_session_cannot_be_completed(): void
    {
        $admin = $this->makeUser('admin', 'Future Completion Admin');
        [, $tutor] = $this->makeTutor('Future Completion Tutor');
        $class = $this->makeClass($tutor);
        $schedule = $this->makeSchedule($tutor, $class, [
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.schedules.index'))
            ->post(route('admin.schedules.complete', $schedule))
            ->assertSessionHas('error');

        $this->assertSame('scheduled', $schedule->fresh()->status);
        $this->assertNull($schedule->fresh()->completed_at);
    }

    public function test_a_tutor_marks_their_own_session_completed_with_audit(): void
    {
        [$tutorUser, $tutor] = $this->makeTutor('Tutor Self Completion');
        $class = $this->makeClass($tutor);
        $schedule = $this->makeSchedule($tutor, $class);

        $this->actingAs($tutorUser)
            ->from(route('tutor.schedules.index'))
            ->post(route('tutor.schedules.complete', $schedule))
            ->assertSessionHas('success');

        $schedule->refresh();

        $this->assertSame('completed', $schedule->status);
        $this->assertNotNull($schedule->completed_at);
        $this->assertSame($tutorUser->id, $schedule->completed_by);
    }

    public function test_reopening_a_completed_session_removes_it_from_the_count(): void
    {
        $admin = $this->makeUser('admin', 'Reopen Admin');
        [, $tutor] = $this->makeTutor('Reopen Tutor');
        $class = $this->makeClass($tutor);
        $schedule = $this->makeSchedule($tutor, $class, [
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.schedules.show', $schedule))
            ->post(route('admin.schedules.reopen', $schedule))
            ->assertSessionHas('success');

        $schedule->refresh();

        $this->assertSame('scheduled', $schedule->status);
        $this->assertNull($schedule->completed_at);
        $this->assertNull($schedule->completed_by);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'schedule.reopened',
            'model_id' => $schedule->id,
        ]);
    }

    public function test_tutors_and_parents_cannot_use_the_admin_completion_actions(): void
    {
        [$tutorUser, $tutor] = $this->makeTutor('Restricted Completion Tutor');
        $class = $this->makeClass($tutor);
        $schedule = $this->makeSchedule($tutor, $class);

        $parent = $this->makeUser('parent', 'Restricted Completion Parent');

        foreach ([$tutorUser, $parent] as $user) {
            $this->actingAs($user)
                ->post(route('admin.schedules.complete', $schedule))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('admin.schedules.reopen', $schedule))
                ->assertForbidden();
        }
    }

    public function test_the_monthly_report_counts_completed_classes_per_tutor(): void
    {
        $admin = $this->makeUser('admin', 'Report Admin');

        [, $tutorA] = $this->makeTutor('Report Tutor Alpha');
        [, $tutorB] = $this->makeTutor('Report Tutor Beta');

        $classA = $this->makeClass($tutorA, 'Report Class Alpha');
        $classB = $this->makeClass($tutorB, 'Report Class Beta');

        $monthStart = now()->startOfMonth();

        // Alpha: one completed + one still scheduled this month.
        $this->makeSchedule($tutorA, $classA, [
            'start_time' => $monthStart->copy()->addDays(2),
            'end_time' => $monthStart->copy()->addDays(2)->addHour(),
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $admin->id,
        ]);
        $this->makeSchedule($tutorA, $classA, [
            'start_time' => $monthStart->copy()->addDays(3),
            'end_time' => $monthStart->copy()->addDays(3)->addHour(),
            'status' => 'scheduled',
        ]);

        // Beta: one completed this month.
        $this->makeSchedule($tutorB, $classB, [
            'start_time' => $monthStart->copy()->addDays(4),
            'end_time' => $monthStart->copy()->addDays(4)->addHour(),
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $admin->id,
        ]);

        // Alpha: a completion in the previous month must not leak into this one.
        $this->makeSchedule($tutorA, $classA, [
            'start_time' => $monthStart->copy()->subMonth()->addDays(2),
            'end_time' => $monthStart->copy()->subMonth()->addDays(2)->addHour(),
            'status' => 'completed',
            'completed_at' => now()->subMonth(),
            'completed_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['month' => $monthStart->format('Y-m')]));

        $response->assertOk()->assertSeeText('1 completed of 2 scheduled');

        $monthly = $response->viewData('tutorMonthly');

        $alpha = $monthly->firstWhere('id', $tutorA->id);
        $beta = $monthly->firstWhere('id', $tutorB->id);

        $this->assertSame(2, (int) $alpha->month_sessions);
        $this->assertSame(1, (int) $alpha->month_completed);
        $this->assertSame(1, (int) $beta->month_sessions);
        $this->assertSame(1, (int) $beta->month_completed);

        $previous = $this->actingAs($admin)->get(route('admin.dashboard', [
            'month' => $monthStart->copy()->subMonth()->format('Y-m'),
        ]));

        $previousAlpha = $previous->viewData('tutorMonthly')->firstWhere('id', $tutorA->id);

        $this->assertSame(1, (int) $previousAlpha->month_sessions);
        $this->assertSame(1, (int) $previousAlpha->month_completed);
    }
}
