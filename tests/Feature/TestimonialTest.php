<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Note 10 — testimonials go to the CEO for approval and only approved ones (with
 * the photo only when allowed) appear on the landing page.
 */
class TestimonialTest extends TestCase
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

    private function makeTestimonial(User $user, array $attributes = []): Testimonial
    {
        return Testimonial::create(array_merge([
            'user_id' => $user->id,
            'body' => 'A testimonial from ' . $user->name,
            'publish_photo' => false,
            'status' => Testimonial::STATUS_PENDING,
        ], $attributes));
    }

    public function test_a_member_submits_a_testimonial_for_approval(): void
    {
        $parent = $this->makeUser('parent', 'Testimonial Parent');

        $this->actingAs($parent)
            ->post(route('profile.testimonial.store'), [
                'body' => 'The tutors are patient and my child looks forward to every class.',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $testimonial = Testimonial::where('user_id', $parent->id)->firstOrFail();

        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->status);
        $this->assertFalse($testimonial->publish_photo);
        $this->assertNull($testimonial->approved_at);

        $this->actingAs($parent->fresh())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Waiting for the CEO');
    }

    public function test_publishing_the_photo_requires_a_profile_photo(): void
    {
        $parent = $this->makeUser('parent', 'No Photo Testimonial Parent');

        $this->actingAs($parent)
            ->from(route('profile.edit'))
            ->post(route('profile.testimonial.store'), [
                'body' => 'Great school.',
                'publish_photo' => '1',
            ])
            ->assertSessionHasErrors('publish_photo');

        $this->assertSame(0, Testimonial::where('user_id', $parent->id)->count());
    }

    public function test_editing_an_approved_testimonial_returns_it_to_review(): void
    {
        $ceo = $this->makeUser('ceo', 'Reviewing CEO');
        $parent = $this->makeUser('parent', 'Editing Testimonial Parent');

        $testimonial = $this->makeTestimonial($parent, [
            'status' => Testimonial::STATUS_APPROVED,
            'publish_photo' => true,
            'approved_by' => $ceo->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($parent)
            ->post(route('profile.testimonial.store'), [
                'body' => 'An updated testimonial.',
            ])
            ->assertSessionHas('success');

        $testimonial->refresh();

        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->status);
        $this->assertSame('An updated testimonial.', $testimonial->body);
        $this->assertNull($testimonial->approved_by);
        $this->assertNull($testimonial->approved_at);
        $this->assertFalse($testimonial->publish_photo);
    }

    public function test_administrators_and_the_ceo_can_review_testimonials(): void
    {
        $parent = $this->makeUser('parent', 'Reviewed Parent');
        $testimonial = $this->makeTestimonial($parent);

        // Members never reach the review screen.
        foreach (['tutor', 'parent', 'learner'] as $role) {
            $user = $this->makeUser($role, 'No Review ' . $role);

            $this->actingAs($user)->get(route('admin.testimonials.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.testimonials.approve', $testimonial))->assertForbidden();
        }

        $admin = $this->makeUser('admin', 'Review Admin');
        $this->actingAs($admin)->get(route('admin.testimonials.index'))->assertOk()->assertSee('Testimonials');

        $ceo = $this->makeUser('ceo', 'Review CEO');
        $this->actingAs($ceo)->get(route('admin.testimonials.index'))->assertOk()->assertSee('Testimonials');
    }

    public function test_the_ceo_approves_a_testimonial_and_it_appears_on_the_landing_page(): void
    {
        $ceo = $this->makeUser('ceo', 'Approving CEO');
        $parent = $this->makeUser('parent', 'Approved Parent');
        $pendingParent = $this->makeUser('parent', 'Pending Parent');

        $approved = $this->makeTestimonial($parent, [
            'body' => 'Approved story: the Saturday classes changed everything for us.',
        ]);
        $this->makeTestimonial($pendingParent, [
            'body' => 'Unreviewed story that must stay hidden.',
        ]);

        $this->actingAs($ceo)
            ->from(route('admin.testimonials.index'))
            ->post(route('admin.testimonials.approve', $approved))
            ->assertSessionHas('success');

        $approved->refresh();

        $this->assertSame(Testimonial::STATUS_APPROVED, $approved->status);
        $this->assertSame($ceo->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);

        Auth::logout();
        $this->flushSession();

        $this->get('/')
            ->assertOk()
            ->assertSee('Approved story: the Saturday classes changed everything for us.')
            ->assertSee('Approved Parent')
            ->assertDontSee('Unreviewed story that must stay hidden.');
    }

    public function test_the_photo_is_only_published_when_the_member_allowed_it(): void
    {
        Storage::fake('public');

        $ceo = $this->makeUser('ceo', 'Photo CEO');

        $withPhoto = $this->makeUser('parent', 'With Photo Parent', ['photo_path' => 'profile-photos/with.jpg']);
        $withoutPhoto = $this->makeUser('tutor', 'No Publish Tutor', ['photo_path' => 'profile-photos/without.jpg']);

        Storage::disk('public')->put('profile-photos/with.jpg', 'photo');
        Storage::disk('public')->put('profile-photos/without.jpg', 'photo');

        $this->makeTestimonial($withPhoto, [
            'body' => 'Photo allowed story.',
            'publish_photo' => true,
            'status' => Testimonial::STATUS_APPROVED,
            'approved_by' => $ceo->id,
            'approved_at' => now(),
        ]);

        $this->makeTestimonial($withoutPhoto, [
            'body' => 'Photo hidden story.',
            'publish_photo' => false,
            'status' => Testimonial::STATUS_APPROVED,
            'approved_by' => $ceo->id,
            'approved_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Photo allowed story.')
            ->assertSee(Storage::disk('public')->url('profile-photos/with.jpg'), false)
            ->assertSee('Photo hidden story.')
            ->assertDontSee(Storage::disk('public')->url('profile-photos/without.jpg'), false);
    }

    public function test_the_ceo_can_reject_with_a_reason_the_member_sees(): void
    {
        $ceo = $this->makeUser('ceo', 'Rejecting CEO');
        $parent = $this->makeUser('parent', 'Rejected Parent');

        $testimonial = $this->makeTestimonial($parent, [
            'body' => 'A testimonial that needs work.',
        ]);

        $this->actingAs($ceo)
            ->from(route('admin.testimonials.index'))
            ->post(route('admin.testimonials.reject', $testimonial), [
                'review_note' => 'Please mention your child by first name only.',
            ])
            ->assertSessionHas('success');

        $testimonial->refresh();

        $this->assertSame(Testimonial::STATUS_REJECTED, $testimonial->status);
        $this->assertSame('Please mention your child by first name only.', $testimonial->review_note);
        $this->assertSame($ceo->id, $testimonial->approved_by);

        Auth::logout();
        $this->flushSession();

        $this->get('/')->assertOk()->assertDontSee('A testimonial that needs work.');

        $this->actingAs($parent)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Rejected')
            ->assertSee('Please mention your child by first name only.');
    }

    public function test_a_member_can_send_a_testimonial_through_messages(): void
    {
        $admin = $this->makeUser('admin', 'Message Admin');
        $ceo = $this->makeUser('ceo', 'Message CEO');
        $tutor = $this->makeUser('tutor', 'Message Tutor');

        $this->actingAs($tutor)
            ->post(route('messages.store', $admin), [
                'body' => 'the lesson are very good and my student improved alot',
                'type' => 'testimonial',
            ])
            ->assertRedirect(route('messages.show', $admin));

        $testimonial = Testimonial::where('user_id', $tutor->id)->firstOrFail();

        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->status);
        $this->assertSame('the lesson are very good and my student improved alot', $testimonial->body);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $tutor->id,
            'receiver_id' => $admin->id,
            'type' => 'testimonial',
        ]);

        // The reviewers are told there is something to polish and approve.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'New testimonial to review',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $ceo->id,
            'title' => 'New testimonial to review',
        ]);
    }

    public function test_a_general_message_does_not_create_a_testimonial(): void
    {
        $admin = $this->makeUser('admin', 'Plain Message Admin');
        $parent = $this->makeUser('parent', 'Plain Message Parent');

        $this->actingAs($parent)
            ->post(route('messages.store', $admin), [
                'body' => 'Please can we move Saturday lesson?',
                'type' => 'general',
            ])
            ->assertRedirect(route('messages.show', $admin));

        $this->assertSame(0, Testimonial::where('user_id', $parent->id)->count());
        $this->assertDatabaseHas('messages', ['sender_id' => $parent->id, 'type' => 'general']);
    }

    public function test_the_school_cannot_file_a_testimonial_about_itself(): void
    {
        $admin = $this->makeUser('admin', 'Filing Admin');
        $parent = $this->makeUser('parent', 'Recipient Parent');

        $this->actingAs($admin)
            ->post(route('messages.store', $parent), [
                'body' => 'We think our school is great.',
                'type' => 'testimonial',
            ])
            ->assertRedirect(route('messages.show', $parent));

        $this->assertSame(0, Testimonial::where('user_id', $admin->id)->count());
        $this->assertDatabaseHas('messages', ['sender_id' => $admin->id, 'type' => 'general']);
    }

    public function test_a_reviewer_can_polish_the_wording_without_losing_the_approval(): void
    {
        $ceo = $this->makeUser('ceo', 'Polishing CEO');
        $admin = $this->makeUser('admin', 'Polishing Admin');
        $parent = $this->makeUser('parent', 'Polished Parent');

        $testimonial = $this->makeTestimonial($parent, [
            'body' => 'my child dey enjoy the class well well',
            'status' => Testimonial::STATUS_APPROVED,
            'approved_by' => $ceo->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.testimonials.index'))
            ->patch(route('admin.testimonials.update', $testimonial), [
                'body' => 'My child really enjoys the classes and looks forward to every one.',
            ])
            ->assertSessionHas('success');

        $testimonial->refresh();

        $this->assertSame('My child really enjoys the classes and looks forward to every one.', $testimonial->body);
        $this->assertSame(Testimonial::STATUS_APPROVED, $testimonial->status, 'Editing the wording must not un-publish it.');

        // Members cannot rewrite a testimonial.
        $this->actingAs($parent)
            ->patch(route('admin.testimonials.update', $testimonial), ['body' => 'Hacked wording.'])
            ->assertForbidden();
    }

    public function test_approved_testimonials_are_published_three_at_a_time(): void
    {
        $ceo = $this->makeUser('ceo', 'Slider CEO');

        foreach (range(1, 4) as $index) {
            $member = $this->makeUser('parent', 'Slider Parent ' . $index);
            $this->makeTestimonial($member, [
                'body' => 'Slider quote number ' . $index,
                'status' => Testimonial::STATUS_APPROVED,
                'approved_by' => $ceo->id,
                'approved_at' => now(),
            ]);
        }

        Auth::logout();
        $this->flushSession();

        $html = $this->get('/')->assertOk()
            ->assertSee('Slider quote number 1')
            ->assertSee('Slider quote number 4')
            ->getContent();

        // Four approved quotes make two slides of three-and-one.
        $this->assertSame(2, substr_count($html, 'data-t-slide='));
        $this->assertStringContainsString('data-t-prev', $html);
        $this->assertStringContainsString('data-t-next', $html);
    }
}
