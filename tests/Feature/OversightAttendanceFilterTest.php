<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The "Filter by Class" dropdown on the admin oversight pages posts
 * ?class_id=N, but the actions used to ignore it: the page always rendered
 * every class and reset the dropdown to "All Classes".
 */
class OversightAttendanceFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ]);
    }

    /**
     * One class, one learner with a completed lesson and a "present" record.
     *
     * @return array{0: Classes, 1: string} the class and the learner's display name
     */
    private function makeClassWithAttendance(string $suffix): array
    {
        $tutor = Tutor::create(['user_id' => $this->makeUser('tutor', 'Tutor ' . $suffix)->id]);

        $class = Classes::create([
            'name' => 'Class ' . $suffix,
            'code' => 'COD-' . $suffix . '-' . uniqid(),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);

        $learnerName = 'Learner ' . $suffix;
        $learner = Learner::create([
            'user_id' => $this->makeUser('learner', $learnerName)->id,
            'grade_level' => 'JSS 2',
        ]);

        $schedule = ClassSchedule::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Lesson ' . $suffix,
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHour(),
            'status' => 'completed',
        ]);

        Attendance::create([
            'class_schedule_id' => $schedule->id,
            'learner_id' => $learner->id,
            'status' => 'present',
            'marked_at' => now()->subDay(),
        ]);

        return [$class, $learnerName];
    }

    public function test_the_page_lists_every_class_when_no_filter_is_chosen(): void
    {
        $admin = $this->makeUser('admin', 'Oversight Admin');
        [$classA, $learnerA] = $this->makeClassWithAttendance('A' . uniqid());
        [, $learnerB] = $this->makeClassWithAttendance('B' . uniqid());

        $this->actingAs($admin)
            ->get(route('admin.oversight.attendance'))
            ->assertOk()
            ->assertSee($learnerA)
            ->assertSee($learnerB);
    }

    public function test_choosing_a_class_filters_the_table_and_keeps_the_selection(): void
    {
        $admin = $this->makeUser('admin', 'Oversight Admin');
        [$classA, $learnerA] = $this->makeClassWithAttendance('A' . uniqid());
        [, $learnerB] = $this->makeClassWithAttendance('B' . uniqid());

        $this->actingAs($admin)
            ->get(route('admin.oversight.attendance', ['class_id' => $classA->id]))
            ->assertOk()
            ->assertSee($learnerA)
            ->assertDontSee($learnerB)
            ->assertSee('value="' . $classA->id . '" selected', false);
    }

    public function test_an_unknown_class_id_falls_back_to_all_classes(): void
    {
        $admin = $this->makeUser('admin', 'Oversight Admin');
        [, $learnerA] = $this->makeClassWithAttendance('A' . uniqid());
        [, $learnerB] = $this->makeClassWithAttendance('B' . uniqid());

        $this->actingAs($admin)
            ->get(route('admin.oversight.attendance', ['class_id' => 999999]))
            ->assertOk()
            ->assertSee($learnerA)
            ->assertSee($learnerB);
    }

    public function test_the_class_route_still_filters_and_404s_for_an_unknown_class(): void
    {
        $admin = $this->makeUser('admin', 'Oversight Admin');
        [$classA, $learnerA] = $this->makeClassWithAttendance('A' . uniqid());
        [, $learnerB] = $this->makeClassWithAttendance('B' . uniqid());

        $this->actingAs($admin)
            ->get(route('admin.oversight.attendance.class', $classA->id))
            ->assertOk()
            ->assertSee($learnerA)
            ->assertDontSee($learnerB);

        $this->actingAs($admin)
            ->get(route('admin.oversight.attendance.class', 999999))
            ->assertNotFound();
    }

    public function test_the_assignments_and_materials_pages_also_honour_the_filter(): void
    {
        $admin = $this->makeUser('admin', 'Oversight Admin');
        [$classA] = $this->makeClassWithAttendance('A' . uniqid());

        $this->actingAs($admin)
            ->get(route('admin.oversight.assignments', ['class_id' => $classA->id]))
            ->assertOk()
            ->assertSee('value="' . $classA->id . '" selected', false);

        $this->actingAs($admin)
            ->get(route('admin.oversight.materials', ['class_id' => $classA->id]))
            ->assertOk()
            ->assertSee('value="' . $classA->id . '" selected', false);
    }
}
