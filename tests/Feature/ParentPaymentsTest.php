<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Parents record the payments they made (a parent usually pays for two or three
 * children) and the CEO only confirms the money with the bank. Verifying a
 * submission is what marks the learner's fee as paid.
 */
class ParentPaymentsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ]);
    }

    /**
     * @return array{0: User, 1: ParentModel}
     */
    private function makeParent(string $name = 'Parent'): array
    {
        $user = $this->makeUser('parent', $name);
        $parent = ParentModel::create(['user_id' => $user->id]);

        return [$user, $parent];
    }

    private function makeChild(ParentModel $parent, string $name = 'Child'): Learner
    {
        return Learner::create([
            'user_id' => $this->makeUser('learner', $name)->id,
            'parent_id' => $parent->id,
            'grade_level' => 'JSS 2',
        ]);
    }

    /** The school account a transfer is paid into (the CEO maintains these). */
    private function account(): PaymentAccount
    {
        return PaymentAccount::firstOrCreate(
            ['account_number' => '0099887766'],
            [
                'bank_name' => 'GTBank',
                'account_name' => 'Excellent Scholars Academy',
                'is_active' => true,
            ]
        );
    }

    private function paymentData(Learner $child, array $overrides = []): array
    {
        return array_merge([
            'learner_id' => $child->id,
            'payment_type' => 'tuition',
            'term' => 'First Term',
            'amount' => 25000,
            'total_fee' => 100000,
            'payment_method' => Payment::METHOD_TRANSFER,
            'payment_account_id' => $this->account()->id,
            'reference' => 'REF-' . uniqid(),
            'payment_date' => now()->toDateString(),
        ], $overrides);
    }

    private function makePayment(Learner $child, User $payer, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $payer->id,
            'learner_id' => $child->id,
            'payment_type' => 'tuition',
            'term' => 'First Term',
            'amount' => 100000,
            'total_fee' => 100000,
            'status' => Payment::STATUS_PENDING,
            'payment_date' => now(),
        ], $overrides));
    }

    public function test_a_parent_records_a_payment_that_waits_for_the_ceo_to_verify_it(): void
    {
        Storage::fake('public');

        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $response = $this->actingAs($parentUser)->post(route('parent.payments.store'), $this->paymentData($child, [
            // A parent can never fast-track their own payment.
            'status' => 'completed',
            'receipt' => UploadedFile::fake()->create('teller.pdf', 120, 'application/pdf'),
        ]));

        $response->assertRedirect(route('parent.payments.index'));

        $payment = Payment::where('learner_id', $child->id)->firstOrFail();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertNull($payment->verified_at);
        $this->assertSame($parentUser->id, $payment->user_id, 'The parent is recorded as the payer.');
        $this->assertSame(25000.0, $payment->amount);
        $this->assertNotNull($payment->receipt_path);
        $this->assertSame('teller.pdf', $payment->receipt_name);
        Storage::disk('public')->assertExists($payment->receipt_path);

        // A pending submission must not touch the balance yet.
        $this->assertSame(0.0, Payment::summaryFor($child->id, 'First Term')['paid']);

        // The CEO is told there is money to check against the bank.
        $this->assertSame(1, Notification::where('user_id', $ceo->id)
            ->where('title', 'Payment awaiting verification')
            ->count());

        $this->actingAs($parentUser)->get(route('parent.payments.index'))
            ->assertOk()
            ->assertSee('Pending verification')
            ->assertSee('Chidi Okafor')
            ->assertSee('teller.pdf', false);
    }

    public function test_a_parent_cannot_record_a_payment_for_someone_elses_child(): void
    {
        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $this->makeChild($parent, 'Chidi Okafor');

        [$otherParentUser, $otherParent] = $this->makeParent('Bola Ade');
        $otherChild = $this->makeChild($otherParent, 'Tunde Ade');

        $this->actingAs($parentUser)
            ->post(route('parent.payments.store'), $this->paymentData($otherChild))
            ->assertSessionHasErrors('learner_id');

        $this->assertSame(0, Payment::where('learner_id', $otherChild->id)->count());
    }

    public function test_the_ceo_verifies_a_payment_and_the_fee_is_marked_complete(): void
    {
        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $payment = $this->makePayment($child, $parentUser);

        $this->actingAs($ceo)
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.verify', $payment))
            ->assertRedirect(route('admin.payments.index'))
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame($ceo->id, $payment->verified_by);

        // Marking the payment complete clears the fee.
        $summary = Payment::summaryFor($child->id, 'First Term');
        $this->assertSame(100000.0, $summary['paid']);
        $this->assertSame(0.0, $summary['balance']);
        $this->assertSame(100, $summary['percent']);

        // The parent is told the money was confirmed.
        $this->assertSame(1, Notification::where('user_id', $parentUser->id)
            ->where('title', 'Payment verified')
            ->count());

        $this->actingAs($ceo)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('Payment complete')
            ->assertSee('Verified');
    }

    public function test_the_ceo_can_reject_a_payment_with_a_reason(): void
    {
        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $payment = $this->makePayment($child, $parentUser, ['reference' => 'FBN-0001']);

        // A reason is mandatory - the parent has to know what went wrong.
        $this->actingAs($ceo)
            ->post(route('admin.payments.reject', $payment), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);

        $this->actingAs($ceo)
            ->from(route('admin.payments.show', $payment))
            ->post(route('admin.payments.reject', $payment), [
                'reason' => 'No transfer of this amount reached the school account.',
            ])
            ->assertRedirect(route('admin.payments.show', $payment))
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);
        $this->assertSame($ceo->id, $payment->verified_by);
        $this->assertStringContainsString('No transfer of this amount', $payment->notes);

        // Rejected money never counts towards the balance.
        $this->assertSame(0.0, Payment::summaryFor($child->id, 'First Term')['paid']);

        $this->assertSame(1, Notification::where('user_id', $parentUser->id)
            ->where('title', 'Payment could not be verified')
            ->count());
    }

    public function test_only_the_ceo_can_verify_or_reject_a_payment(): void
    {
        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');
        $admin = $this->makeUser('admin', 'School Admin');

        $payment = $this->makePayment($child, $parentUser);

        $this->actingAs($admin)->post(route('admin.payments.verify', $payment))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.payments.reject', $payment), ['reason' => 'Nope'])->assertForbidden();
        $this->actingAs($parentUser)->post(route('admin.payments.verify', $payment))->assertForbidden();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertNull($payment->verified_by);
    }

    public function test_the_ceo_payments_screen_is_a_verification_queue_without_the_record_button(): void
    {
        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $this->makePayment($child, $parentUser, ['amount' => 45000, 'total_fee' => 100000]);

        // Recording moved to the parent area; the CEO only verifies now.
        $this->assertFalse(Route::has('admin.payments.create'));
        $this->assertFalse(Route::has('admin.payments.store'));

        $this->actingAs($ceo)->get(route('admin.payments.index'))
            ->assertOk()
            ->assertDontSee('Record Payment')
            ->assertSee('Awaiting verification')
            ->assertSee('Payment received')
            ->assertSee('Chidi Okafor');
    }

    public function test_a_parent_only_downloads_receipts_for_their_own_children(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('receipts/teller.pdf', 'fake receipt');

        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');
        $payment = $this->makePayment($child, $parentUser, [
            'receipt_path' => 'receipts/teller.pdf',
            'receipt_name' => 'teller.pdf',
        ]);

        [$otherParentUser] = $this->makeParent('Bola Ade');

        $this->actingAs($parentUser)->get(route('parent.payments.receipt', $payment))->assertOk();
        $this->actingAs($otherParentUser)->get(route('parent.payments.receipt', $payment))->assertForbidden();
    }

    public function test_the_parent_area_links_to_fees_and_shows_the_verified_balance(): void
    {
        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');
        $learnerUser = $child->user;

        $this->makePayment($child, $parentUser, [
            'amount' => 40000,
            'total_fee' => 100000,
            'status' => Payment::STATUS_COMPLETED,
        ]);
        $this->makePayment($child, $parentUser, [
            'amount' => 20000,
            'total_fee' => 100000,
            'status' => Payment::STATUS_PENDING,
            'reference' => 'REF-PENDING',
        ]);

        // Sidebar entry + the fee line on the child's dashboard card.
        $this->actingAs($parentUser)->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Fees &amp; Payments', false)
            ->assertSee(route('parent.payments.index'), false)
            ->assertSee('40,000.00')
            ->assertSee('60,000.00')
            ->assertSee('1 pending');

        $this->actingAs($parentUser)->get(route('parent.payments.index'))
            ->assertOk()
            ->assertSee('First Term')
            ->assertSee('₦60,000.00')
            ->assertSee('awaiting verification');

        // Only parents can open the fee screens.
        $this->actingAs($learnerUser)->get(route('parent.payments.index'))->assertForbidden();
    }

    public function test_a_parent_submission_cannot_be_larger_than_the_total_fee(): void
    {
        [$parentUser, $parent] = $this->makeParent('Ada Okafor');
        $child = $this->makeChild($parent, 'Chidi Okafor');

        $this->actingAs($parentUser)
            ->from(route('parent.payments.create'))
            ->post(route('parent.payments.store'), $this->paymentData($child, [
                'amount' => 150000,
                'total_fee' => 100000,
            ]))
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::where('learner_id', $child->id)->count());
    }
}



