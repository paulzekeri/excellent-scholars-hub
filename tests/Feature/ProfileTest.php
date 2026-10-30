<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Note 10 — members edit their own name, email and profile photo.
 */
class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => true,
        ], $attributes));
    }

    public function test_a_guest_is_sent_to_login_for_the_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_a_member_updates_their_name_email_and_photo(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('parent', 'Profile Parent');
        $newEmail = 'profile.' . uniqid() . '@example.com';

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Renamed Parent',
                'email' => $newEmail,
                'photo' => UploadedFile::fake()->create('avatar.jpg', 200, 'image/jpeg'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Renamed Parent', $user->name);
        $this->assertSame($newEmail, $user->email);
        $this->assertNotNull($user->photo_path);
        Storage::disk('public')->assertExists($user->photo_path);
    }

    public function test_replacing_a_photo_removes_the_old_file(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('tutor', 'Photo Tutor', ['photo_path' => 'profile-photos/old.jpg']);
        Storage::disk('public')->put('profile-photos/old.jpg', 'old');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'photo' => UploadedFile::fake()->create('new.png', 200, 'image/png'),
            ])
            ->assertSessionHas('success');

        $user->refresh();

        Storage::disk('public')->assertMissing('profile-photos/old.jpg');
        Storage::disk('public')->assertExists($user->photo_path);
    }

    public function test_a_member_can_remove_their_photo(): void
    {
        Storage::fake('public');

        $user = $this->makeUser('parent', 'Remove Photo Parent', ['photo_path' => 'profile-photos/remove.jpg']);
        Storage::disk('public')->put('profile-photos/remove.jpg', 'photo');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'remove_photo' => '1',
            ])
            ->assertSessionHas('success');

        $this->assertNull($user->fresh()->photo_path);
        Storage::disk('public')->assertMissing('profile-photos/remove.jpg');
    }

    public function test_the_email_must_stay_unique(): void
    {
        $user = $this->makeUser('parent', 'Unique Email Parent');
        $other = $this->makeUser('tutor', 'Unique Email Tutor');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $other->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_a_learner_may_keep_no_email(): void
    {
        $learner = $this->makeUser('learner', 'No Email Learner', ['email' => null]);

        $this->actingAs($learner)
            ->patch(route('profile.update'), [
                'name' => 'No Email Learner',
                'email' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($learner->fresh()->email);
    }

    public function test_the_profile_page_renders_for_every_role(): void
    {
        foreach (['admin', 'ceo', 'tutor', 'parent', 'learner'] as $role) {
            $user = $this->makeUser($role, 'Profile ' . ucfirst($role));

            $this->actingAs($user)
                ->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('My profile')
                ->assertSee('Your testimonial');
        }
    }
}
