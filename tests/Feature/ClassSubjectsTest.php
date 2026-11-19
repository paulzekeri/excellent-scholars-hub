<?php

namespace Tests\Feature;

use App\Models\Classes;
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
 * A class teaches several subjects, and each subject has its own teacher.
 *
 *   Test Class WAEC → Mathematics → John Tutor
 *   Test Class WAEC → Physics     → John Tutor   (same tutor, second subject)
 *   Test Class WAEC → English     → another tutor
 *   Test Class WAEC → Chemistry   → not assigned yet
 *
 * The old single `subject` column and `tutor_id` stay in place (lead tutor), so
 * every screen written before this feature keeps working.
 */
class ClassSubjectsTest extends TestCase
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
            'code' => 'CLS-' . strtoupper(uniqid()),
            'tutor_id' => $lead->id,
            'grade_level' => 'SS3',
            'is_active' => true,
        ]);
    }

    private function makeLearner(string $name): Learner
    {
        return Learner::create([
            'user_id' => $this->makeUser('learner', $name)->id,
            'grade_level' => 'SS3',
        ]);
    }

    private function classPayload(Tutor $lead, array $subjects, string $name = 'Test Class WAEC'): array
    {
        return [
            'name' => $name,
            'tutor_id' => $lead->id,
            'grade_level' => 'SS3',
            'subjects' => $subjects,
        ];
    }

    public function test_a_class_can_teach_several_subjects_and_one_tutor_can_take_more_than_one(): void
    {
        $admin = $this->makeUser('admin', 'Subject Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');

        $this->actingAs($admin)
            ->post(route('admin.classes.store'), $this->classPayload($john, [
                ['subject' => 'Mathematics', 'tutor_id' => $john->id],
                ['subject' => 'Physics', 'tutor_id' => $john->id],
                ['subject' => 'English', 'tutor_id' => $ada->id],
                ['subject' => 'Chemistry', 'tutor_id' => ''],
            ]))
            ->assertRedirect(route('admin.classes.index'));

        $class = Classes::where('name', 'Test Class WAEC')->firstOrFail();
        $class->load('subjectAssignments.subject');

        $this->assertSame(4, $class->subjectAssignments->count());
        $this->assertSame('Mathematics', $class->subject);
        $this->assertSame('Mathematics, Physics, English, Chemistry', $class->subjectSummary());

        // John takes two subjects; Ada one; Chemistry is waiting for a teacher.
        $this->assertSame(2, $class->subjectAssignments->where('tutor_id', $john->id)->count());
        $this->assertSame(1, $class->subjectAssignments->where('tutor_id', $ada->id)->count());

        $chemistry = $class->subjectAssignments->first(function (ClassSubject $row) {
            return $row->subject->name === 'Chemistry';
        });

        $this->assertNotNull($chemistry);
        $this->assertNull($chemistry->tutor_id);

        // Both teachers count as teachers of the class for permissions.
        $this->assertTrue($class->isTaughtBy($john->id));
        $this->assertTrue($class->isTaughtBy($ada->id));
        $this->assertContains($class->id, Classes::idsTaughtBy($john->id));
        $this->assertContains($class->id, Classes::idsTaughtBy($ada->id));

        $this->actingAs($admin)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertSee('Physics')
            ->assertSee('Ada English')
            ->assertSee('Not assigned yet');
    }

    public function test_editing_a_class_replaces_its_subject_rows_and_keeps_the_legacy_column_in_step(): void
    {
        $admin = $this->makeUser('admin', 'Edit Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');

        $class = $this->makeClass($john);
        $class->syncSubjectAssignments([
            ['subject' => 'Mathematics', 'tutor_id' => $john->id],
            ['subject' => 'Physics', 'tutor_id' => $john->id],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.classes.update', $class), [
                'name' => $class->name,
                'tutor_id' => $john->id,
                'subjects' => [
                    ['subject' => 'English', 'tutor_id' => $ada->id],
                    ['subject' => 'Biology', 'tutor_id' => ''],
                ],
            ])
            ->assertRedirect(route('admin.classes.index'));

        $class = $class->fresh()->load('subjectAssignments.subject');

        $this->assertSame(['English', 'Biology'], $class->subjectAssignments->pluck('subject.name')->all());
        $this->assertSame('English', $class->subject);
        $this->assertSame('English, Biology', $class->subjectSummary());
    }

    public function test_subjects_can_be_added_reassigned_and_removed_from_the_class_page(): void
    {
        $admin = $this->makeUser('admin', 'Show Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');

        $class = $this->makeClass($john);
        $class->syncSubjectAssignments([
            ['subject' => 'Mathematics', 'tutor_id' => $john->id],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.classes.show', $class))
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertSee('John Tutor');

        // Add Physics with a different teacher.
        $this->actingAs($admin)
            ->post(route('admin.classes.subjects.store', $class), [
                'subject' => 'Physics',
                'tutor_id' => $ada->id,
            ])
            ->assertRedirect();

        $physics = $class->subjectAssignments()
            ->whereHas('subject', function ($query) {
                $query->where('name', 'Physics');
            })
            ->firstOrFail();

        $this->assertSame($ada->id, $physics->tutor_id);
        $this->assertSame('Mathematics', $class->fresh()->subject);

        // Adding the same subject again changes its teacher, not the row count.
        $this->actingAs($admin)
            ->post(route('admin.classes.subjects.store', $class), [
                'subject' => 'Physics',
                'tutor_id' => $john->id,
            ])
            ->assertRedirect();

        $this->assertSame(2, $class->subjectAssignments()->count());
        $this->assertSame($john->id, $physics->fresh()->tutor_id);

        // Removing the first subject moves the old column to the next subject.
        $maths = $class->subjectAssignments()
            ->whereHas('subject', function ($query) {
                $query->where('name', 'Mathematics');
            })
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.classes.subjects.destroy', [$class, $maths]))
            ->assertRedirect();

        $this->assertNull(ClassSubject::find($maths->id));
        $this->assertSame('Physics', $class->fresh()->subject);
        $this->assertSame(1, $class->subjectAssignments()->count());
    }

    public function test_the_old_single_subject_form_still_creates_one_subject_row(): void
    {
        $admin = $this->makeUser('admin', 'Legacy Admin');
        $john = $this->makeTutor('John Tutor');

        // The field list used before this feature (no `subjects` array).
        $this->actingAs($admin)
            ->post(route('admin.classes.store'), [
                'name' => 'Legacy Class',
                'tutor_id' => $john->id,
                'subject' => 'Mathematics',
                'grade_level' => 'JSS1',
            ])
            ->assertRedirect(route('admin.classes.index'));

        $class = Classes::where('name', 'Legacy Class')->firstOrFail();
        $row = $class->subjectAssignments()->with('subject')->firstOrFail();

        $this->assertSame('Mathematics', $row->subject->name);
        $this->assertSame($john->id, $row->tutor_id);
        $this->assertSame('Mathematics', $class->subject);
    }


    public function test_a_learner_sees_every_subject_and_its_teacher(): void
    {
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');

        $class = $this->makeClass($john);
        $class->syncSubjectAssignments([
            ['subject' => 'Mathematics', 'tutor_id' => $john->id],
            ['subject' => 'Physics', 'tutor_id' => $ada->id],
            ['subject' => 'Chemistry', 'tutor_id' => ''],
        ]);

        $learner = $this->makeLearner('Subject Learner');
        $learner->classes()->attach($class->id);

        $this->actingAs($learner->user)
            ->get(route('learner.classes'))
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertSee('John Tutor')
            ->assertSee('Physics')
            ->assertSee('Ada English')
            ->assertSee('Teacher not assigned yet');
    }

    public function test_a_subject_teacher_can_use_the_class_but_a_stranger_cannot(): void
    {
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');
        $outsider = $this->makeTutor('Outside Tutor');

        $class = $this->makeClass($john, 'WAEC Science');
        $class->syncSubjectAssignments([
            ['subject' => 'Mathematics', 'tutor_id' => $john->id],
            ['subject' => 'Physics', 'tutor_id' => $ada->id],
        ]);

        // Ada is not the lead tutor, but she takes Physics in the class.
        $this->actingAs($ada->user)
            ->get(route('tutor.class-library.index'))
            ->assertOk()
            ->assertSee('WAEC Science');

        $this->actingAs($ada->user)
            ->get(route('tutor.assignments.create'))
            ->assertOk()
            ->assertSee('WAEC Science');

        $this->actingAs($ada->user)
            ->post(route('tutor.class-library.store'), [
                'class_id' => $class->id,
                'title' => 'Physics past questions',
                'url' => 'https://drive.google.com/file/d/physics/view',
                'type' => 'link',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('class_library_resources', [
            'class_id' => $class->id,
            'created_by' => $ada->user_id,
            'title' => 'Physics past questions',
        ]);

        // A tutor with no subject in this class is still refused.
        $this->actingAs($outsider->user)
            ->post(route('tutor.class-library.store'), [
                'class_id' => $class->id,
                'title' => 'Should not be allowed',
                'url' => 'https://example.com/nope',
                'type' => 'link',
            ])
            ->assertSessionHasErrors('class_id');

        $this->assertDatabaseMissing('class_library_resources', [
            'class_id' => $class->id,
            'title' => 'Should not be allowed',
        ]);
    }

    public function test_the_create_and_edit_forms_show_the_subject_rows(): void
    {
        $admin = $this->makeUser('admin', 'Form Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');

        $class = $this->makeClass($john);
        $class->syncSubjectAssignments([
            ['subject' => 'Mathematics', 'tutor_id' => $john->id],
            ['subject' => 'Physics', 'tutor_id' => $ada->id],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.classes.create'))
            ->assertOk()
            ->assertSee('Subjects & Teachers')
            ->assertSee('Add another subject')
            ->assertSee('Not assigned yet');

        $this->actingAs($admin)
            ->get(route('admin.classes.edit', $class))
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertSee('Physics')
            ->assertSee('Ada English')
            ->assertSee('subjects[1][tutor_id]', false);
    }

    public function test_a_class_without_subject_rows_falls_back_to_the_old_subject_column(): void
    {
        $john = $this->makeTutor('John Tutor');

        // A class inserted directly (an import, a seeder, another integration)
        // has no subject rows. The old column must still describe it.
        $class = $this->makeClass($john);
        $class->forceFill(['subject' => 'Further Mathematics'])->save();

        $this->assertSame('Further Mathematics', $class->fresh()->subjectSummary());
        $this->assertSame(0, $class->subjectAssignments()->count());
    }

    public function test_the_subject_page_adds_a_subject_that_then_appears_on_the_class_form(): void
    {
        $admin = $this->makeUser('admin', 'Subject Office');
        $name = 'Biology ' . strtoupper(uniqid());

        $this->actingAs($admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertSee('Subjects');

        $this->actingAs($admin)
            ->post(route('admin.subjects.store'), ['name' => $name])
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', ['name' => $name]);

        // The new subject is in the class form's dropdown, not a fixed list.
        $this->actingAs($admin)
            ->get(route('admin.classes.create'))
            ->assertOk()
            ->assertSee($name)
            ->assertSee('Add a new subject');

        // The same name cannot be added twice.
        $this->actingAs($admin)
            ->post(route('admin.subjects.store'), ['name' => $name])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Subject::where('name', $name)->count());
    }

    public function test_a_subject_can_be_renamed_and_every_class_shows_the_new_name(): void
    {
        $admin = $this->makeUser('admin', 'Rename Admin');
        $john = $this->makeTutor('John Tutor');

        $original = 'Maths ' . strtoupper(uniqid());
        $renamed = 'Mathematics ' . strtoupper(uniqid());

        $subject = Subject::create(['name' => $original, 'is_active' => true]);

        $class = $this->makeClass($john);
        $class->syncSubjectAssignments([
            ['subject_id' => $subject->id, 'tutor_id' => $john->id],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.subjects.update', $subject), [
                'name' => $renamed,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame($renamed, $subject->fresh()->name);
        $this->assertSame($renamed, $class->fresh()->subjectSummary());

        $this->actingAs($admin)
            ->get(route('admin.classes.show', $class))
            ->assertOk()
            ->assertSee($renamed);
    }

    public function test_a_subject_used_by_a_class_cannot_be_deleted_but_an_unused_one_can(): void
    {
        $admin = $this->makeUser('admin', 'Delete Admin');
        $john = $this->makeTutor('John Tutor');

        $used = Subject::create(['name' => 'Physics ' . strtoupper(uniqid()), 'is_active' => true]);
        $unused = Subject::create(['name' => 'Geography ' . strtoupper(uniqid()), 'is_active' => true]);

        $class = $this->makeClass($john);
        $class->syncSubjectAssignments([
            ['subject_id' => $used->id, 'tutor_id' => $john->id],
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.subjects.destroy', $used))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('subjects', ['id' => $used->id]);
        $this->assertSame(1, $class->subjectAssignments()->count());

        $this->actingAs($admin)
            ->delete(route('admin.subjects.destroy', $unused))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('subjects', ['id' => $unused->id]);
    }

    public function test_a_class_can_be_saved_by_picking_subjects_from_the_dropdown(): void
    {
        $admin = $this->makeUser('admin', 'Dropdown Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');

        $mathsName = 'Mathematics ' . strtoupper(uniqid());
        $englishName = 'English ' . strtoupper(uniqid());

        $maths = Subject::create(['name' => $mathsName, 'is_active' => true]);
        $english = Subject::create(['name' => $englishName, 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.classes.store'), [
                'name' => 'Test Class WAEC',
                'tutor_id' => $john->id,
                'subjects' => [
                    ['subject_id' => $maths->id, 'tutor_id' => $john->id],
                    ['subject_id' => $english->id, 'tutor_id' => $ada->id],
                ],
            ])
            ->assertRedirect(route('admin.classes.index'));

        $class = Classes::where('name', 'Test Class WAEC')->firstOrFail();
        $class->load('subjectAssignments.subject');

        $this->assertSame(
            [$maths->id, $english->id],
            $class->subjectAssignments->pluck('subject_id')->all()
        );
        $this->assertSame($mathsName . ', ' . $englishName, $class->subjectSummary());
        $this->assertSame($mathsName, $class->subject);
    }

    public function test_the_class_page_can_add_a_catalogue_subject_or_type_a_new_one(): void
    {
        $admin = $this->makeUser('admin', 'Page Admin');
        $john = $this->makeTutor('John Tutor');
        $ada = $this->makeTutor('Ada English');

        $biologyName = 'Biology ' . strtoupper(uniqid());
        $chemistryName = 'Chemistry ' . strtoupper(uniqid());

        $biology = Subject::create(['name' => $biologyName, 'is_active' => true]);
        $class = $this->makeClass($john);

        // Pick an existing subject from the dropdown.
        $this->actingAs($admin)
            ->post(route('admin.classes.subjects.store', $class), [
                'subject_id' => $biology->id,
                'tutor_id' => $ada->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('class_subject', [
            'class_id' => $class->id,
            'subject_id' => $biology->id,
            'tutor_id' => $ada->id,
        ]);

        // Or choose "+ Add a new subject…" and type the name.
        $this->actingAs($admin)
            ->post(route('admin.classes.subjects.store', $class), [
                'subject_id' => '__new__',
                'subject' => $chemistryName,
                'tutor_id' => $john->id,
            ])
            ->assertRedirect();

        $chemistry = Subject::where('name', $chemistryName)->firstOrFail();

        $this->assertDatabaseHas('class_subject', [
            'class_id' => $class->id,
            'subject_id' => $chemistry->id,
            'tutor_id' => $john->id,
        ]);
        $this->assertSame($biologyName . ', ' . $chemistryName, $class->fresh()->subjectSummary());
    }
}

