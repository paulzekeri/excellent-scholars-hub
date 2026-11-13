<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\ClassLibraryResource;
use App\Models\Learner;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * "Classroom library" — the school keeps videos, PDFs and documents in Google
 * Drive. Administrators and tutors attach those links to a class, and the
 * learners enrolled in it open them from My Learning → Class Library. The title
 * is the clickable name; the address itself is never printed on the page.
 */
class ClassLibraryTest extends TestCase
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
            'code' => 'CL-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);
    }

    private function makeLearner(string $name): Learner
    {
        return Learner::create([
            'user_id' => $this->makeUser('learner', $name)->id,
            'grade_level' => 'JSS 2',
        ]);
    }

    private function makeResource(Classes $class, User $addedBy, string $title, string $url = 'https://drive.google.com/file/d/library1/view'): ClassLibraryResource
    {
        return ClassLibraryResource::create([
            'class_id' => $class->id,
            'created_by' => $addedBy->id,
            'title' => $title,
            'url' => $url,
            'type' => 'link',
        ]);
    }

    public function test_an_admin_adds_a_link_and_the_enrolled_learner_can_open_it(): void
    {
        $admin = $this->makeUser('admin', 'Library Admin');
        $tutor = $this->makeTutor('Library Tutor');
        $class = $this->makeClass($tutor, 'JSS2 Maths');
        $learner = $this->makeLearner('Enrolled Learner');
        $learner->classes()->attach($class->id);

        $this->actingAs($admin)->post(route('admin.class-library.store'), [
            'class_id' => $class->id,
            'title' => 'Fractions video library',
            'description' => 'A walkthrough of fractions for the weekend.',
            'url' => 'https://drive.google.com/file/d/abc123/view',
            'type' => 'video',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('class_library_resources', [
            'class_id' => $class->id,
            'created_by' => $admin->id,
            'title' => 'Fractions video library',
            'url' => 'https://drive.google.com/file/d/abc123/view',
            'type' => 'video',
        ]);

        $response = $this->actingAs($learner->user)->get(route('learner.class-library.index'));

        $response->assertOk()
            ->assertSee('Fractions video library')
            ->assertSee('A walkthrough of fractions for the weekend.')
            ->assertSee('>Video</span>', false)
            ->assertSee('href="https://drive.google.com/file/d/abc123/view"', false)
            ->assertSee('target="_blank"', false);

        // The address stays under the hood: exactly one occurrence, in the href.
        $this->assertSame(1, substr_count($response->getContent(), 'drive.google.com/file/d/abc123'));
    }

    public function test_a_learner_does_not_see_links_for_classes_they_are_not_in(): void
    {
        $admin = $this->makeUser('admin', 'Library Admin');
        $tutor = $this->makeTutor('Library Tutor');
        $class = $this->makeClass($tutor, 'JSS2 Maths');
        $otherClass = $this->makeClass($tutor, 'JSS2 English');

        $learner = $this->makeLearner('Enrolled Learner');
        $learner->classes()->attach($class->id);
        $outsider = $this->makeLearner('Other Learner');
        $outsider->classes()->attach($otherClass->id);

        $this->makeResource($class, $admin, 'Maths drive folder');

        $this->actingAs($learner->user)->get(route('learner.class-library.index'))
            ->assertOk()
            ->assertSee('Maths drive folder')
            ->assertSee('JSS2 Maths');

        $this->actingAs($outsider->user)->get(route('learner.class-library.index'))
            ->assertOk()
            ->assertDontSee('Maths drive folder')
            ->assertSeeText('Your class library is empty for now');
    }

    public function test_the_admin_sees_every_link_and_can_filter_by_class(): void
    {
        $admin = $this->makeUser('admin', 'Library Admin');
        $tutor = $this->makeTutor('Library Tutor');
        $maths = $this->makeClass($tutor, 'Filter Maths');
        $english = $this->makeClass($tutor, 'Filter English');

        $this->makeResource($maths, $admin, 'Maths drive folder');
        $this->makeResource($english, $admin, 'English PDF pack');

        $this->actingAs($admin)->get(route('admin.class-library.index'))
            ->assertOk()
            ->assertSee('Maths drive folder')
            ->assertSee('English PDF pack')
            ->assertSee('Filter Maths');

        $this->actingAs($admin)->get(route('admin.class-library.index', ['class_id' => $maths->id]))
            ->assertOk()
            ->assertSee('Maths drive folder')
            ->assertDontSee('English PDF pack');
    }

    public function test_a_tutor_adds_a_link_to_their_own_class(): void
    {
        $tutor = $this->makeTutor('Class Tutor');
        $class = $this->makeClass($tutor, 'Tutor Class');
        $learner = $this->makeLearner('Tutor Class Learner');
        $learner->classes()->attach($class->id);

        $this->actingAs($tutor->user)->post(route('tutor.class-library.store'), [
            'class_id' => $class->id,
            'title' => 'Grammar worksheets',
            'description' => 'Printable PDFs for the weekend.',
            'url' => 'https://drive.google.com/drive/folders/grammar',
            'type' => 'pdf',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('class_library_resources', [
            'class_id' => $class->id,
            'created_by' => $tutor->user->id,
            'title' => 'Grammar worksheets',
            'type' => 'pdf',
        ]);

        $this->actingAs($learner->user)->get(route('learner.class-library.index'))
            ->assertOk()
            ->assertSee('Grammar worksheets')
            ->assertSee('Tutor Class');
    }

    public function test_a_tutor_cannot_add_a_link_to_another_tutors_class(): void
    {
        $tutorA = $this->makeTutor('Tutor A');
        $tutorB = $this->makeTutor('Tutor B');
        $classB = $this->makeClass($tutorB, 'Tutor B Class');

        $this->actingAs($tutorA->user)->post(route('tutor.class-library.store'), [
            'class_id' => $classB->id,
            'title' => 'Not mine',
            'url' => 'https://drive.google.com/file/d/nope/view',
        ])->assertSessionHasErrors('class_id');

        $this->assertDatabaseMissing('class_library_resources', ['title' => 'Not mine']);
    }

    public function test_a_tutor_manages_their_own_links_but_not_another_tutors(): void
    {
        $tutorA = $this->makeTutor('Tutor A');
        $tutorB = $this->makeTutor('Tutor B');
        $classA = $this->makeClass($tutorA, 'Tutor A Class');
        $classB = $this->makeClass($tutorB, 'Tutor B Class');

        $own = $this->makeResource($classA, $tutorA->user, 'Tutor A resource');
        $foreign = $this->makeResource($classB, $tutorB->user, 'Tutor B resource');

        $this->actingAs($tutorA->user)
            ->delete(route('tutor.class-library.destroy', $foreign))
            ->assertForbidden();
        $this->assertDatabaseHas('class_library_resources', ['id' => $foreign->id]);

        $this->actingAs($tutorA->user)
            ->delete(route('tutor.class-library.destroy', $own))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('class_library_resources', ['id' => $own->id]);
    }

    public function test_an_admin_removes_a_link(): void
    {
        $admin = $this->makeUser('admin', 'Library Admin');
        $tutor = $this->makeTutor('Library Tutor');
        $class = $this->makeClass($tutor, 'JSS2 Maths');
        $resource = $this->makeResource($class, $admin, 'Old link');

        $this->actingAs($admin)
            ->delete(route('admin.class-library.destroy', $resource))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('class_library_resources', ['id' => $resource->id]);
    }

    public function test_the_link_and_title_are_checked(): void
    {
        $admin = $this->makeUser('admin', 'Library Admin');
        $tutor = $this->makeTutor('Library Tutor');
        $class = $this->makeClass($tutor, 'JSS2 Maths');

        // A link without http(s) is refused, including a javascript: address.
        $this->actingAs($admin)->post(route('admin.class-library.store'), [
            'class_id' => $class->id,
            'title' => 'Half a link',
            'url' => 'drive.google.com/file/d/abc/view',
        ])->assertSessionHasErrors('url');

        $this->actingAs($admin)->post(route('admin.class-library.store'), [
            'class_id' => $class->id,
            'title' => 'Script',
            'url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('url');

        $this->actingAs($admin)->post(route('admin.class-library.store'), [
            'class_id' => $class->id,
            'url' => 'https://drive.google.com/file/d/abc/view',
        ])->assertSessionHasErrors('title');

        $this->assertDatabaseCount('class_library_resources', 0);
    }

    public function test_the_tutor_library_page_lists_only_their_own_classes(): void
    {
        $tutorA = $this->makeTutor('Tutor A');
        $tutorB = $this->makeTutor('Tutor B');
        $classA = $this->makeClass($tutorA, 'Tutor A Class');
        $classB = $this->makeClass($tutorB, 'Tutor B Class');

        $this->makeResource($classA, $tutorA->user, 'My grammar pack');
        $this->makeResource($classB, $tutorB->user, 'Not mine');

        $this->actingAs($tutorA->user)->get(route('tutor.class-library.index'))
            ->assertOk()
            ->assertSee('My grammar pack')
            ->assertDontSee('Not mine')
            ->assertDontSee('Tutor B Class');

        // A tutor with no classes is told why the form is not shown.
        $this->actingAs($this->makeTutor('New Tutor')->user)
            ->get(route('tutor.class-library.index'))
            ->assertOk()
            ->assertSeeText('You have no active classes yet');
    }

    public function test_only_the_right_roles_reach_each_library_page(): void
    {
        $tutor = $this->makeTutor('Role Tutor');
        $learner = $this->makeLearner('Role Learner');

        $this->actingAs($tutor->user)->get(route('admin.class-library.index'))->assertForbidden();
        $this->actingAs($learner->user)->get(route('tutor.class-library.index'))->assertForbidden();
    }
}
