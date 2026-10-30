<?php

namespace Tests\Feature;

use App\Models\AssessmentSession;
use App\Models\Classes;
use App\Models\Enquiry;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Note 13, phase 3 — recording the assessment result and moving the pipeline:
 * completed → Assessed (or Awaiting enrolment when a class is recommended),
 * no-show → back to Contacted for rebooking.
 */
class AssessmentResultTest extends TestCase
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
            'status' => Enquiry::STATUS_ASSESSMENT_BOOKED,
        ], $overrides));
    }

    private function book(Enquiry $enquiry, Tutor $tutor, array $overrides = []): AssessmentSession
    {
        return $enquiry->assessments()->create(array_merge([
            'tutor_id' => $tutor->id,
            'scheduled_at' => now()->subHour(),
            'duration_minutes' => 30,
            'platform' => AssessmentSession::PLATFORM_GOOGLE_MEET,
            'meeting_link' => 'https://meet.google.com/room-' . uniqid(),
            'status' => AssessmentSession::STATUS_SCHEDULED,
        ], $overrides));
    }

    private function makeClass(Tutor $tutor, string $name = 'JSS1 Maths'): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'CLS-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'subject' => 'Maths',
            'grade_level' => 'JSS1',
            'is_active' => true,
        ]);
    }

    public function test_a_tutor_records_a_completed_result_and_the_enquiry_is_assessed(): void
    {
        [$tutorUser, $tutor] = $this->makeTutor('Result Tutor');
        $enquiry = $this->makeEnquiry();
        $assessment = $this->book($enquiry, $tutor);

        $this->actingAs($tutorUser)
            ->post(route('tutor.assessments.result', $assessment), [
                'result' => AssessmentSession::STATUS_COMPLETED,
                'outcome' => AssessmentSession::OUTCOME_READY,
                'strengths' => 'Strong reader, confident speaker.',
                'focus_areas' => 'Fractions and word problems.',
                'notes' => 'Recommend the JSS1 group.',
            ])
            ->assertRedirect(route('tutor.assessments.show', $assessment))
            ->assertSessionHas('success');

        $assessment->refresh();
        $enquiry->refresh();

        $this->assertSame(AssessmentSession::STATUS_COMPLETED, $assessment->status);
        $this->assertSame(AssessmentSession::OUTCOME_READY, $assessment->outcome);
        $this->assertSame($tutorUser->id, $assessment->conducted_by);
        $this->assertNotNull($assessment->completed_at);
        $this->assertSame(Enquiry::STATUS_ASSESSED, $enquiry->status);

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'body' => 'Assessment completed — Ready for the recommended class.',
        ]);
    }

    public function test_recommending_a_class_places_the_enquiry_in_awaiting_enrolment(): void
    {
        [$tutorUser, $tutor] = $this->makeTutor('Placement Tutor');
        $enquiry = $this->makeEnquiry();
        $assessment = $this->book($enquiry, $tutor);
        $class = $this->makeClass($tutor, 'JSS1 Maths Group A');

        $this->actingAs($tutorUser)
            ->post(route('tutor.assessments.result', $assessment), [
                'result' => AssessmentSession::STATUS_COMPLETED,
                'outcome' => AssessmentSession::OUTCOME_NEEDS_SUPPORT,
                'recommended_class_id' => $class->id,
                'recommended_level' => 'Year 5, needs phonics support',
            ])
            ->assertSessionHas('success');

        $assessment->refresh();
        $enquiry->refresh();

        $this->assertSame($class->id, $assessment->recommended_class_id);
        $this->assertSame(Enquiry::STATUS_PLACED, $enquiry->status);

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'body' => 'Assessment completed — Needs additional support; recommended JSS1 Maths Group A.',
        ]);
    }

    public function test_a_completed_result_requires_an_outcome(): void
    {
        [$tutorUser, $tutor] = $this->makeTutor('Outcome Tutor');
        $assessment = $this->book($this->makeEnquiry(), $tutor);

        $this->actingAs($tutorUser)
            ->from(route('tutor.assessments.show', $assessment))
            ->post(route('tutor.assessments.result', $assessment), [
                'result' => AssessmentSession::STATUS_COMPLETED,
            ])
            ->assertSessionHasErrors('outcome');

        $this->assertSame(AssessmentSession::STATUS_SCHEDULED, $assessment->fresh()->status);
    }

    public function test_a_no_show_returns_the_enquiry_to_contacted_for_rebooking(): void
    {
        [$tutorUser, $tutor] = $this->makeTutor('No Show Tutor');
        $enquiry = $this->makeEnquiry();
        $assessment = $this->book($enquiry, $tutor);

        $this->actingAs($tutorUser)
            ->post(route('tutor.assessments.result', $assessment), [
                'result' => AssessmentSession::STATUS_NO_SHOW,
            ])
            ->assertSessionHas('success');

        $assessment->refresh();
        $enquiry->refresh();

        $this->assertSame(AssessmentSession::STATUS_NO_SHOW, $assessment->status);
        $this->assertNull($assessment->outcome);
        $this->assertSame(Enquiry::STATUS_CONTACTED, $enquiry->status);

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'body' => 'Assessment no-show — the enquiry is back in Contacted for rebooking.',
        ]);
    }

    public function test_the_school_can_revisit_a_result_and_place_the_child(): void
    {
        $admin = $this->makeUser('admin', 'Result Admin');
        [$tutorUser, $tutor] = $this->makeTutor('School Side Tutor');
        $enquiry = $this->makeEnquiry();
        $assessment = $this->book($enquiry, $tutor);

        $this->actingAs($tutorUser)->post(route('tutor.assessments.result', $assessment), [
            'result' => AssessmentSession::STATUS_COMPLETED,
            'outcome' => AssessmentSession::OUTCOME_READY,
        ])->assertSessionHas('success');

        $this->assertSame(Enquiry::STATUS_ASSESSED, $enquiry->fresh()->status);

        $class = $this->makeClass($tutor, 'JSS1 English');

        $this->actingAs($admin)->post(route('admin.assessments.result', $assessment), [
            'result' => AssessmentSession::STATUS_COMPLETED,
            'outcome' => AssessmentSession::OUTCOME_READY,
            'recommended_class_id' => $class->id,
        ])->assertSessionHas('success');

        $this->assertSame(Enquiry::STATUS_PLACED, $enquiry->fresh()->status);
        $this->assertSame($class->id, $assessment->fresh()->recommended_class_id);

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'body' => 'Assessment completed — Ready for the recommended class; recommended JSS1 English.',
        ]);
    }

    public function test_a_report_can_be_uploaded_and_downloaded_by_the_school_and_the_tutor(): void
    {
        Storage::fake('public');

        $admin = $this->makeUser('admin', 'Report Admin');
        [$tutorUser, $tutor] = $this->makeTutor('Report Tutor');
        $enquiry = $this->makeEnquiry();
        $assessment = $this->book($enquiry, $tutor);

        $this->actingAs($tutorUser)->post(route('tutor.assessments.result', $assessment), [
            'result' => AssessmentSession::STATUS_COMPLETED,
            'outcome' => AssessmentSession::OUTCOME_READY,
            'report' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])->assertSessionHas('success');

        $path = $assessment->fresh()->attachment_path;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)
            ->get(route('admin.assessments.report', $assessment))
            ->assertDownload('assessment-' . $enquiry->reference . '.pdf');

        $this->actingAs($tutorUser)
            ->get(route('tutor.assessments.report', $assessment))
            ->assertDownload('assessment-' . $enquiry->reference . '.pdf');
    }

    public function test_a_report_must_be_a_pdf_or_image(): void
    {
        $admin = $this->makeUser('admin', 'Bad Report Admin');
        [, $tutor] = $this->makeTutor('Bad Report Tutor');
        $assessment = $this->book($this->makeEnquiry(), $tutor);

        $this->actingAs($admin)
            ->from(route('admin.assessments.show', $assessment))
            ->post(route('admin.assessments.result', $assessment), [
                'result' => AssessmentSession::STATUS_COMPLETED,
                'outcome' => AssessmentSession::OUTCOME_READY,
                'report' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('report');

        $this->assertSame(AssessmentSession::STATUS_SCHEDULED, $assessment->fresh()->status);
    }

    public function test_a_tutor_cannot_open_or_record_another_tutors_assessment(): void
    {
        [, $owner] = $this->makeTutor('Owner Tutor');
        [$otherUser] = $this->makeTutor('Other Tutor');
        $assessment = $this->book($this->makeEnquiry(), $owner);

        $this->actingAs($otherUser)
            ->get(route('tutor.assessments.show', $assessment))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('tutor.assessments.result', $assessment), [
                'result' => AssessmentSession::STATUS_COMPLETED,
                'outcome' => AssessmentSession::OUTCOME_READY,
            ])
            ->assertForbidden();

        $this->assertSame(AssessmentSession::STATUS_SCHEDULED, $assessment->fresh()->status);
    }

    public function test_the_pipeline_guards_require_a_recorded_result(): void
    {
        $admin = $this->makeUser('admin', 'Guard Result Admin');
        [, $tutor] = $this->makeTutor('Guard Result Tutor');
        $enquiry = $this->makeEnquiry();
        $assessment = $this->book($enquiry, $tutor);

        // No result yet: Assessed is refused.
        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), ['status' => Enquiry::STATUS_ASSESSED])
            ->assertSessionHas('error');

        $this->assertSame(Enquiry::STATUS_ASSESSMENT_BOOKED, $enquiry->fresh()->status);

        // Record the result without a class.
        $this->actingAs($admin)->post(route('admin.assessments.result', $assessment), [
            'result' => AssessmentSession::STATUS_COMPLETED,
            'outcome' => AssessmentSession::OUTCOME_READY,
        ])->assertSessionHas('success');

        $this->assertSame(Enquiry::STATUS_ASSESSED, $enquiry->fresh()->status);

        // Awaiting enrolment needs a recommended class.
        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), ['status' => Enquiry::STATUS_PLACED])
            ->assertSessionHas('error');

        $this->assertSame(Enquiry::STATUS_ASSESSED, $enquiry->fresh()->status);
    }

    public function test_a_no_show_assessment_cannot_be_edited_into_a_completed_one(): void
    {
        $admin = $this->makeUser('admin', 'No Show Guard Admin');
        [, $tutor] = $this->makeTutor('No Show Guard Tutor');
        $enquiry = $this->makeEnquiry(['status' => Enquiry::STATUS_CONTACTED]);
        $assessment = $this->book($enquiry, $tutor, ['status' => AssessmentSession::STATUS_NO_SHOW]);

        $this->actingAs($admin)
            ->from(route('admin.assessments.show', $assessment))
            ->post(route('admin.assessments.result', $assessment), [
                'result' => AssessmentSession::STATUS_COMPLETED,
                'outcome' => AssessmentSession::OUTCOME_READY,
            ])
            ->assertSessionHas('error');

        $this->assertSame(AssessmentSession::STATUS_NO_SHOW, $assessment->fresh()->status);
    }

    public function test_the_assessment_result_pages_render_for_the_school_and_the_tutor(): void
    {
        $admin = $this->makeUser('admin', 'Render Result Admin');
        [$tutorUser, $tutor] = $this->makeTutor('Render Result Tutor');
        $assessment = $this->book($this->makeEnquiry(), $tutor);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Record the result')
            ->assertSee('Save result');

        $this->actingAs($tutorUser)
            ->get(route('tutor.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Record the result')
            ->assertSee('Save result');
    }
}
