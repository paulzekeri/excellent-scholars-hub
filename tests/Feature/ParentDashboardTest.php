<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\LearningMaterial;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\Submission;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentDashboardTest extends TestCase
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

    private function makeParentWithChild(string $name): array
    {
        $parentUser = $this->makeUser('parent', $name);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);

        $learnerUser = $this->makeUser('learner', $name . ' Child');
        $learner = Learner::create([
            'user_id' => $learnerUser->id,
            'parent_id' => $parent->id,
            'grade_level' => 'Grade 9',
        ]);

        return [$parentUser, $learner];
    }

    private function makeTutorWithClass(Learner $learner): array
    {
        $tutorUser = $this->makeUser('tutor', 'Tutor ' . uniqid());
        $tutor = Tutor::create(['user_id' => $tutorUser->id]);

        $class = Classes::create([
            'name' => 'Science 9',
            'code' => 'SCI9-' . uniqid(),
            'tutor_id' => $tutor->id,
            'grade_level' => 'Grade 9',
            'is_active' => true,
        ]);
        $class->learners()->attach($learner->id);

        return [$tutor, $class];
    }

    private function makeSchedule(Tutor $tutor, Classes $class, string $title, $start): ClassSchedule
    {
        return ClassSchedule::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => $title,
            'start_time' => $start,
            'end_time' => (clone $start)->addHour(),
        ]);
    }

    private function makeAssignment(Tutor $tutor, Classes $class, string $title, $due): Assignment
    {
        return Assignment::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => $title,
            'due_date' => $due,
            'total_points' => 100,
            'status' => 'active',
        ]);
    }

    public function test_parent_dashboard_shows_per_child_stats()
    {
        [$parentUser, $learner] = $this->makeParentWithChild('Stats Parent');
        [$tutor, $class] = $this->makeTutorWithClass($learner);

        $this->makeSchedule($tutor, $class, 'Upcoming Lesson', now()->addDays(2));
        $pastSession = $this->makeSchedule($tutor, $class, 'Past Lesson', now()->subDays(2));

        $this->makeAssignment($tutor, $class, 'Pending Task One', now()->addDays(3));
        $this->makeAssignment($tutor, $class, 'Pending Task Two', now()->addDays(4));

        $graded = $this->makeAssignment($tutor, $class, 'Graded Essay', now()->subDays(6));
        Submission::create([
            'assignment_id' => $graded->id,
            'learner_id' => $learner->id,
            'status' => 'submitted',
            'submitted_at' => now()->subDays(5),
        ]);

        LearningMaterial::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Chapter Notes',
            'file_path' => 'materials/chapter-notes.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 2048,
            'status' => 'published',
        ]);

        Attendance::create([
            'class_schedule_id' => $pastSession->id,
            'learner_id' => $learner->id,
            'status' => 'absent',
            'marked_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($parentUser)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Assignments Pending')
            ->assertSee('Assignments Completed')
            ->assertSee('Materials Shared')
            ->assertSee('1 missed class recorded')
            ->assertSee('tab=pending', false);

        $stats = $response->viewData('childStats')->get($learner->id);

        $this->assertSame(1, $stats['enrolledClasses']);
        $this->assertSame(1, $stats['upcomingClasses']);
        $this->assertSame(2, $stats['pendingAssignments']);
        $this->assertSame(1, $stats['completedAssignments']);
        $this->assertSame(1, $stats['materials']);
        $this->assertSame(1, $stats['missedClasses']);
        $this->assertSame(1, $stats['attendanceRecorded']);
        $this->assertSame(0, $stats['attendanceAttended']);
        $this->assertSame(0, $stats['attendanceRate']);
    }

    public function test_child_details_tabs_list_the_matching_records()
    {
        [$parentUser, $learner] = $this->makeParentWithChild('Tabs Parent');
        [$tutor, $class] = $this->makeTutorWithClass($learner);

        $this->makeSchedule($tutor, $class, 'Upcoming Lesson', now()->addDays(2));
        $pastSession = $this->makeSchedule($tutor, $class, 'Past Lesson', now()->subDays(2));
        $this->makeAssignment($tutor, $class, 'Pending Task', now()->addDays(3));
        $graded = $this->makeAssignment($tutor, $class, 'Graded Essay', now()->subDays(6));

        Submission::create([
            'assignment_id' => $graded->id,
            'learner_id' => $learner->id,
            'status' => 'graded',
            'score' => 88,
            'feedback' => 'Excellent structure',
            'submitted_at' => now()->subDays(5),
            'graded_at' => now()->subDays(4),
        ]);

        LearningMaterial::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Chapter Notes',
            'file_path' => 'materials/chapter-notes.pdf',
            'status' => 'published',
        ]);

        Attendance::create([
            'class_schedule_id' => $pastSession->id,
            'learner_id' => $learner->id,
            'status' => 'absent',
            'marked_at' => now()->subDays(2),
        ]);

        $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule']))
            ->assertOk()
            ->assertSee('Upcoming Lesson')
            ->assertDontSee('Pending Task');

        $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'pending']))
            ->assertOk()
            ->assertSee('Pending Task')
            ->assertDontSee('Upcoming Lesson');

        $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'completed']))
            ->assertOk()
            ->assertSee('Graded Essay')
            ->assertSee('Excellent structure')
            ->assertSee('88 / 100');

        $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'materials']))
            ->assertOk()
            ->assertSee('Chapter Notes')
            ->assertSee('Download');

        $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'attendance']))
            ->assertOk()
            ->assertSee('Classes Missed')
            ->assertSee('Past Lesson')
            ->assertSee('Absent');
    }

    public function test_parent_cannot_view_another_parents_child()
    {
        [, $learner] = $this->makeParentWithChild('First Parent');
        [$otherParentUser] = $this->makeParentWithChild('Second Parent');

        $this->actingAs($otherParentUser)
            ->get(route('parent.child.progress', $learner))
            ->assertForbidden();
    }

    public function test_unknown_tab_falls_back_to_overview()
    {
        [$parentUser, $learner] = $this->makeParentWithChild('Fallback Parent');

        $response = $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'nonsense']))
            ->assertOk()
            ->assertSee('Upcoming Classes');

        $this->assertSame('overview', $response->viewData('tab'));
    }

    public function test_parent_without_profile_sees_empty_dashboard()
    {
        $user = $this->makeUser('parent', 'No Profile Parent');

        $this->actingAs($user)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('No children linked to your account yet.');
    }

    public function test_past_sessions_without_attendance_are_not_counted_as_missed()
    {
        [$parentUser, $learner] = $this->makeParentWithChild('Unrecorded Parent');
        [$tutor, $class] = $this->makeTutorWithClass($learner);

        $this->makeSchedule($tutor, $class, 'Unmarked Session', now()->subDays(3));

        $response = $this->actingAs($parentUser)
            ->get(route('parent.child.progress', ['learner' => $learner, 'tab' => 'attendance']))
            ->assertOk()
            ->assertSee('1 past session without recorded attendance')
            ->assertSee('No missed classes recorded');

        $this->assertSame(0, $response->viewData('stats')['missedClasses']);
    }
}
