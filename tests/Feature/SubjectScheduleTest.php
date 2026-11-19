<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\ClassSubject;
use App\Models\Learner;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A class with several subjects is scheduled per subject:
 *
 *   Test Class WAEC / Mathematics / John → Monday 9:00 am
 *   Test Class WAEC / Physics     / Ada  → Monday 11:00 am
 *
 * Each session is linked to its class_subject row, which supplies both the
 * subject and the teacher. The same class can therefore run different subjects
 * on the same day at different times, or on different days, and only the
 * subject's own teacher is attached to each session.
 */
class SubjectScheduleTest extends TestCase
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

    private function makeClass(Tutor $lead, string $name = 'Test Class WAEC'): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'SC-' . strtoupper(uniqid()),
            'tutor_id' => $lead->id,
            'is_active' => true,
        ]);
    }

    private function makeSubject(Classes $class, ?Tutor $tutor, string $name): ClassSubject
    {
        $subject = Subject::create([
            'name' => $name . ' ' . strtoupper(uniqid()),
            'is_active' => true,
        ]);

        return ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'tutor_id' => $tutor ? $tutor->id : null,
        ]);
    }

    private function makeLearner(string $name): Learner
    {
        return Learner::create([
            'user_id' => $this->makeUser('learner', $name)->id,
        ]);
    }

    public function test_each_subject_can_be_scheduled_at_its_own_time_on_the_same_day(): void
    {
        $admin = $this->makeUser('admin', 'Subject Schedule Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada Physics');
        $class = $this->makeClass($john);

        $maths = $this->makeSubject($class, $john, 'Mathematics');
        $physics = $this->makeSubject($class, $ada, 'Physics');

        $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'class_subject_id' => $maths->id,
            'title' => 'Mathematics lesson',
            'start_time' => '2026-11-23 09:00:00',
            'end_time' => '2026-11-23 10:00:00',
            'meeting_platform' => 'jitsi',
            'meeting_link' => '',
        ])->assertRedirect(route('admin.schedules.index'))->assertSessionHas('success');

        // A forged tutor_id must not matter: the subject's teacher wins.
        $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'class_subject_id' => $physics->id,
            'tutor_id' => $john->id,
            'title' => 'Physics lesson',
            'start_time' => '2026-11-23 11:00:00',
            'end_time' => '2026-11-23 12:00:00',
        ])->assertRedirect(route('admin.schedules.index'));

        $sessions = ClassSchedule::where('class_id', $class->id)->orderBy('start_time')->get();

        $this->assertCount(2, $sessions);
        $this->assertSame([$maths->id, $physics->id], $sessions->pluck('class_subject_id')->all());
        $this->assertSame([$john->id, $ada->id], $sessions->pluck('tutor_id')->all());
        $this->assertSame([$class->id, $class->id], $sessions->pluck('class_id')->all());
        $this->assertSame(['09:00', '11:00'], $sessions->map(function (ClassSchedule $session) {
            return $session->start_time->format('H:i');
        })->all());
        $this->assertSame([$maths->subject->name, $physics->subject->name], $sessions->map(function (ClassSchedule $session) {
            return $session->subjectName();
        })->all());

        $this->actingAs($admin)
            ->get(route('admin.schedules.index', ['view' => 'all', 'class_subject_id' => $maths->id]))
            ->assertOk()
            ->assertSee('Mathematics lesson')
            ->assertDontSee('Physics lesson');
    }

    public function test_a_weekly_series_links_only_the_subject_teacher(): void
    {
        $admin = $this->makeUser('admin', 'Weekly Subject Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada Physics');
        $class = $this->makeClass($john);
        $physics = $this->makeSubject($class, $ada, 'Physics');

        $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'class_subject_id' => $physics->id,
            'tutor_id' => $john->id,          // ignored: Physics belongs to Ada
            'title' => 'Physics every Wednesday',
            'repeat' => 'weekly',
            'repeats_from' => '2026-11-02',
            'repeat_until' => '2026-11-15',
            'slots' => [
                ['day' => 3, 'start' => '14:00', 'end' => '15:30'],
            ],
        ])->assertRedirect(route('admin.schedules.index'));

        $sessions = ClassSchedule::where('class_id', $class->id)->get();

        $this->assertCount(2, $sessions);
        $this->assertTrue($sessions->every(function (ClassSchedule $session) use ($class, $physics, $ada) {
            return (int) $session->class_id === (int) $class->id
                && (int) $session->class_subject_id === (int) $physics->id
                && (int) $session->tutor_id === (int) $ada->id;
        }));
    }

    public function test_a_subject_without_a_teacher_cannot_be_scheduled(): void
    {
        $admin = $this->makeUser('admin', 'No Teacher Admin');
        $john = $this->makeTutor('John Tutor');
        $class = $this->makeClass($john);
        $chemistry = $this->makeSubject($class, null, 'Chemistry');

        $this->actingAs($admin)->from(route('admin.schedules.create'))->post(route('admin.schedules.store'), [
            'class_subject_id' => $chemistry->id,
            'title' => 'Chemistry lesson',
            'start_time' => '2026-11-23 09:00:00',
            'end_time' => '2026-11-23 10:00:00',
        ])->assertSessionHasErrors('class_subject_id');

        $this->assertSame(0, ClassSchedule::where('class_id', $class->id)->count());
    }

    public function test_the_legacy_class_and_tutor_payload_still_creates_a_session(): void
    {
        $admin = $this->makeUser('admin', 'Legacy Schedule Admin');
        $john = $this->makeTutor('John Tutor');
        $class = $this->makeClass($john);

        $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'class_id' => $class->id,
            'tutor_id' => $john->id,
            'title' => 'Old style lesson',
            'start_time' => '2026-11-23 09:00:00',
            'end_time' => '2026-11-23 10:00:00',
        ])->assertRedirect(route('admin.schedules.index'));

        $schedule = ClassSchedule::where('class_id', $class->id)->firstOrFail();

        $this->assertNull($schedule->class_subject_id);
        $this->assertSame($john->id, $schedule->tutor_id);
        $this->assertSame('General', $schedule->subjectName());
    }

    public function test_a_tutor_can_schedule_only_their_own_subject(): void
    {
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada Physics');
        $class = $this->makeClass($john);

        $maths = $this->makeSubject($class, $john, 'Mathematics');
        $physics = $this->makeSubject($class, $ada, 'Physics');

        $this->actingAs($ada->user)
            ->get(route('tutor.schedules.create'))
            ->assertOk()
            ->assertSee($physics->subject->name)
            ->assertDontSee($maths->subject->name);

        $this->actingAs($ada->user)->post(route('tutor.schedules.store'), [
            'class_subject_id' => $physics->id,
            'title' => 'Physics lesson',
            'start_time' => '2026-11-23 11:00:00',
            'end_time' => '2026-11-23 12:00:00',
        ])->assertRedirect(route('tutor.schedules.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('class_schedules', [
            'class_id' => $class->id,
            'class_subject_id' => $physics->id,
            'tutor_id' => $ada->id,
        ]);

        // The same tutor cannot schedule the subject that belongs to John.
        $this->actingAs($ada->user)->from(route('tutor.schedules.create'))->post(route('tutor.schedules.store'), [
            'class_subject_id' => $maths->id,
            'title' => 'Not my subject',
            'start_time' => '2026-11-23 13:00:00',
            'end_time' => '2026-11-23 14:00:00',
        ])->assertSessionHasErrors('class_subject_id');

        $this->assertSame(1, ClassSchedule::where('class_id', $class->id)->count());
    }

    public function test_the_admin_form_lists_subjects_grouped_under_their_class(): void
    {
        $admin = $this->makeUser('admin', 'Form Subject Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada Physics');
        $class = $this->makeClass($john);

        $maths = $this->makeSubject($class, $john, 'Mathematics');
        $physics = $this->makeSubject($class, $ada, 'Physics');

        $this->actingAs($admin)
            ->get(route('admin.schedules.create'))
            ->assertOk()
            ->assertSee('name="class_subject_id"', false)
            ->assertSee($class->name)
            ->assertSee($maths->subject->name)
            ->assertSee($physics->subject->name)
            ->assertSee('John Tutor')
            ->assertSee('Ada Physics');
    }


    public function test_a_subject_with_scheduled_sessions_cannot_be_removed_from_the_class(): void
    {
        $admin = $this->makeUser('admin', 'Remove Guard Admin');
        $john = $this->makeTutor('John Tutor');
        $class = $this->makeClass($john);
        $maths = $this->makeSubject($class, $john, 'Mathematics');

        $schedule = ClassSchedule::create([
            'class_id' => $class->id,
            'class_subject_id' => $maths->id,
            'tutor_id' => $john->id,
            'title' => 'Mathematics lesson',
            'start_time' => '2026-11-23 09:00:00',
            'end_time' => '2026-11-23 10:00:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.classes.subjects.destroy', [$class, $maths]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('class_subject', ['id' => $maths->id]);

        // Once the lesson is gone the subject can be removed as usual.
        $this->actingAs($admin)
            ->delete(route('admin.schedules.destroy', $schedule))
            ->assertRedirect();

        $this->actingAs($admin)
            ->delete(route('admin.classes.subjects.destroy', [$class, $maths]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('class_subject', ['id' => $maths->id]);
    }

    public function test_editing_a_session_can_move_it_to_another_subject_and_teacher(): void
    {
        $admin = $this->makeUser('admin', 'Edit Subject Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada Physics');
        $class = $this->makeClass($john);

        $maths = $this->makeSubject($class, $john, 'Mathematics');
        $physics = $this->makeSubject($class, $ada, 'Physics');

        $schedule = ClassSchedule::create([
            'class_id' => $class->id,
            'class_subject_id' => $maths->id,
            'tutor_id' => $john->id,
            'title' => 'Lesson',
            'start_time' => '2026-11-23 09:00:00',
            'end_time' => '2026-11-23 10:00:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.schedules.update', $schedule), [
                'class_subject_id' => $physics->id,
                'title' => 'Lesson',
                'start_time' => '2026-11-23 09:00:00',
                'end_time' => '2026-11-23 10:00:00',
                'status' => 'scheduled',
            ])
            ->assertRedirect();

        $schedule = $schedule->fresh();

        $this->assertSame($physics->id, $schedule->class_subject_id);
        $this->assertSame($ada->id, $schedule->tutor_id);
        $this->assertSame($class->id, $schedule->class_id);
        $this->assertSame($physics->subject->name, $schedule->subjectName());
    }

    public function test_a_learner_sees_the_subject_of_each_session(): void
    {
        $john = $this->makeTutor('John Tutor');
        $class = $this->makeClass($john);
        $maths = $this->makeSubject($class, $john, 'Mathematics');

        $learner = $this->makeLearner('Subject Schedule Learner');
        $learner->classes()->attach($class->id);

        ClassSchedule::create([
            'class_id' => $class->id,
            'class_subject_id' => $maths->id,
            'tutor_id' => $john->id,
            'title' => 'Mathematics lesson',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]);

        $this->actingAs($learner->user)
            ->get(route('learner.classes'))
            ->assertOk()
            ->assertSee($maths->subject->name);
    }
}

