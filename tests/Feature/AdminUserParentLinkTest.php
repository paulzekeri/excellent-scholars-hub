<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserParentLinkTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser($roleSlug, $name, array $attributes = [])
    {
        return User::create(array_merge([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ], $attributes));
    }

    private function makeParent($name)
    {
        $parent = ParentModel::create(['user_id' => $this->makeUser('parent', $name)->id]);

        return $parent;
    }

    public function test_admin_can_create_a_learner_linked_to_a_parent()
    {
        $admin = $this->makeUser('admin', 'Test Admin');
        $parent = $this->makeParent('Test Parent One');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Test Learner One',
            'username' => 'test_learner_one',
            'email' => 'test.learner.one.' . uniqid() . '@example.com',
            'password' => 'password123',
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $learner = Learner::whereHas('user', function ($query) {
            $query->where('name', 'Test Learner One');
        })->firstOrFail();

        $this->assertSame($parent->id, $learner->parent_id);
        $this->assertSame(1, $parent->children()->count());
        // A school-provisioned child must choose their own password first.
        $this->assertTrue($learner->user->must_change_password);
    }

    public function test_a_learner_cannot_be_created_without_a_guardian()
    {
        $admin = $this->makeUser('admin', 'Test Admin No Parent');
        $email = 'test.learner.two.' . uniqid() . '@example.com';

        $this->from(route('admin.users.create'))->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Test Learner Two',
            'username' => 'test_learner_two',
            'email' => $email,
            'password' => 'password123',
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_admin_can_reassign_a_learners_parent_but_cannot_detach_them()
    {
        $admin = $this->makeUser('admin', 'Test Admin Reassign');
        $firstParent = $this->makeParent('Test Parent Two');
        $secondParent = $this->makeParent('Test Parent Three');

        $learnerUser = $this->makeUser('learner', 'Test Learner Three', ['username' => 'test_learner_three']);
        $learner = Learner::create([
            'user_id' => $learnerUser->id,
            'parent_id' => $firstParent->id,
        ]);

        $payload = [
            'name' => $learnerUser->name,
            'username' => 'test_learner_three',
            'email' => $learnerUser->email,
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
            'is_active' => 1,
        ];

        $this->actingAs($admin)
            ->put(route('admin.users.update', $learnerUser), $payload + ['parent_id' => $secondParent->id])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame($secondParent->id, $learner->fresh()->parent_id);

        // Every child keeps a reachable guardian (note 12).
        $this->from(route('admin.users.edit', $learnerUser))
            ->actingAs($admin)
            ->put(route('admin.users.update', $learnerUser), $payload + ['parent_id' => ''])
            ->assertSessionHasErrors('parent_id');

        $this->assertSame($secondParent->id, $learner->fresh()->parent_id);
    }

    public function test_parent_id_must_exist_in_the_parents_table()
    {
        $admin = $this->makeUser('admin', 'Test Admin Invalid');
        $email = 'test.learner.invalid.' . uniqid() . '@example.com';

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Test Learner Invalid',
            'username' => 'test_learner_invalid',
            'email' => $email,
            'password' => 'password123',
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
            'parent_id' => 999999,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_create_and_edit_screens_expose_the_parent_selector()
    {
        $admin = $this->makeUser('admin', 'Test Admin Screens');
        $parent = $this->makeParent('Test Parent Four');

        $learnerUser = $this->makeUser('learner', 'Test Learner Four');
        Learner::create(['user_id' => $learnerUser->id, 'parent_id' => $parent->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Parent / Guardian (learners only)');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $learnerUser))
            ->assertOk()
            ->assertSee('value="' . $parent->id . '" selected', false);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($parent->user->name);
    }
}
