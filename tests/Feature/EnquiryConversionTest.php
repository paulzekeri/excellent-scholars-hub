<?php

namespace Tests\Feature;

use App\Mail\AccountProvisionedMail;
use App\Models\AssessmentSession;
use App\Models\Classes;
use App\Models\Enquiry;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Note 13, phase 4 — converting an "Awaiting enrolment" enquiry into a parent,
 * a learner and a class enrolment, with note 12's provisioning rules.
 */
class EnquiryConversionTest extends TestCase
{
    use DatabaseTransactions;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    private function makeUser(string $roleSlug, string $name, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->roleId($roleSlug),
            'is_active' => true,
        ], $attributes));
    }

    /** @return array{0: User, 1: Tutor} */
    private function makeTutor(string $name): array
    {
        $user = $this->makeUser('tutor', $name);
        $tutor = Tutor::create(['user_id' => $user->id]);

        return [$user, $tutor];
    }

    private function makeClass(Tutor $tutor, string $name = 'JSS1 Maths', array $overrides = []): Classes
    {
        return Classes::create(array_merge([
            'name' => $name,
            'code' => 'CLS-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'subject' => 'Maths',
            'grade_level' => 'JSS1',
            'is_active' => true,
        ], $overrides));
    }

    /** A placed enquiry with a completed assessment that recommends the class. */
    private function makePlacedEnquiry(Tutor $tutor, Classes $class, array $overrides = []): Enquiry
    {
        $enquiry = Enquiry::create(array_merge([
            'child_name' => 'Converted Child',
            'child_grade_level' => 'JSS1',
            'guardian_name' => 'Converted Guardian',
            'guardian_phone' => '0803000000' . rand(10, 99),
            'guardian_email' => 'convert.' . uniqid() . '@example.com',
            'source' => Enquiry::SOURCE_WHATSAPP,
            'status' => Enquiry::STATUS_PLACED,
        ], $overrides));

        $enquiry->assessments()->create([
            'tutor_id' => $tutor->id,
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 30,
            'platform' => AssessmentSession::PLATFORM_GOOGLE_MEET,
            'meeting_link' => 'https://meet.google.com/convert-room',
            'status' => AssessmentSession::STATUS_COMPLETED,
            'outcome' => AssessmentSession::OUTCOME_READY,
            'recommended_class_id' => $class->id,
            'completed_at' => now()->subDay(),
        ]);

        return $enquiry;
    }

    public function test_conversion_creates_the_parent_learner_and_enrolment(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Conversion Admin');
        [, $tutor] = $this->makeTutor('Conversion Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Conversion');
        $enquiry = $this->makePlacedEnquiry($tutor, $class);

        $this->actingAs($admin)
            ->post(route('admin.enquiries.convert.store', $enquiry), [
                'child_username' => 'converted_child',
                'parent_username' => 'converted_guardian',
                'class_id' => $class->id,
            ])
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHas('success');

        $child = User::where('username', 'converted_child')->firstOrFail();
        $parent = User::where('username', 'converted_guardian')->firstOrFail();

        $this->assertSame('learner', $child->role->slug);
        $this->assertSame('parent', $parent->role->slug);
        $this->assertSame($enquiry->guardian_email, $parent->email);
        $this->assertTrue($child->must_change_password);
        $this->assertTrue($parent->must_change_password);

        $learner = Learner::where('user_id', $child->id)->firstOrFail();
        $this->assertSame($parent->parent->id, $learner->parent_id);
        $this->assertSame('JSS1', $learner->grade_level);
        $this->assertTrue($class->learners()->where('learner_id', $learner->id)->exists());

        $enquiry->refresh();
        $this->assertSame(Enquiry::STATUS_ENROLLED, $enquiry->status);
        $this->assertSame($learner->id, $enquiry->converted_learner_id);
        $this->assertNotNull($enquiry->converted_at);

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'body' => 'Converted to a learner (username converted_child) and enrolled in JSS1 Conversion.',
        ]);

        $this->assertCount(2, Mail::sent(AccountProvisionedMail::class));
        Mail::assertSent(AccountProvisionedMail::class, function (AccountProvisionedMail $mail) use ($enquiry) {
            return $mail->hasTo($enquiry->guardian_email);
        });
    }

    public function test_an_existing_parent_is_reused_instead_of_duplicated(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Existing Parent Admin');
        [, $tutor] = $this->makeTutor('Existing Parent Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Reuse');

        $parentUser = $this->makeUser('parent', 'Existing Guardian', ['username' => 'existing_guardian']);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);

        $enquiry = $this->makePlacedEnquiry($tutor, $class, [
            'child_name' => 'Reuse Child',
            'guardian_name' => 'Existing Guardian',
            'guardian_email' => $parentUser->email,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.enquiries.convert.store', $enquiry), [
                'child_username' => 'reuse_child',
                'class_id' => $class->id,
            ])
            ->assertSessionHas('success');

        $child = User::where('username', 'reuse_child')->firstOrFail();
        $learner = Learner::where('user_id', $child->id)->firstOrFail();

        $this->assertSame($parent->id, $learner->parent_id);
        $this->assertSame(1, User::where('email', $parentUser->email)->count());
        $this->assertCount(1, Mail::sent(AccountProvisionedMail::class));
    }

    public function test_conversion_is_idempotent(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Idempotent Admin');
        [, $tutor] = $this->makeTutor('Idempotent Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Once');
        $enquiry = $this->makePlacedEnquiry($tutor, $class);

        $payload = [
            'child_username' => 'only_once_child',
            'parent_username' => 'only_once_guardian',
            'class_id' => $class->id,
        ];

        $this->actingAs($admin)->post(route('admin.enquiries.convert.store', $enquiry), $payload)
            ->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.enquiries.convert.store', $enquiry), $payload)
            ->assertSessionHas('error');

        $this->assertSame(1, User::where('username', 'only_once_child')->count());
        $this->assertSame(1, User::where('username', 'only_once_guardian')->count());
        $this->assertSame(1, Learner::where('user_id', User::where('username', 'only_once_child')->firstOrFail()->id)->count());
    }

    public function test_a_non_placed_enquiry_cannot_be_converted(): void
    {
        $admin = $this->makeUser('admin', 'Blocked Admin');
        [, $tutor] = $this->makeTutor('Blocked Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Blocked');
        $enquiry = $this->makePlacedEnquiry($tutor, $class, ['status' => Enquiry::STATUS_ASSESSMENT_BOOKED]);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->get(route('admin.enquiries.convert', $enquiry))
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('admin.enquiries.convert.store', $enquiry), [
                'child_username' => 'blocked_child',
                'parent_username' => 'blocked_guardian',
                'class_id' => $class->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['username' => 'blocked_child']);
        $this->assertSame(Enquiry::STATUS_ASSESSMENT_BOOKED, $enquiry->fresh()->status);
    }

    public function test_a_duplicate_child_username_is_refused(): void
    {
        $admin = $this->makeUser('admin', 'Duplicate Conversion Admin');
        [, $tutor] = $this->makeTutor('Duplicate Conversion Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Duplicate');
        $enquiry = $this->makePlacedEnquiry($tutor, $class);

        $this->makeUser('learner', 'Taken Handle', ['username' => 'taken_convert_name']);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.convert', $enquiry))
            ->post(route('admin.enquiries.convert.store', $enquiry), [
                'child_username' => 'taken_convert_name',
                'parent_username' => 'fresh_guardian_name',
                'class_id' => $class->id,
            ])
            ->assertSessionHasErrors('child_username');

        $this->assertNull($enquiry->fresh()->converted_learner_id);
    }

    public function test_a_staff_email_cannot_be_used_as_the_guardian(): void
    {
        $admin = $this->makeUser('admin', 'Staff Email Admin');
        [, $tutor] = $this->makeTutor('Staff Email Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Staff Email');
        $enquiry = $this->makePlacedEnquiry($tutor, $class, ['guardian_email' => $admin->email]);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.convert', $enquiry))
            ->post(route('admin.enquiries.convert.store', $enquiry), [
                'child_username' => 'staff_email_child',
                'parent_username' => 'staff_email_guardian',
                'class_id' => $class->id,
            ])
            ->assertSessionHasErrors('guardian_email');

        $this->assertDatabaseMissing('users', ['username' => 'staff_email_child']);
    }

    public function test_the_class_capacity_is_enforced(): void
    {
        $admin = $this->makeUser('admin', 'Capacity Admin');
        [, $tutor] = $this->makeTutor('Capacity Tutor');
        $class = $this->makeClass($tutor, 'Full Class', ['max_students' => 1]);

        $sittingUser = $this->makeUser('learner', 'Sitting Learner');
        $sitting = Learner::create(['user_id' => $sittingUser->id]);
        $class->learners()->attach($sitting->id);

        $enquiry = $this->makePlacedEnquiry($tutor, $class);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.convert', $enquiry))
            ->post(route('admin.enquiries.convert.store', $enquiry), [
                'child_username' => 'capacity_child',
                'parent_username' => 'capacity_guardian',
                'class_id' => $class->id,
            ])
            ->assertSessionHasErrors('class_id');

        $this->assertDatabaseMissing('users', ['username' => 'capacity_child']);
    }

    public function test_credentials_are_flashed_when_email_cannot_deliver(): void
    {
        $admin = $this->makeUser('admin', 'Flash Conversion Admin');
        [, $tutor] = $this->makeTutor('Flash Conversion Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Flash');
        $enquiry = $this->makePlacedEnquiry($tutor, $class);

        // phpunit runs the array mailer, like UAT's log driver.
        $this->actingAs($admin)
            ->post(route('admin.enquiries.convert.store', $enquiry), [
                'child_username' => 'flash_child',
                'parent_username' => 'flash_guardian',
                'class_id' => $class->id,
            ])
            ->assertSessionHas('conversion_credentials');

        $credentials = session('conversion_credentials');

        $this->assertCount(2, $credentials);

        $names = array_column($credentials, 'name');
        $this->assertContains('Converted Child', $names);
        $this->assertContains('Converted Guardian', $names);

        foreach ($credentials as $account) {
            $this->assertNotEmpty($account['username']);
            $this->assertNotEmpty($account['password']);
        }
    }

    public function test_only_school_administration_can_convert(): void
    {
        [, $tutor] = $this->makeTutor('Access Conversion Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Access');
        $enquiry = $this->makePlacedEnquiry($tutor, $class);

        foreach (['tutor', 'parent', 'learner'] as $role) {
            $user = $this->makeUser($role, 'No Convert ' . $role);

            $this->actingAs($user)->get(route('admin.enquiries.convert', $enquiry))->assertForbidden();
            $this->actingAs($user)->post(route('admin.enquiries.convert.store', $enquiry), [])->assertForbidden();
        }
    }

    public function test_the_conversion_page_renders_and_blocks_when_not_ready(): void
    {
        $admin = $this->makeUser('admin', 'Render Conversion Admin');
        [, $tutor] = $this->makeTutor('Render Conversion Tutor');
        $class = $this->makeClass($tutor, 'JSS1 Render');
        $enquiry = $this->makePlacedEnquiry($tutor, $class);

        $this->actingAs($admin)
            ->get(route('admin.enquiries.convert', $enquiry))
            ->assertOk()
            ->assertSee('Create accounts and enrol')
            ->assertSee($enquiry->child_name);

        $notReady = $this->makePlacedEnquiry($tutor, $class, [
            'child_name' => 'Not Ready Child',
            'status' => Enquiry::STATUS_NEW,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enquiries.convert', $notReady))
            ->assertRedirect(route('admin.enquiries.show', $notReady))
            ->assertSessionHas('error');
    }
}
