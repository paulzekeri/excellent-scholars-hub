<?php

namespace Tests\Feature;

use App\Models\Affirmation;
use App\Models\Learner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Note 8 — the school writes the weekly affirmation and every dashboard shows it.
 */
class AffirmationTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    private function makeLearner(string $name): User
    {
        $user = $this->makeUser('learner', $name);
        Learner::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_an_administrator_publishes_an_affirmation_and_learners_see_it(): void
    {
        $admin = $this->makeUser('admin', 'Affirmation Admin');
        $learner = $this->makeLearner('Affirmation Learner');

        $this->actingAs($admin)
            ->post(route('admin.affirmations.store'), [
                'body' => 'Every page you read this week makes next week easier.',
            ])
            ->assertRedirect(route('admin.affirmations.index'))
            ->assertSessionHas('success');

        $affirmation = Affirmation::firstOrFail();

        $this->assertTrue($affirmation->is_active);
        $this->assertNotNull($affirmation->published_at);
        $this->assertSame($admin->id, $affirmation->author_id);

        $this->actingAs($learner)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Every page you read this week makes next week easier.')
            ->assertSee('Affirmation of the week')
            ->assertSee('Affirmation Admin');
    }

    public function test_the_newest_active_post_is_the_one_on_the_dashboards(): void
    {
        $admin = $this->makeUser('admin', 'Newest Admin');
        $learner = $this->makeLearner('Newest Learner');

        Affirmation::create([
            'body' => 'Older affirmation text.',
            'author_id' => $admin->id,
            'is_active' => true,
            'published_at' => now()->subWeek(),
        ]);

        $this->actingAs($admin)->post(route('admin.affirmations.store'), [
            'body' => 'Newest affirmation text.',
        ])->assertSessionHas('success');

        $this->actingAs($learner)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Newest affirmation text.')
            ->assertDontSee('Older affirmation text.');
    }

    public function test_switching_an_affirmation_off_restores_the_rotation(): void
    {
        $admin = $this->makeUser('admin', 'Toggle Admin');
        $learner = $this->makeLearner('Toggle Learner');

        $affirmation = Affirmation::create([
            'body' => 'Switch me off.',
            'author_id' => $admin->id,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.affirmations.index'))
            ->post(route('admin.affirmations.toggle', $affirmation))
            ->assertSessionHas('success');

        $this->assertFalse($affirmation->fresh()->is_active);

        $this->actingAs($learner)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertDontSee('Switch me off.')
            ->assertSee('Affirmation of the day');
    }

    public function test_only_school_administration_can_manage_affirmations(): void
    {
        $affirmation = Affirmation::create([
            'body' => 'Managed by the school.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        foreach (['tutor', 'parent', 'learner'] as $role) {
            $user = $this->makeUser($role, 'No Affirmation ' . $role);

            $this->actingAs($user)->get(route('admin.affirmations.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.affirmations.store'), ['body' => 'Nope'])->assertForbidden();
            $this->actingAs($user)->post(route('admin.affirmations.toggle', $affirmation))->assertForbidden();
        }

        $ceo = $this->makeUser('ceo', 'Affirmation CEO');
        $this->actingAs($ceo)->get(route('admin.affirmations.index'))->assertOk()->assertSee('Affirmations');
    }

    public function test_the_message_is_required_and_limited_to_five_hundred_characters(): void
    {
        $admin = $this->makeUser('admin', 'Validation Affirmation Admin');

        $this->actingAs($admin)
            ->from(route('admin.affirmations.index'))
            ->post(route('admin.affirmations.store'), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($admin)
            ->from(route('admin.affirmations.index'))
            ->post(route('admin.affirmations.store'), ['body' => str_repeat('a', 501)])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, Affirmation::count());
    }

    public function test_the_school_can_delete_an_affirmation(): void
    {
        $admin = $this->makeUser('admin', 'Delete Affirmation Admin');
        $affirmation = Affirmation::create([
            'body' => 'Delete this post.',
            'author_id' => $admin->id,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.affirmations.index'))
            ->delete(route('admin.affirmations.destroy', $affirmation))
            ->assertSessionHas('success');

        $this->assertNull(Affirmation::find($affirmation->id));
    }

    public function test_the_management_screen_renders_the_current_post_and_history(): void
    {
        $admin = $this->makeUser('admin', 'Screen Affirmation Admin');
        Affirmation::create([
            'body' => 'Screen affirmation body.',
            'author_id' => $admin->id,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.affirmations.index'))
            ->assertOk()
            ->assertSee('Publish affirmation')
            ->assertSee('Screen affirmation body.')
            ->assertSee('Showing on every dashboard now');
    }
}
