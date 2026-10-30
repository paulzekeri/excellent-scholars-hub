<?php

namespace Tests\Feature;

use App\Mail\AssessmentScheduledMail;
use App\Models\AssessmentSession;
use App\Models\Enquiry;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Note 13, phase 2 — booking the video-call assessment.
 *
 * The parent can request one from the landing page without an account, the
 * school sets up the Meet/Zoom link and invites the tutor, and all three join
 * from the same link (the guardian receives it by email/WhatsApp).
 */
class AssessmentBookingTest extends TestCase
{
    use DatabaseTransactions;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->roleId($roleSlug),
            'is_active' => true,
        ]);
    }

    /** @return array{0: User, 1: Tutor} */
    private function makeTutor(string $name): array
    {
        $user = $this->makeUser('tutor', $name);
        $tutor = Tutor::create(['user_id' => $user->id]);

        return [$user, $tutor];
    }

    private function makeEnquiry(array $overrides = []): Enquiry
    {
        return Enquiry::create(array_merge([
            'child_name' => 'Ada Prospect',
            'guardian_name' => 'Mrs Prospect',
            'guardian_phone' => '0803000000' . rand(10, 99),
            'guardian_email' => 'guardian.' . uniqid() . '@example.com',
            'source' => Enquiry::SOURCE_WHATSAPP,
            'status' => Enquiry::STATUS_NEW,
        ], $overrides));
    }

    private function bookingPayload(Tutor $tutor, array $overrides = []): array
    {
        return array_merge([
            'tutor_id' => $tutor->id,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
            'platform' => AssessmentSession::PLATFORM_GOOGLE_MEET,
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            'guardian_email' => 'booking.guardian.' . uniqid() . '@example.com',
            'notes' => 'Year 5, needs maths support.',
        ], $overrides);
    }

    public function test_the_landing_page_offers_a_public_assessment_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Book a video-call assessment')
            ->assertSee('Request an assessment')
            ->assertSee(route('assessments.request'), false);
    }

    public function test_a_guest_can_request_an_assessment_without_an_account(): void
    {
        $admin = $this->makeUser('admin', 'Admissions Admin');
        $email = 'public.' . uniqid() . '@example.com';

        $this->post(route('assessments.request'), [
            'child_name' => 'Public Child',
            'guardian_name' => 'Public Guardian',
            'guardian_phone' => '0803 555 0001',
            'guardian_email' => $email,
            'preferred_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'notes' => 'Prefers weekends',
        ])->assertRedirect('/#book-assessment')->assertSessionHas('success');

        $enquiry = Enquiry::where('child_name', 'Public Child')->firstOrFail();

        $this->assertSame(Enquiry::SOURCE_WEBSITE, $enquiry->source);
        $this->assertSame(Enquiry::STATUS_NEW, $enquiry->status);
        $this->assertSame($email, $enquiry->guardian_email);
        $this->assertNotNull($enquiry->preferred_at);
        $this->assertSame(1, $enquiry->timeline()->count());
        $this->assertStringContainsString('public website', $enquiry->timeline()->first()->body);

        // The school is told, and no account/learner record is created.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => 'New assessment request',
        ]);
        $this->assertDatabaseMissing('users', ['name' => 'Public Child']);
    }

    public function test_a_public_request_needs_the_child_guardian_and_contact_details(): void
    {
        $before = Enquiry::count();

        $this->from('/')->post(route('assessments.request'), [])
            ->assertSessionHasErrors(['child_name', 'guardian_name', 'guardian_phone', 'guardian_email']);

        $this->assertSame($before, Enquiry::count());
    }

    public function test_the_honeypot_blocks_bots_without_creating_an_enquiry(): void
    {
        $this->post(route('assessments.request'), [
            'child_name' => 'Bot Child',
            'guardian_name' => 'Bot Guardian',
            'guardian_phone' => '08030000000',
            'guardian_email' => 'bot@example.com',
            'website' => 'http://spam.example.com',
        ])->assertRedirect('/#book-assessment');

        $this->assertDatabaseMissing('enquiries', ['child_name' => 'Bot Child']);
    }

    public function test_an_admin_books_the_call_and_emails_the_link_to_the_guardian(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Booking Admin');
        [$tutorUser, $tutor] = $this->makeTutor('Assessing Tutor');
        $enquiry = $this->makeEnquiry(['status' => Enquiry::STATUS_CONTACTED]);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.assessments.store', $enquiry), $this->bookingPayload($tutor, [
                'guardian_email' => $enquiry->guardian_email,
            ]))
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHas('success');

        $assessment = AssessmentSession::where('enquiry_id', $enquiry->id)->firstOrFail();

        $this->assertSame($tutor->id, $assessment->tutor_id);
        $this->assertSame(AssessmentSession::STATUS_SCHEDULED, $assessment->status);
        $this->assertSame(Enquiry::STATUS_ASSESSMENT_BOOKED, $enquiry->fresh()->status);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tutorUser->id,
            'title' => 'New assessment booked',
        ]);

        Mail::assertSent(AssessmentScheduledMail::class, function (AssessmentScheduledMail $mail) use ($enquiry) {
            return $mail->hasTo($enquiry->guardian_email)
                && $mail->meetingLink === 'https://meet.google.com/abc-defg-hij';
        });

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'type' => 'status',
        ]);

        // The child is still only an enquiry.
        $this->assertDatabaseMissing('users', ['name' => $enquiry->child_name]);
    }

    public function test_an_assessment_cannot_be_booked_before_the_enquiry_is_contacted(): void
    {
        $admin = $this->makeUser('admin', 'Guard Admin');
        [, $tutor] = $this->makeTutor('Guard Tutor');
        $enquiry = $this->makeEnquiry(); // still new

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.assessments.store', $enquiry), $this->bookingPayload($tutor))
            ->assertSessionHas('error');

        $this->assertSame(0, $enquiry->assessments()->count());
        $this->assertSame(Enquiry::STATUS_NEW, $enquiry->fresh()->status);
    }

    public function test_booking_validates_the_time_and_the_online_link(): void
    {
        $admin = $this->makeUser('admin', 'Validation Booking Admin');
        [, $tutor] = $this->makeTutor('Validation Tutor');
        $enquiry = $this->makeEnquiry(['status' => Enquiry::STATUS_CONTACTED]);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.assessments.store', $enquiry), $this->bookingPayload($tutor, [
                'scheduled_at' => now()->subDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('scheduled_at');

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.assessments.store', $enquiry), $this->bookingPayload($tutor, [
                'meeting_link' => '',
            ]))
            ->assertSessionHasErrors('meeting_link');

        $this->assertSame(0, $enquiry->assessments()->count());
    }

    public function test_a_phone_assessment_does_not_need_a_meeting_link(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', 'Phone Booking Admin');
        [, $tutor] = $this->makeTutor('Phone Tutor');
        $enquiry = $this->makeEnquiry(['status' => Enquiry::STATUS_CONTACTED]);

        $this->actingAs($admin)
            ->post(route('admin.enquiries.assessments.store', $enquiry), $this->bookingPayload($tutor, [
                'platform' => AssessmentSession::PLATFORM_PHONE,
                'meeting_link' => '',
            ]))
            ->assertSessionHas('success');

        $assessment = $enquiry->assessments()->firstOrFail();

        $this->assertSame(AssessmentSession::PLATFORM_PHONE, $assessment->platform);
        $this->assertNull($assessment->meeting_link);
        $this->assertNull($assessment->joinUrl());
    }

    private function book(Enquiry $enquiry, Tutor $tutor, array $overrides = []): AssessmentSession
    {
        return $enquiry->assessments()->create(array_merge([
            'tutor_id' => $tutor->id,
            'scheduled_at' => now()->addDay()->setTime(16, 0),
            'duration_minutes' => 30,
            'platform' => AssessmentSession::PLATFORM_GOOGLE_MEET,
            'meeting_link' => 'https://meet.google.com/room-' . uniqid(),
            'status' => AssessmentSession::STATUS_SCHEDULED,
        ], $overrides));
    }

    public function test_a_tutor_only_sees_their_own_assessments_with_the_join_link(): void
    {
        [$alphaUser, $alpha] = $this->makeTutor('Alpha Tutor');
        [, $beta] = $this->makeTutor('Beta Tutor');

        $alphaEnquiry = $this->makeEnquiry(['child_name' => 'Alpha Child', 'status' => Enquiry::STATUS_ASSESSMENT_BOOKED]);
        $betaEnquiry = $this->makeEnquiry(['child_name' => 'Beta Child', 'status' => Enquiry::STATUS_ASSESSMENT_BOOKED]);

        $this->book($alphaEnquiry, $alpha, ['meeting_link' => 'https://meet.google.com/alpha-room']);
        $this->book($betaEnquiry, $beta, ['meeting_link' => 'https://meet.google.com/beta-room']);

        $this->actingAs($alphaUser)
            ->get(route('tutor.assessments.index'))
            ->assertOk()
            ->assertSee('Alpha Child')
            ->assertDontSee('Beta Child')
            ->assertSee('https://meet.google.com/alpha-room');
    }

    public function test_the_school_sees_every_assessment_and_can_join(): void
    {
        $admin = $this->makeUser('admin', 'Assessment Viewer');
        [, $alpha] = $this->makeTutor('Viewer Tutor One');
        [, $beta] = $this->makeTutor('Viewer Tutor Two');

        $first = $this->makeEnquiry(['child_name' => 'First Child', 'status' => Enquiry::STATUS_ASSESSMENT_BOOKED]);
        $second = $this->makeEnquiry(['child_name' => 'Second Child', 'status' => Enquiry::STATUS_ASSESSMENT_BOOKED]);

        $this->book($first, $alpha, ['meeting_link' => 'https://meet.google.com/first-room']);
        $this->book($second, $beta, ['meeting_link' => 'https://meet.google.com/second-room']);

        $this->actingAs($admin)
            ->get(route('admin.assessments.index'))
            ->assertOk()
            ->assertSee('First Child')
            ->assertSee('Second Child')
            ->assertSee('https://meet.google.com/first-room');
    }

    public function test_cancelling_an_assessment_returns_the_enquiry_to_contacted(): void
    {
        $admin = $this->makeUser('admin', 'Cancel Admin');
        [, $tutor] = $this->makeTutor('Cancel Tutor');
        $enquiry = $this->makeEnquiry(['status' => Enquiry::STATUS_ASSESSMENT_BOOKED]);
        $assessment = $this->book($enquiry, $tutor);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.assessments.cancel', $assessment))
            ->assertSessionHas('success');

        $this->assertSame(AssessmentSession::STATUS_CANCELLED, $assessment->fresh()->status);
        $this->assertSame(Enquiry::STATUS_CONTACTED, $enquiry->fresh()->status);
        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'body' => 'Assessment cancelled.',
        ]);
    }

    public function test_todays_assessments_appear_on_the_school_and_tutor_dashboards(): void
    {
        $admin = $this->makeUser('admin', 'Dashboard Booking Admin');
        [$tutorUser, $tutor] = $this->makeTutor('Dashboard Assessor');

        $enquiry = $this->makeEnquiry([
            'child_name' => 'Today Child',
            'status' => Enquiry::STATUS_ASSESSMENT_BOOKED,
        ]);

        $this->book($enquiry, $tutor, [
            'scheduled_at' => now()->setTime(23, 0),
            'meeting_link' => 'https://meet.google.com/today-room',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Today Child')
            ->assertSee('https://meet.google.com/today-room');

        $this->actingAs($tutorUser)
            ->get(route('tutor.dashboard'))
            ->assertOk()
            ->assertSee('Today Child')
            ->assertSee('Join call');
    }

    public function test_the_enquiry_page_offers_booking_only_after_it_is_contacted(): void
    {
        $admin = $this->makeUser('admin', 'Enquiry Booking Admin');
        $this->makeTutor('Enquiry Booking Tutor');

        $newEnquiry = $this->makeEnquiry(['child_name' => 'Fresh Lead']);
        $contactedEnquiry = $this->makeEnquiry(['child_name' => 'Contacted Lead', 'status' => Enquiry::STATUS_CONTACTED]);

        $this->actingAs($admin)->get(route('admin.enquiries.show', $newEnquiry))
            ->assertOk()
            ->assertSee('Mark the enquiry as')
            ->assertDontSee('Book assessment and email the link');

        $this->actingAs($admin)->get(route('admin.enquiries.show', $contactedEnquiry))
            ->assertOk()
            ->assertSee('Book assessment and email the link');
    }
}
