<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
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

        $batch = $request->has('class_ids');

        $rules = [
            'learner_id' => ['required', Rule::in($childIds)],
            'payment_type' => 'required|in:tuition,registration,material,other',
            'term' => 'nullable|string|max:50',
            'payment_method' => ['required', Rule::in(Payment::PARENT_METHODS)],
            'payment_account_id' => [
                'nullable',
                Rule::requiredIf(function () use ($request) {
                    return $request->input('payment_method') === Payment::METHOD_TRANSFER;
                }),
                'exists:payment_accounts,id',
            ],
            // A batch shares one bank reference across its installments.
            'reference' => $batch
                ? 'nullable|string|max:100'
                : 'nullable|string|max:100|unique:payments,reference',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ];

        // The form ticks the classes a transfer covered; a single-installment
        // post (old links, API callers) still sends class_id/amount/total_fee.
        $rules += $batch
            ? [
                'class_ids' => 'required|array|min:1',
                'class_ids.*' => 'integer|min:0',
                'amounts' => 'required|array',
                'amounts.*' => 'nullable|numeric|min:0',
                'total_fees' => 'nullable|array',
                'total_fees.*' => 'nullable|numeric|min:0',
            ]
            : [
                'class_id' => 'nullable|exists:classes,id',
                'amount' => 'required|numeric|min:1',
                'total_fee' => 'nullable|numeric|min:0',
            ];

        $validated = $request->validate($rules, [
            'learner_id.in' => 'Choose one of the children linked to your account.',
            'payment_method.required' => 'Choose how you paid.',
            'payment_method.in' => 'Card payments are coming soon. Please pay by bank transfer for now.',
            'payment_account_id.required' => 'Choose the school account you transferred the money into.',
            'payment_account_id.exists' => 'That school account is no longer listed. Choose another one, or contact the school if none is listed.',
        ]);

        $learner = $children->firstWhere('id', (int) $validated['learner_id']);
        $lines = $this->installments($validated, $learner, $batch);

        // Only a transfer carries a school account; cash happens at the office.
        $account = $validated['payment_method'] === Payment::METHOD_TRANSFER
            ? PaymentAccount::find($validated['payment_account_id'] ?? null)
            : null;

        // The receipt is the same document for every class in the transfer.
        $receipt = [];
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $receipt = [
                'receipt_path' => $file->store('receipts', 'public'),
                'receipt_name' => $file->getClientOriginalName(),
            ];
        }

        $payments = DB::transaction(function () use ($parentUser, $learner, $lines, $validated, $account, $receipt) {
            return collect($lines)->map(function (array $line) use ($parentUser, $learner, $validated, $account, $receipt) {
                return Payment::create(array_merge([
                    'user_id' => $parentUser->id,
                    'learner_id' => $learner->id,
                    'payment_type' => $validated['payment_type'],
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

        $this->notifyCeos($payments, $learner, $validated['payment_method']);

        $summary = $payments->count() === 1
            ? '1 installment'
            : $payments->count() . ' installments';

        return redirect()
            ->route('parent.payments.index')
            ->with(
                'success',
                $summary . ' submitted. The school will check the '
                    . ($validated['payment_method'] === Payment::METHOD_CASH ? 'cash book' : 'bank')
                    . ' and the balance will update.'
            );
    }

    /**
     * Turn the ticked classes into the installments to record.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, array{class_id: int|null, amount: float, total_fee: float|null}>
     */
    private function installments(array $validated, Learner $learner, bool $batch): array
    {
        if (! $batch) {
            $amount = (float) $validated['amount'];
            $total = isset($validated['total_fee']) ? (float) $validated['total_fee'] : null;

            $this->guardLine((int) ($validated['class_id'] ?? 0), $amount, $total, $learner, 'amount', 'class_id');

            return [['class_id' => $validated['class_id'] ?? null, 'amount' => $amount, 'total_fee' => $total]];
        }

        $amounts = $validated['amounts'] ?? [];
        $fees = $validated['total_fees'] ?? [];
        $lines = [];

        foreach (array_unique($validated['class_ids']) as $key) {
            $key = (int) $key;
            $amount = (float) ($amounts[$key] ?? 0);

            // A ticked class with no amount is simply not part of this transfer.
            if ($amount <= 0) {
                continue;
            }

            $total = $fees[$key] ?? null;
            $total = ($total === null || $total === '') ? null : (float) $total;

            $this->guardLine($key, $amount, $total, $learner, 'amounts.' . $key, 'class_ids.' . $key);

            $lines[] = ['class_id' => $key ?: null, 'amount' => $amount, 'total_fee' => $total];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'amounts' => 'Enter the amount you paid against at least one class.',
            ]);
        }

        return $lines;
    }

    /**
     * A line may only point at the child's own class, and an installment can
     * never be larger than the whole fee.
     */
    private function guardLine(int $classId, float $amount, ?float $total, Learner $learner, string $amountKey, string $classKey): void
    {
        if ($classId > 0 && ! $learner->classes()->whereKey($classId)->exists()) {
            throw ValidationException::withMessages([
                $classKey => 'That class is not on this child\'s timetable.',
            ]);
        }

        if ($total !== null && $amount > $total) {
            throw ValidationException::withMessages([
                $amountKey => 'A single installment cannot be more than the total fee.',
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
     * One submission can cover several classes, so the note summarises them.
     *
     * @param  Collection<int, Payment>  $payments
     */
    private function notifyCeos(Collection $payments, Learner $learner, string $method): void
    {
        $payment = $payments->first();

        if (! $payment) {
            return;
        }

        $count = $payments->count();
        $classes = $payments->pluck('class_id')->filter()->count();

        $message = sprintf(
            '%s submitted %s%s for %s (%s%s) by %s%s. %s',
            auth()->user()->name,
            number_format((float) $payments->sum('amount'), 2),
            $payment->currency ? ' ' . $payment->currency : '',
            $learner->user->name ?? 'a learner',
            $count === 1 ? '1 installment' : $count . ' installments',
            $count > 1 && $classes < $count ? ' incl. a general payment' : '',
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
