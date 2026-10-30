<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Note 13, phase 1 — admissions before a learner exists: capture, the pipeline
 * list, the timeline, guarded status moves and the dashboard counters.
 */
class AdmissionsTest extends TestCase
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

    private function makeEnquiry(array $overrides = []): Enquiry
    {
        return Enquiry::create(array_merge([
            'child_name' => 'Tolu Okafor',
            'guardian_name' => 'Mrs Okafor',
            'guardian_phone' => '0803000000' . rand(10, 99),
            'source' => Enquiry::SOURCE_WHATSAPP,
            'status' => Enquiry::STATUS_NEW,
        ], $overrides));
    }

    public function test_only_school_administration_can_reach_admissions(): void
    {
        $admin = $this->makeUser('admin', 'Admissions Admin');

        $this->actingAs($admin)->get(route('admin.enquiries.index'))->assertOk()->assertSee('Admissions');
        $this->actingAs($admin)->get(route('admin.enquiries.create'))->assertOk()->assertSee('Capture an enquiry');

        foreach (['tutor', 'parent', 'learner'] as $role) {
            $user = $this->makeUser($role, 'No Access ' . $role);

            $this->actingAs($user)->get(route('admin.enquiries.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.enquiries.store'), [])->assertForbidden();
        }
    }

    public function test_an_enquiry_is_captured_without_creating_an_account(): void
    {
        $admin = $this->makeUser('admin', 'Capture Admin');

        $response = $this->actingAs($admin)->post(route('admin.enquiries.store'), [
            'child_name' => 'Ada Nwosu',
            'child_grade_level' => 'JSS1',
            'guardian_name' => 'Mr Nwosu',
            'guardian_phone' => '0803 111 2222',
            'guardian_email' => 'nwosu.' . uniqid() . '@example.com',
            'source' => Enquiry::SOURCE_WHATSAPP,
            'notes' => 'Needs evening classes.',
        ]);

        $enquiry = Enquiry::where('child_name', 'Ada Nwosu')->firstOrFail();

        $response->assertRedirect(route('admin.enquiries.show', $enquiry));

        $this->assertMatchesRegularExpression('/^ENQ-\d{4}-\d{4}$/', $enquiry->reference);
        $this->assertSame(Enquiry::STATUS_NEW, $enquiry->status);
        $this->assertSame($admin->id, $enquiry->created_by);
        $this->assertSame($admin->id, $enquiry->assigned_to);
        $this->assertSame(1, $enquiry->timeline()->count());
        $this->assertSame('Enquiry captured from WhatsApp.', $enquiry->timeline()->first()->body);

        // No login or learner record is created at this stage.
        $this->assertDatabaseMissing('users', ['name' => 'Ada Nwosu']);
        $this->assertDatabaseMissing('learners', ['parent_id' => null, 'user_id' => $admin->id]);
    }

    public function test_capture_requires_the_child_and_guardian_details(): void
    {
        $admin = $this->makeUser('admin', 'Validation Admin');

        $this->actingAs($admin)
            ->from(route('admin.enquiries.create'))
            ->post(route('admin.enquiries.store'), [])
            ->assertSessionHasErrors(['child_name', 'guardian_name', 'guardian_phone', 'source']);

        $this->assertSame(0, Enquiry::count());
    }

    public function test_the_pipeline_tabs_search_and_source_filter_work(): void
    {
        $admin = $this->makeUser('admin', 'Pipeline Admin');

        $new = $this->makeEnquiry(['child_name' => 'New Child', 'guardian_phone' => '08031110001']);
        $contacted = $this->makeEnquiry([
            'child_name' => 'Contacted Child',
            'guardian_phone' => '08031110002',
            'status' => Enquiry::STATUS_CONTACTED,
        ]);
        $declined = $this->makeEnquiry([
            'child_name' => 'Declined Child',
            'guardian_phone' => '08031110003',
            'status' => Enquiry::STATUS_DECLINED,
            'declined_reason' => 'Chose another school',
        ]);
        $referral = $this->makeEnquiry([
            'child_name' => 'Referral Child',
            'guardian_phone' => '08031110004',
            'source' => Enquiry::SOURCE_REFERRAL,
        ]);

        $this->actingAs($admin)->get(route('admin.enquiries.index', ['status' => 'open']))
            ->assertOk()
            ->assertSee('New Child')
            ->assertSee('Contacted Child')
            ->assertDontSee('Declined Child');

        $this->actingAs($admin)->get(route('admin.enquiries.index', ['status' => 'declined']))
            ->assertOk()
            ->assertSee('Declined Child')
            ->assertDontSee('New Child');

        $this->actingAs($admin)->get(route('admin.enquiries.index', ['status' => 'all', 'search' => '08031110002']))
            ->assertOk()
            ->assertSee('Contacted Child')
            ->assertDontSee('New Child');

        $this->actingAs($admin)->get(route('admin.enquiries.index', ['status' => 'all', 'source' => Enquiry::SOURCE_REFERRAL]))
            ->assertOk()
            ->assertSee('Referral Child')
            ->assertDontSee('Contacted Child');
    }

    public function test_a_call_or_message_is_added_to_the_timeline(): void
    {
        $admin = $this->makeUser('admin', 'Timeline Admin');
        $enquiry = $this->makeEnquiry();

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.notes.store', $enquiry), [
                'type' => 'call',
                'body' => 'Called the guardian; wants a Saturday class.',
            ])
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'type' => 'call',
            'body' => 'Called the guardian; wants a Saturday class.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('Called the guardian; wants a Saturday class.');
    }

    public function test_an_invalid_timeline_type_is_refused(): void
    {
        $admin = $this->makeUser('admin', 'Timeline Guard Admin');
        $enquiry = $this->makeEnquiry();

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.notes.store', $enquiry), [
                'type' => 'sms',
                'body' => 'Should not be stored.',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, $enquiry->timeline()->count());
    }

    public function test_status_moves_follow_the_declared_pipeline(): void
    {
        $admin = $this->makeUser('admin', 'Status Admin');
        $enquiry = $this->makeEnquiry();

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), ['status' => Enquiry::STATUS_CONTACTED])
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHas('success');

        $this->assertSame(Enquiry::STATUS_CONTACTED, $enquiry->fresh()->status);
        $this->assertSame(1, $enquiry->timeline()->count());

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), ['status' => Enquiry::STATUS_ARCHIVED])
            ->assertSessionHas('success');

        $this->assertSame(Enquiry::STATUS_ARCHIVED, $enquiry->fresh()->status);

        // Archived is final in this phase.
        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), ['status' => Enquiry::STATUS_CONTACTED])
            ->assertSessionHas('error');

        $this->assertSame(Enquiry::STATUS_ARCHIVED, $enquiry->fresh()->status);
    }

    public function test_declining_requires_a_reason_and_records_it(): void
    {
        $admin = $this->makeUser('admin', 'Decline Admin');
        $enquiry = $this->makeEnquiry(['status' => Enquiry::STATUS_CONTACTED]);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), ['status' => Enquiry::STATUS_DECLINED])
            ->assertSessionHasErrors('declined_reason');

        $this->assertSame(Enquiry::STATUS_CONTACTED, $enquiry->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), [
                'status' => Enquiry::STATUS_DECLINED,
                'declined_reason' => 'Moved to another city',
            ])
            ->assertSessionHas('success');

        $enquiry->refresh();

        $this->assertSame(Enquiry::STATUS_DECLINED, $enquiry->status);
        $this->assertSame('Moved to another city', $enquiry->declined_reason);

        $this->assertDatabaseHas('enquiry_notes', [
            'enquiry_id' => $enquiry->id,
            'type' => 'status',
            'body' => 'Status changed from Contacted to Declined: Moved to another city',
        ]);
    }

    public function test_a_pipeline_step_cannot_be_skipped_in_this_phase(): void
    {
        $admin = $this->makeUser('admin', 'Skip Admin');
        $enquiry = $this->makeEnquiry();

        $this->actingAs($admin)
            ->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.status', $enquiry), ['status' => Enquiry::STATUS_ENROLLED])
            ->assertSessionHas('error');

        $this->assertSame(Enquiry::STATUS_NEW, $enquiry->fresh()->status);
    }

    public function test_enquiry_details_can_be_corrected(): void
    {
        $admin = $this->makeUser('admin', 'Edit Admin');
        $enquiry = $this->makeEnquiry(['child_name' => 'Wrong Name']);

        $this->actingAs($admin)
            ->put(route('admin.enquiries.update', $enquiry), [
                'child_name' => 'Right Name',
                'guardian_name' => $enquiry->guardian_name,
                'guardian_phone' => '0803 999 0000',
                'source' => Enquiry::SOURCE_PHONE,
            ])
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHas('success');

        $enquiry->refresh();

        $this->assertSame('Right Name', $enquiry->child_name);
        $this->assertSame('0803 999 0000', $enquiry->guardian_phone);
        $this->assertSame(Enquiry::SOURCE_PHONE, $enquiry->source);
    }

    public function test_the_dashboard_counts_new_and_in_progress_enquiries(): void
    {
        $admin = $this->makeUser('admin', 'Dashboard Admin');

        $this->makeEnquiry(['child_name' => 'Dash One']);
        $this->makeEnquiry(['child_name' => 'Dash Two']);
        $this->makeEnquiry(['child_name' => 'Dash Three', 'status' => Enquiry::STATUS_CONTACTED]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('newEnquiries', 2)
            ->assertViewHas('contactedEnquiries', 1)
            ->assertSee('New Enquiries')
            ->assertSee('Enquiries In Progress');
    }

    public function test_every_admissions_screen_renders(): void
    {
        $admin = $this->makeUser('admin', 'Render Admin');
        $enquiry = $this->makeEnquiry([
            'child_name' => 'Render Child',
            'guardian_email' => 'render.' . uniqid() . '@example.com',
        ]);

        $this->actingAs($admin)->get(route('admin.enquiries.index'))->assertOk()->assertSee('Render Child');
        $this->actingAs($admin)->get(route('admin.enquiries.create'))->assertOk()->assertSee('Capture an enquiry');
        $this->actingAs($admin)->get(route('admin.enquiries.show', $enquiry))->assertOk()
            ->assertSee('Render Child')->assertSee($enquiry->reference)->assertSee('Timeline');
        $this->actingAs($admin)->get(route('admin.enquiries.edit', $enquiry))->assertOk()->assertSee('Edit enquiry');
    }
}
