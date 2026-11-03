<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Learner;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Parents record the payments they have made (a parent usually pays for two or
 * three children, which is quicker than the CEO entering every family), and the
 * CEO only checks the bank and verifies them.
 *
 * Everything a parent submits is stored as `pending`; the CEO's verification is
 * what turns it into a `completed` installment that clears the balance.
 */
class PaymentController extends Controller
{
    public function index()
    {
        $children = $this->children(auth()->user()->parent);

        $fees = $children->mapWithKeys(function (Learner $child) {
            return [$child->id => Payment::feeGroupsForLearner($child->id)];
        });

        $totals = $children->mapWithKeys(function (Learner $child) {
            return [$child->id => Payment::totalsForLearner($child->id)];
        });

        return view('parent.payments.index', [
            'children' => $children,
            'fees' => $fees,
            'totals' => $totals,
            'accounts' => PaymentAccount::active()->ordered()->get(),
            'summary' => [
                'paid' => (float) $totals->sum('paid'),
                'pending' => (float) $totals->sum('pending'),
                'pending_count' => (int) $totals->sum('pending_count'),
                'balance' => (float) $totals->sum('balance'),
            ],
            'submissions' => $this->submissions($children)->paginate(10)->withQueryString(),
        ]);
    }

    public function create(Request $request)
    {
        $children = $this->children(auth()->user()->parent)->load('classes');
        $accounts = PaymentAccount::active()->ordered()->get();

        return view('parent.payments.create', [
            'children' => $children,
            'terms' => Payment::TERMS,
            'accounts' => $accounts,
            // Every active class, so a parent can pay ahead for a class the
            // child has not been enrolled in yet.
            'classes' => Classes::where('is_active', true)->orderBy('name')->get(),
            // Card is offered as "coming soon" until Paystack is connected.
            'cardReady' => PaystackService::configured(),
            'selectedLearner' => (string) $request->query('learner_id'),
            'selectedTerm' => (string) $request->query('term'),
        ]);
    }

    public function store(Request $request)
    {
        $parentUser = auth()->user();
        $children = $this->children($parentUser->parent);
        $childIds = $children->pluck('id')->all();

        // The form posts one row per payment line: child, what it is for, the
        // class, the amount and an optional purpose. Old single-installment
        // links still post learner_id/amount/class_id.
        $linesMode = $request->has('lines');

        $rules = [
            'term' => 'nullable|string|max:50',
            'payment_method' => ['required', Rule::in(Payment::PARENT_METHODS)],
            'payment_account_id' => [
                'nullable',
                Rule::requiredIf(function () use ($request) {
                    return $request->input('payment_method') === Payment::METHOD_TRANSFER;
                }),
                'exists:payment_accounts,id',
            ],
            // Every line of one submission shares the same bank reference.
            'reference' => $linesMode
                ? 'nullable|string|max:100'
                : 'nullable|string|max:100|unique:payments,reference',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ];

        $rules += $linesMode
            ? [
                'lines' => 'required|array|min:1',
                'lines.*.learner_id' => ['required', Rule::in($childIds)],
                'lines.*.payment_type' => 'required|in:tuition,registration,material,other',
                'lines.*.class_id' => 'required|exists:classes,id',
                'lines.*.amount' => 'nullable|numeric|min:0',
                'lines.*.total_fee' => 'nullable|numeric|min:0',
                'lines.*.purpose' => 'nullable|string|max:500',
            ]
            : [
                'payment_type' => 'required|in:tuition,registration,material,other',
                'learner_id' => ['required', Rule::in($childIds)],
                'class_id' => 'nullable|exists:classes,id',
                'amount' => 'required|numeric|min:1',
                'total_fee' => 'nullable|numeric|min:0',
            ];

        $validated = $request->validate($rules, [
            'learner_id.in' => 'Choose one of the children linked to your account.',
            'lines.*.learner_id.in' => 'One of the children you chose is not linked to your account.',
            'lines.*.payment_type.required' => 'Choose what each payment is for.',
            'lines.*.class_id.required' => 'Choose the class each payment belongs to.',
            'lines.*.class_id.exists' => 'One of the classes is no longer available. Refresh the page and choose again.',
            'payment_method.required' => 'Choose how you paid.',
            'payment_method.in' => 'Card payments are coming soon. Please pay by bank transfer for now.',
            'payment_account_id.required' => 'Choose the school account you transferred the money into.',
            'payment_account_id.exists' => 'That school account is no longer listed. Choose another one, or contact the school if none is listed.',
        ]);

        $lines = $this->installments($validated, $children, $linesMode);

        // Only a transfer carries a school account; cash happens at the office.
        $account = $validated['payment_method'] === Payment::METHOD_TRANSFER
            ? PaymentAccount::find($validated['payment_account_id'] ?? null)
            : null;

        // The receipt is the same document for every child and class covered.
        $receipt = [];
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $receipt = [
                'receipt_path' => $file->store('receipts', 'public'),
                'receipt_name' => $file->getClientOriginalName(),
            ];
        }

        $payments = DB::transaction(function () use ($parentUser, $lines, $validated, $account, $receipt) {
            return collect($lines)->map(function (array $line) use ($parentUser, $validated, $account, $receipt) {
                return Payment::create(array_merge([
                    'user_id' => $parentUser->id,
                    'learner_id' => $line['learner_id'],
                    'payment_type' => $line['payment_type'],
                    'purpose' => $line['purpose'],
                    'term' => $validated['term'] ?? null,
                    'class_id' => $line['class_id'],
                    'amount' => $line['amount'],
                    'total_fee' => $line['total_fee'],
                    'payment_method' => $validated['payment_method'],
                    'payment_account_id' => $account ? $account->id : null,
                    // Snapshot: deleting or renaming the account later keeps this record honest.
                    'paid_into' => $account ? $account->displayLabel() : null,
                    'reference' => $validated['reference'] ?? null,
                    // Parents can never mark their own payment as verified.
                    'status' => Payment::STATUS_PENDING,
                    'payment_date' => $validated['payment_date'],
                    'notes' => $validated['notes'] ?? null,
                ], $receipt));
            });
        });

        $paidFor = $children->whereIn('id', $payments->pluck('learner_id')->unique()->all());

        $this->notifyCeos($payments, $paidFor, $validated['payment_method']);

        $summary = $payments->count() === 1
            ? '1 installment'
            : $payments->count() . ' installments';

        $family = $paidFor->count() > 1 ? ' for ' . $paidFor->count() . ' children' : '';

        return redirect()
            ->route('parent.payments.index')
            ->with(
                'success',
                $summary . $family . ' submitted. The school will check the '
                    . ($validated['payment_method'] === Payment::METHOD_CASH ? 'cash book' : 'bank')
                    . ' and the balance will update.'
            );
    }

    /**
     * Turn the submitted rows into the installments to record.
     *
     * The form posts `lines`: one row per payment line with its own child,
     * payment type and class. A single-installment post (old links, API
     * callers) still sends learner_id/amount/class_id.
     *
     * @param  array<string, mixed>  $validated
     * @param  Collection<int, Learner>  $children
     * @return array<int, array{learner_id: int, class_id: int|null, payment_type: string, purpose: string|null, amount: float, total_fee: float|null}>
     */
    private function installments(array $validated, Collection $children, bool $linesMode): array
    {
        if (! $linesMode) {
            $learner = $children->firstWhere('id', (int) $validated['learner_id']);

            $amount = (float) $validated['amount'];
            $total = isset($validated['total_fee']) ? (float) $validated['total_fee'] : null;

            $this->guardAmount($amount, $total, 'amount');

            return [[
                'learner_id' => $learner->id,
                'class_id' => $validated['class_id'] ?? null,
                'payment_type' => $validated['payment_type'],
                'purpose' => null,
                'amount' => $amount,
                'total_fee' => $total,
            ]];
        }

        $lines = [];

        foreach (array_values($validated['lines']) as $index => $row) {
            $amount = (float) ($row['amount'] ?? 0);

            // A row left blank is simply not part of this transfer.
            if ($amount <= 0) {
                continue;
            }

            $total = $row['total_fee'] ?? null;
            $total = ($total === null || $total === '') ? null : (float) $total;

            $this->guardAmount($amount, $total, 'lines.' . $index . '.amount');

            $lines[] = [
                'learner_id' => (int) $row['learner_id'],
                'class_id' => (int) $row['class_id'],
                'payment_type' => $row['payment_type'],
                'purpose' => $row['purpose'] ?? null,
                'amount' => $amount,
                'total_fee' => $total,
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Enter the amount you paid on at least one row.',
            ]);
        }

        return $lines;
    }

    /**
     * An installment can never be larger than the full fee it belongs to.
     */
    private function guardAmount(float $amount, ?float $total, string $amountKey): void
    {
        if ($total !== null && $amount > $total) {
            throw ValidationException::withMessages([
                $amountKey => 'A single installment cannot be more than the full fee.',
            ]);
        }
    }

    /**
     * Receipts stay private: a parent may only download the ones linked to
     * their own children (or the ones they uploaded themselves).
     */
    public function receipt(Payment $payment)
    {
        $parent = auth()->user()->parent;

        $owns = $payment->user_id === auth()->id()
            || ($parent && $payment->learner && $payment->learner->parent_id === $parent->id);

        abort_unless($owns, 403, 'This receipt belongs to another family.');

        if (! $payment->receipt_path || ! Storage::disk('public')->exists($payment->receipt_path)) {
            return redirect()->route('parent.payments.index')
                ->with('error', 'No receipt has been uploaded for this payment.');
        }

        return Storage::disk('public')->download(
            $payment->receipt_path,
            $payment->receipt_name ?: basename($payment->receipt_path)
        );
    }

    /**
     * Children linked to this parent's account.
     *
     * @return Collection<int, Learner>
     */
    private function children(?ParentModel $parent): Collection
    {
        if (! $parent) {
            return collect();
        }

        return Learner::with('user')
            ->where('parent_id', $parent->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * Everything this parent has submitted (their own children plus payments
     * they paid for), newest first.
     *
     * @param Collection<int, Learner> $children
     */
    private function submissions(Collection $children)
    {
        return Payment::with(['learner.user', 'schoolClass', 'verifiedBy'])
            ->where(function ($query) use ($children) {
                $query->where('user_id', auth()->id());

                if ($children->isNotEmpty()) {
                    $query->orWhereIn('learner_id', $children->pluck('id'));
                }
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id');
    }

    /**
     * Lets the CEO know there is money to check against the bank or cash book.
     * One submission can cover several children and classes, so the note
     * summarises the whole family in one line.
     *
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, Learner>  $learners
     */
    private function notifyCeos(Collection $payments, Collection $learners, string $method): void
    {
        $payment = $payments->first();

        if (! $payment) {
            return;
        }

        $count = $payments->count();
        $classless = $payments->whereNull('class_id')->count();

        $names = $learners
            ->map(function (Learner $learner) {
                return $learner->user->name ?? null;
            })
            ->filter()
            ->values();

        $who = $names->count() > 1
            ? $names->count() . ' children (' . $names->implode(', ') . ')'
            : ($names->first() ?: 'a learner');

        $message = sprintf(
            '%s submitted %s%s for %s (%s%s) by %s%s. %s',
            auth()->user()->name,
            number_format((float) $payments->sum('amount'), 2),
            $payment->currency ? ' ' . $payment->currency : '',
            $who,
            $count === 1 ? '1 installment' : $count . ' installments',
            $classless > 0 ? ' incl. money not tied to a class' : '',
            strtolower(Payment::METHODS[$method] ?? $method),
            $payment->paid_into ? ' into ' . $payment->paid_into : '',
            $method === Payment::METHOD_CASH
                ? 'Check the cash book and verify it.'
                : 'Check the bank and verify it.'
        );

        $ceos = User::whereHas('role', function ($query) {
            $query->where('slug', 'ceo');
        })->get();

        foreach ($ceos as $ceo) {
            Notification::create([
                'user_id' => $ceo->id,
                'type' => Notification::TYPE_REMINDER,
                'title' => 'Payment awaiting verification',
                'message' => Str::limit($message, 250),
                'channel' => 'in_app',
                'is_read' => false,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }
    }
}
