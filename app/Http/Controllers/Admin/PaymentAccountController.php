<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The school bank accounts parents transfer fees into.
 *
 * Only the CEO maintains them: an account can be added, corrected, switched off
 * (hidden from parents but kept for history) or deleted. Payments keep a
 * `paid_into` snapshot, so deleting an account never rewrites what a parent paid.
 */
class PaymentAccountController extends Controller
{
    public function __construct()
    {
        // Bank details are CEO-only, like the payments themselves.
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->check() && auth()->user()->canManagePayments(), 403, 'Only the CEO can manage the school bank accounts.');

            return $next($request);
        });
    }

    public function index()
    {
        return view('admin.payment-accounts.index', [
            'accounts' => PaymentAccount::with('createdBy')->withCount('payments')->ordered()->get(),
            'activeCount' => PaymentAccount::active()->count(),
            'paystackReady' => PaystackService::configured(),
            'paystackMissing' => PaystackService::missingKeys(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAccount($request);

        PaymentAccount::create([
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.payment-accounts.index')
            ->with('success', 'Bank account added. Parents can now pay into it.');
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $validated = $this->validateAccount($request, $paymentAccount);

        $paymentAccount->update([
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'instructions' => $validated['instructions'] ?? null,
            // Leaving the checkbox out of the form keeps the account as it was.
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $paymentAccount->is_active,
        ]);

        return redirect()->route('admin.payment-accounts.index')
            ->with('success', $paymentAccount->displayLabel() . ' updated.');
    }

    /** Hide or show an account on the parent payment form. */
    public function toggle(PaymentAccount $paymentAccount)
    {
        $paymentAccount->update(['is_active' => ! $paymentAccount->is_active]);

        return redirect()->route('admin.payment-accounts.index')->with(
            'success',
            $paymentAccount->is_active
                ? $paymentAccount->displayLabel() . ' switched on — parents can see it again.'
                : $paymentAccount->displayLabel() . ' switched off — it is hidden from parents. Payments already recorded keep their details.'
        );
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        $label = $paymentAccount->displayLabel();
        $payments = $paymentAccount->payments()->count();

        $paymentAccount->delete();

        return redirect()->route('admin.payment-accounts.index')->with(
            'success',
            $label . ' deleted.' . ($payments > 0
                ? ' The ' . $payments . ' payment(s) recorded against it keep the account name and number they were saved with.'
                : '')
        );
    }

    /**
     * Validate an account; numbers are stored without spaces or dashes so the
     * copy button on the parent form always shows the same value.
     *
     * @return array<string, mixed>
     */
    private function validateAccount(Request $request, ?PaymentAccount $account = null): array
    {
        if (is_string($request->input('account_number'))) {
            $request->merge([
                'account_number' => preg_replace('/[\s-]+/', '', $request->input('account_number')),
            ]);
        }

        return $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:150',
            'account_number' => [
                'required',
                'string',
                'regex:/^[0-9]{6,20}$/',
                Rule::unique('payment_accounts', 'account_number')->ignore($account ? $account->id : null),
            ],
            'instructions' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'account_number.regex' => 'Enter the account number as digits only (spaces and dashes are removed for you).',
            'account_number.unique' => 'This account number has already been added.',
        ]);
    }
}
