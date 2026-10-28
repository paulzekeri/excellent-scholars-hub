<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Role;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fees can be paid by bank transfer (into an account the CEO maintains), in cash
 * at the office, or - later - by card through Paystack. The CEO only sees active
 * accounts on the parent form and every payment keeps the account it used.
 */
class PaymentMethodsTest extends TestCase
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
     * @return array{0: User, 1: Learner}
     */
    private function makeParentWithChild(): array
    {
        $parentUser = $this->makeUser('parent', 'Ada Okafor');
        $parent = ParentModel::create(['user_id' => $parentUser->id]);

        $child = Learner::create([
            'user_id' => $this->makeUser('learner', 'Chidi Okafor')->id,
            'parent_id' => $parent->id,
            'grade_level' => 'JSS 2',
        ]);

        return [$parentUser, $child];
    }

    private function makeAccount(array $overrides = []): PaymentAccount
    {
        return PaymentAccount::create(array_merge([
            'bank_name' => 'GTBank',
            'account_name' => 'Excellent Scholars Academy',
            'account_number' => (string) random_int(1000000000, 9999999999),
            'is_active' => true,
        ], $overrides));
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
            'reference' => 'REF-' . uniqid(),
            'payment_date' => now()->toDateString(),
        ], $overrides);
    }

    public function test_only_the_ceo_can_add_switch_off_edit_and_delete_school_accounts(): void
    {
        $ceo = $this->makeUser('ceo', 'Chief Executive');
        $admin = $this->makeUser('admin', 'School Admin');
        [$parentUser] = $this->makeParentWithChild();

        // Bank details are CEO-only, exactly like the payments themselves.
        $this->actingAs($admin)->get(route('admin.payment-accounts.index'))->assertForbidden();
        $this->actingAs($parentUser)->get(route('admin.payment-accounts.index'))->assertForbidden();
        $this->actingAs($parentUser)->post(route('admin.payment-accounts.store'), [])->assertForbidden();

        $number = (string) random_int(1000000000, 9999999999);
        $spaced = substr($number, 0, 4) . ' ' . substr($number, 4);

        $this->actingAs($ceo)->post(route('admin.payment-accounts.store'), [
            'bank_name' => 'Access Bank',
            'account_name' => 'Excellent Scholars Academy',
            'account_number' => $spaced,
            'instructions' => 'Use the child\'s name as narration.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.payment-accounts.index'))->assertSessionHas('success');

        // Spaces are stripped so the copy button always shows the same value.
        $account = PaymentAccount::where('account_number', $number)->firstOrFail();
        $this->assertTrue($account->is_active);

        $this->actingAs($parentUser)->get(route('parent.payments.create'))
            ->assertOk()
            ->assertSee('Access Bank')
            ->assertSee($number)
            ->assertSee('Use the child&#039;s name as narration.', false);

        // Switching it off hides it from parents but keeps it on the CEO page.
        $this->actingAs($ceo)->followingRedirects()
            ->patch(route('admin.payment-accounts.toggle', $account))
            ->assertOk()
            ->assertSee('switched off');
        $this->assertFalse($account->fresh()->is_active);

        $this->actingAs($parentUser)->get(route('parent.payments.create'))
            ->assertOk()
            ->assertDontSee($number);

        $this->actingAs($ceo)->get(route('admin.payment-accounts.index'))
            ->assertOk()
            ->assertSee('Switched off')
            ->assertSee($number);

        // Editing corrects a typo without touching payments already made.
        $this->actingAs($ceo)->patch(route('admin.payment-accounts.update', $account), [
            'bank_name' => 'Access Bank Plc',
            'account_name' => 'Excellent Scholars Academy',
            'account_number' => $number,
            'instructions' => null,
        ])->assertSessionHas('success');

        $this->assertSame('Access Bank Plc', $account->fresh()->bank_name);

        $this->actingAs($ceo)->delete(route('admin.payment-accounts.destroy', $account))
            ->assertRedirect(route('admin.payment-accounts.index'))
            ->assertSessionHas('success');

        $this->assertNull(PaymentAccount::find($account->id));
    }

    public function test_an_account_can_be_added_already_switched_off(): void
    {
        $ceo = $this->makeUser('ceo', 'Chief Executive');
        $number = (string) random_int(1000000000, 9999999999);

        $this->actingAs($ceo)->post(route('admin.payment-accounts.store'), [
            'bank_name' => 'Polaris Bank',
            'account_name' => 'Excellent Scholars Academy',
            'account_number' => $number,
            'is_active' => '0',
        ])->assertRedirect(route('admin.payment-accounts.index'))->assertSessionHas('success');

        $account = PaymentAccount::where('account_number', $number)->firstOrFail();
        $this->assertFalse($account->is_active);

        // Hidden from parents until the CEO switches it on.
        [$parentUser] = $this->makeParentWithChild();
        $this->actingAs($parentUser)->get(route('parent.payments.create'))
            ->assertOk()
            ->assertDontSee($number);
    }

    public function test_a_transfer_must_name_the_school_account_it_was_paid_into(): void
    {
        [$parentUser, $child] = $this->makeParentWithChild();
        $account = $this->makeAccount(['bank_name' => 'Zenith Bank']);

        $this->actingAs($parentUser)
            ->from(route('parent.payments.create'))
            ->post(route('parent.payments.store'), $this->paymentData($child, ['payment_account_id' => '']))
            ->assertSessionHasErrors('payment_account_id');

        $this->assertSame(0, Payment::where('learner_id', $child->id)->count());

        $this->actingAs($parentUser)->post(route('parent.payments.store'), $this->paymentData($child, [
            'payment_account_id' => $account->id,
            'reference' => 'TRF-' . uniqid(),
        ]))->assertRedirect(route('parent.payments.index'));

        $payment = Payment::where('learner_id', $child->id)->firstOrFail();

        $this->assertSame(Payment::METHOD_TRANSFER, $payment->payment_method);
        $this->assertSame($account->id, $payment->payment_account_id);
        $this->assertSame($account->bank_name . ' · ' . $account->account_number, $payment->paid_into);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
    }

    public function test_a_cash_payment_is_recorded_without_a_bank_account(): void
    {
        [$parentUser, $child] = $this->makeParentWithChild();
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $this->actingAs($parentUser)->post(route('parent.payments.store'), $this->paymentData($child, [
            'payment_method' => Payment::METHOD_CASH,
            'payment_account_id' => null,
            'reference' => null,
        ]))->assertRedirect(route('parent.payments.index'))->assertSessionHas('success');

        $payment = Payment::where('learner_id', $child->id)->firstOrFail();

        $this->assertSame(Payment::METHOD_CASH, $payment->payment_method);
        $this->assertNull($payment->payment_account_id);
        $this->assertNull($payment->paid_into);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);

        // The CEO is pointed at the cash book, not the bank statement.
        $this->actingAs($ceo)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('Cash at the school')
            ->assertSee('cash book')
            ->assertSee('School office (cash)');
    }

    public function test_card_payments_are_shown_as_coming_soon_and_cannot_be_submitted(): void
    {
        [$parentUser, $child] = $this->makeParentWithChild();

        $this->actingAs($parentUser)->get(route('parent.payments.create'))
            ->assertOk()
            ->assertSee('Card')
            ->assertSee('coming soon')
            ->assertSee('Paystack');

        $this->actingAs($parentUser)
            ->from(route('parent.payments.create'))
            ->post(route('parent.payments.store'), $this->paymentData($child, [
                'payment_method' => Payment::METHOD_CARD,
                'payment_account_id' => null,
            ]))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Payment::where('learner_id', $child->id)->count());
    }


    public function test_the_ceo_queue_filters_by_how_the_money_was_paid(): void
    {
        [$parentUser, $child] = $this->makeParentWithChild();
        $ceo = $this->makeUser('ceo', 'Chief Executive');
        $account = $this->makeAccount(['bank_name' => 'First Bank']);

        $this->makePayment($child, $parentUser, [
            'payment_method' => Payment::METHOD_TRANSFER,
            'payment_account_id' => $account->id,
            'paid_into' => $account->displayLabel(),
            'reference' => 'TRF-SEE-ME',
        ]);

        $this->makePayment($child, $parentUser, [
            'payment_method' => Payment::METHOD_CASH,
            'reference' => 'CASH-SEE-ME',
        ]);

        $this->actingAs($ceo)->get(route('admin.payments.index', ['method' => Payment::METHOD_TRANSFER]))
            ->assertOk()
            ->assertSee('TRF-SEE-ME')
            ->assertDontSee('CASH-SEE-ME')
            ->assertSee('Bank transfer')
            ->assertSee('First Bank');

        $this->actingAs($ceo)->get(route('admin.payments.index', ['method' => Payment::METHOD_CASH]))
            ->assertOk()
            ->assertSee('CASH-SEE-ME')
            ->assertDontSee('TRF-SEE-ME')
            ->assertSee('Cash at the school');
    }

    public function test_deleting_a_school_account_keeps_the_payment_history(): void
    {
        [$parentUser, $child] = $this->makeParentWithChild();
        $ceo = $this->makeUser('ceo', 'Chief Executive');
        $account = $this->makeAccount(['bank_name' => 'UBA']);

        $payment = $this->makePayment($child, $parentUser, [
            'payment_method' => Payment::METHOD_TRANSFER,
            'payment_account_id' => $account->id,
            'paid_into' => $account->displayLabel(),
        ]);

        $this->actingAs($ceo)->delete(route('admin.payment-accounts.destroy', $account))
            ->assertSessionHas('success');

        $payment->refresh();

        // The link is cleared, but the snapshot still shows where the money went.
        $this->assertNull($payment->payment_account_id);
        $this->assertSame('UBA · ' . $account->account_number, $payment->paid_into);

        $this->actingAs($ceo)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('UBA · ' . $account->account_number);
    }

    public function test_the_bank_accounts_screen_reports_whether_paystack_is_connected(): void
    {
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        config(['services.paystack.public_key' => null, 'services.paystack.secret_key' => null]);

        $this->assertFalse(PaystackService::configured());
        $this->assertSame(['PAYSTACK_PUBLIC_KEY', 'PAYSTACK_SECRET_KEY'], PaystackService::missingKeys());

        $this->actingAs($ceo)->get(route('admin.payment-accounts.index'))
            ->assertOk()
            ->assertSee('Not connected yet')
            ->assertSee('PAYSTACK_SECRET_KEY');

        config(['services.paystack.public_key' => 'pk_test_demo', 'services.paystack.secret_key' => 'sk_test_demo']);

        $this->assertTrue(PaystackService::configured());
        $this->assertSame([], PaystackService::missingKeys());

        $this->actingAs($ceo)->get(route('admin.payment-accounts.index'))
            ->assertOk()
            ->assertSee('Paystack keys detected');
    }

    /**
     * A pending row for the CEO queue / history screens.
     */
    private function makePayment(Learner $child, User $payer, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $payer->id,
            'learner_id' => $child->id,
            'payment_type' => 'tuition',
            'term' => 'First Term',
            'amount' => 25000,
            'total_fee' => 100000,
            'status' => Payment::STATUS_PENDING,
            'payment_date' => now(),
        ], $overrides));
    }
}

