<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Enquiry;
use App\Models\Grade;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Grade entry and management.
 *
 * The admin/CEO maintains the list once, and every grade dropdown (learner
 * registration, admin user creation, assessment booking, enquiry capture and
 * class creation) reads from it.
 */
class GradeManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => $name,
            'email' => strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => true,
        ], $extra));
    }

    private function makeParent(string $name): array
    {
        $user = $this->makeUser('parent', $name);
        $parent = ParentModel::create(['user_id' => $user->id]);

        return [$user, $parent];
    }

    private function makeGrade(string $name = 'Test KG'): Grade
    {
        return Grade::create([
            'name' => $name,
            'sort_order' => 999,
            'is_active' => true,
        ]);
    }

    public function test_the_admin_adds_a_grade_and_it_appears_in_every_grade_dropdown(): void
    {
        // Guests see the landing-page booking form.
        $this->get('/')->assertOk()->assertSee('name="grade_id"', false);

        $admin = $this->makeUser('admin', 'Grade Office');

        $this->actingAs($admin)
            ->post(route('admin.grades.store'), [
                'name' => 'Primary 7 - Primary',
                'sort_order' => 75,
            ])
            ->assertRedirect();

        $grade = Grade::where('name', 'Primary 7 - Primary')->firstOrFail();

        $this->assertSame('Primary 7 - Primary', $grade->label());

        $this->actingAs($admin)
            ->get(route('admin.grades.index'))
            ->assertOk()
            ->assertSee('Primary 7 - Primary');

        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertOk()->assertSee('Primary 7 - Primary');

        $this->actingAs($admin)->get(route('admin.enquiries.create'))
            ->assertOk()->assertSee('Primary 7 - Primary');

        $this->actingAs($admin)->get(route('admin.classes.create'))
            ->assertOk()->assertSee('Primary 7 - Primary');

        // Registration is a guest page.
        auth()->logout();

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Primary 7 - Primary');
    }

    public function test_a_learner_can_register_with_a_grade(): void
    {
        $grade = $this->makeGrade('Reg KG - Pre Primary');

        $this->post(route('register'), [
            'name' => 'Grade Child',
            'username' => 'grade_child_' . uniqid(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
            'grade_id' => $grade->id,
            'parent_name' => 'Grade Guardian',
            'parent_email' => 'grade.guardian.' . uniqid() . '@example.com',
        ])->assertRedirect();

        $user = User::where('name', 'Grade Child')->firstOrFail();
        $learner = $user->learner;

        $this->assertSame($grade->id, $learner->grade_id);
        $this->assertSame('Reg KG - Pre Primary', $learner->grade_level);
    }

    public function test_an_admin_can_create_a_learner_with_a_grade(): void
    {
        $admin = $this->makeUser('admin', 'Learner Grade Admin');
        [, $parent] = $this->makeParent('Learner Grade Parent');
        $grade = $this->makeGrade('KG1X - Pre Primary');
        $username = 'grade_learner_' . substr(uniqid(), -8);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Admin Grade Child',
            'username' => $username,
            'password' => 'password123',
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
            'parent_id' => $parent->id,
            'grade_id' => $grade->id,
        ])->assertRedirect(route('admin.users.index'));

        $learner = User::where('username', $username)->firstOrFail()->learner;

        $this->assertSame($grade->id, $learner->grade_id);
        $this->assertSame('KG1X - Pre Primary', $learner->grade_level);
    }

    public function test_a_child_added_under_a_parent_keeps_the_selected_grade(): void
    {
        $admin = $this->makeUser('admin', 'Child Grade Admin');
        [$parentUser, $parent] = $this->makeParent('Child Grade Parent');
        $grade = $this->makeGrade('KG2X - Pre Primary');
        $username = 'grade_child_' . substr(uniqid(), -8);

        $this->actingAs($admin)->post(route('admin.users.children.store', $parentUser), [
            'name' => 'Added Grade Child',
            'username' => $username,
            'password' => 'password123',
            'grade_id' => $grade->id,
        ])->assertRedirect(route('admin.users.edit', $parentUser));

        $learner = User::where('username', $username)->firstOrFail()->learner;

        $this->assertSame($parent->id, $learner->parent_id);
        $this->assertSame($grade->id, $learner->grade_id);
        $this->assertSame('KG2X - Pre Primary', $learner->grade_level);
    }

    public function test_a_public_assessment_booking_records_the_selected_grade(): void
    {
        $grade = $this->makeGrade('Booking KG - Pre Primary');

        $this->post(route('assessments.request'), [
            'child_name' => 'Booking Grade Child',
            'grade_id' => $grade->id,
            'guardian_name' => 'Booking Guardian',
            'guardian_phone' => '08030000001',
            'guardian_email' => 'booking.guardian.' . uniqid() . '@example.com',
        ])->assertRedirect('/#book-assessment')->assertSessionHas('success');

        $enquiry = Enquiry::where('child_name', 'Booking Grade Child')->firstOrFail();

        $this->assertSame($grade->id, $enquiry->grade_id);
        $this->assertSame('Booking KG - Pre Primary', $enquiry->child_grade_level);
    }

    public function test_an_admin_enquiry_records_the_selected_grade(): void
    {
        $admin = $this->makeUser('admin', 'Enquiry Grade Admin');
        $grade = $this->makeGrade('Enquiry KG - Pre Primary');

        $this->actingAs($admin)->post(route('admin.enquiries.store'), [
            'child_name' => 'Enquiry Grade Child',
            'grade_id' => $grade->id,
            'guardian_name' => 'Enquiry Guardian',
            'guardian_phone' => '08030000002',
            'source' => Enquiry::SOURCE_WALK_IN,
        ])->assertRedirect();

        $enquiry = Enquiry::where('child_name', 'Enquiry Grade Child')->firstOrFail();

        $this->assertSame($grade->id, $enquiry->grade_id);
        $this->assertSame('Enquiry KG - Pre Primary', $enquiry->child_grade_level);
    }

    public function test_renaming_a_grade_updates_learners_classes_and_bookings(): void
    {
        $admin = $this->makeUser('admin', 'Rename Grade Admin');
        $grade = $this->makeGrade('Rename KG ' . substr(uniqid(), -5) . ' - Pre Primary');
        $newName = 'Renamed KG ' . substr(uniqid(), -5) . ' - Pre Primary';

        $learner = Learner::create([
            'user_id' => $this->makeUser('learner', 'Rename Grade Learner')->id,
            'grade_id' => $grade->id,
            'grade_level' => $grade->label(),
        ]);

        $tutor = Tutor::create(['user_id' => $this->makeUser('tutor', 'Rename Grade Tutor')->id]);
        $class = Classes::create([
            'name' => 'Rename Grade Class',
            'code' => 'RG-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'grade_id' => $grade->id,
            'grade_level' => $grade->label(),
            'is_active' => true,
        ]);

        $enquiry = Enquiry::create([
            'child_name' => 'Rename Grade Child',
            'grade_id' => $grade->id,
            'child_grade_level' => $grade->label(),
            'guardian_name' => 'Rename Grade Guardian',
            'guardian_phone' => '08030000003',
            'source' => Enquiry::SOURCE_WALK_IN,
            'status' => Enquiry::STATUS_NEW,
        ]);

        $this->actingAs($admin)->put(route('admin.grades.update', $grade), [
            'name' => $newName,
            'is_active' => 1,
        ])->assertRedirect();

        $label = $newName;

        $this->assertSame($label, $grade->fresh()->label());
        $this->assertSame($label, $learner->fresh()->grade_level);
        $this->assertSame($label, $class->fresh()->grade_level);
        $this->assertSame($label, $enquiry->fresh()->child_grade_level);
    }

    public function test_a_grade_in_use_cannot_be_deleted_but_an_unused_one_can(): void
    {
        $admin = $this->makeUser('admin', 'Delete Grade Admin');
        $used = $this->makeGrade('Used Grade ' . substr(uniqid(), -5));
        $unused = $this->makeGrade('Free Grade ' . substr(uniqid(), -5));

        $learner = Learner::create([
            'user_id' => $this->makeUser('learner', 'Used Grade Learner')->id,
            'grade_id' => $used->id,
            'grade_level' => $used->label(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.grades.destroy', $used))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('grades', ['id' => $used->id]);

        $this->actingAs($admin)
            ->delete(route('admin.grades.destroy', $unused))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('grades', ['id' => $unused->id]);
        $this->assertSame($used->id, $learner->fresh()->grade_id);
    }
}
