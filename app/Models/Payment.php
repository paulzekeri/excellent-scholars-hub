<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Payment extends Model
{
    use HasFactory;

    /** Terms offered on the payment screens; the field still accepts anything. */
    public const TERMS = ['First Term', 'Second Term', 'Third Term'];

    /*
    |--------------------------------------------------------------------------
    | Payment methods
    |--------------------------------------------------------------------------
    | A parent pays by bank transfer (into one of the school accounts the CEO
    | added), in cash at the office, or - once Paystack is connected - by card.
    | Cash and transfer both wait for the CEO's confirmation; a card payment is
    | confirmed by Paystack itself.
    */
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_POS = 'pos';

    /** Methods the parent form offers today (card is still "coming soon"). */
    public const PARENT_METHODS = [self::METHOD_TRANSFER, self::METHOD_CASH];

    /** Wording used on every payments screen. */
    public const METHODS = [
        self::METHOD_TRANSFER => 'Bank transfer',
        self::METHOD_CASH => 'Cash at the school',
        self::METHOD_CARD => 'Card (Paystack)',
        self::METHOD_POS => 'POS at the school',
    ];

    /** Tailwind chip classes per method (literal so the CDN build keeps them). */
    public const METHOD_BADGES = [
        self::METHOD_TRANSFER => 'bg-blue-100 text-blue-800',
        self::METHOD_CASH => 'bg-green-100 text-green-800',
        self::METHOD_CARD => 'bg-orange-100 text-orange-800',
        self::METHOD_POS => 'bg-gray-100 text-gray-700',
    ];

    /** Gateway name stored on card payments once Paystack is wired up. */
    public const GATEWAY_PAYSTACK = 'paystack';

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    | A parent records the payment they made at the bank, which arrives as
    | `pending`. The CEO checks it against the bank statement and verifies it to
    | `completed` - only completed installments reduce a balance. A payment that
    | cannot be matched is rejected (`failed`) with a reason.
    */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    /** Status options with the wording used on every payments screen. */
    public const STATUSES = [
        self::STATUS_PENDING => 'Pending verification',
        self::STATUS_COMPLETED => 'Verified',
        self::STATUS_FAILED => 'Rejected',
        self::STATUS_REFUNDED => 'Refunded',
    ];

    /** Tailwind chip classes per status (literal so the CDN build keeps them). */
    public const STATUS_BADGES = [
        self::STATUS_PENDING => 'bg-orange-100 text-orange-800',
        self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
        self::STATUS_FAILED => 'bg-orange-100 text-orange-800',
        self::STATUS_REFUNDED => 'bg-gray-100 text-gray-800',
    ];

    protected $fillable = [
        'user_id', 'learner_id', 'payment_type', 'term', 'class_id', 'amount', 'total_fee',
        'currency', 'payment_method', 'payment_account_id', 'paid_into', 'gateway',
        'gateway_reference', 'reference', 'status', 'notes', 'payment_date',
        'receipt_path', 'receipt_name', 'verified_at', 'verified_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
        'total_fee' => 'float',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function learner()
    {
        return $this->belongsTo(Learner::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /** The school bank account the money was transferred into, if any. */
    public function paymentAccount()
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    /** The CEO (or administrator) who confirmed this payment with the bank. */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Payments that actually count towards a balance.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Payments a parent has submitted and the school has not confirmed yet.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? self::STATUS_BADGES[self::STATUS_REFUNDED];
    }

    public function isCash(): bool
    {
        return $this->payment_method === self::METHOD_CASH;
    }

    public function isCard(): bool
    {
        return in_array($this->payment_method, [self::METHOD_CARD, self::METHOD_POS], true);
    }

    /** "Bank transfer", "Cash at the school", ... */
    public function methodLabel(): string
    {
        if (! $this->payment_method) {
            return 'Not recorded';
        }

        return self::METHODS[$this->payment_method]
            ?? ucwords(str_replace(['_', '-'], ' ', $this->payment_method));
    }

    public function methodBadge(): string
    {
        return self::METHOD_BADGES[$this->payment_method] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * Where the money went: the snapshot saved with the payment, the linked
     * account, or the office for cash.
     */
    public function paidIntoLabel(): string
    {
        if ($this->paid_into) {
            return $this->paid_into;
        }

        if ($this->paymentAccount) {
            return $this->paymentAccount->displayLabel();
        }

        return $this->isCash() ? 'School office (cash)' : 'Not recorded';
    }

    /** What the CEO should check before pressing "Payment received". */
    public function verificationHint(): string
    {
        if ($this->isCash()) {
            return 'Confirm the amount in the cash book and against the bursar\'s receipt before verifying.';
        }

        if ($this->isCard()) {
            return 'Confirm the transaction in the Paystack dashboard before verifying.';
        }

        if ($this->payment_account_id || $this->paid_into) {
            return 'Match the amount and reference on the statement for ' . $this->paidIntoLabel() . '.';
        }

        return 'Match the amount and reference on the school bank statement.';
    }

    /** Short line for the parent's own history table. */
    public function pendingNote(): string
    {
        if ($this->isCash()) {
            return 'The school is checking the cash book.';
        }

        if ($this->isCard()) {
            return 'Waiting for the Paystack confirmation.';
        }

        return 'The school is checking this with the bank.';
    }

    /**
     * The CEO matched the money with the bank, so the installment now counts.
     */
    public function markVerified(User $user): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'verified_at' => now(),
            'verified_by' => $user->id,
        ])->save();
    }

    /**
     * The money could not be matched; the reason is kept on the record so the
     * parent can see why and correct it.
     */
    public function markRejected(User $user, string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'verified_at' => now(),
            'verified_by' => $user->id,
            'notes' => trim(($this->notes ? $this->notes . "\n" : '') . 'Rejected: ' . $reason),
        ])->save();
    }

    /**
     * Where a learner stands for a fee described by (term, class).
     *
     * A payment row is one installment, so `paid` is the sum of the completed
     * rows and `due` is the fee recorded on those same rows.
     *
     * @return array{due: float, paid: float, balance: float, percent: ?int, installments: int}
     */
    public static function summaryFor(int $learnerId, ?string $term = null, ?int $classId = null): array
    {
        $query = static::where('learner_id', $learnerId);

        if ($term !== null && $term !== '') {
            $query->where('term', $term);
        }

        if ($classId !== null) {
            $query->where('class_id', $classId);
        }

        $rows = $query->get();
        $completed = $rows->where('status', 'completed');

        $due = (float) $rows->max('total_fee');
        $paid = (float) $completed->sum('amount');

        return [
            'due' => $due,
            'paid' => $paid,
            'balance' => max($due - $paid, 0),
            'percent' => $due > 0 ? (int) min(100, round($paid / $due * 100)) : null,
            'installments' => $completed->count(),
        ];
    }

    /**
     * Every fee a learner has a payment against, each with its own balance.
     *
     * A fee is one (term, class) pair - the same shape `summaryFor()` uses - so
     * a parent paying for two children, or two terms, sees one progress bar per
     * fee instead of a single merged total.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function feeGroupsForLearner(int $learnerId): Collection
    {
        $rows = static::with('schoolClass')
            ->where('learner_id', $learnerId)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        return $rows
            ->groupBy(function (Payment $payment) {
                return ($payment->term ?? '') . '|' . ($payment->class_id ?? '');
            })
            ->map(function (Collection $group) {
                $completed = $group->where('status', self::STATUS_COMPLETED);
                $pending = $group->where('status', self::STATUS_PENDING);
                $due = (float) $group->max('total_fee');
                $paid = (float) $completed->sum('amount');
                $first = $group->first();

                return [
                    'term' => $first->term,
                    'class_id' => $first->class_id,
                    'class' => $first->schoolClass,
                    'label' => $first->feeLabel(),
                    'due' => $due,
                    'paid' => $paid,
                    'pending' => (float) $pending->sum('amount'),
                    'pending_count' => $pending->count(),
                    'balance' => max($due - $paid, 0),
                    'percent' => $due > 0 ? (int) min(100, round($paid / $due * 100)) : null,
                    'installments' => $completed->count(),
                    'rows' => $group,
                ];
            })
            ->values();
    }

    /**
     * Every fee of a learner added together, for dashboard and page summaries.
     *
     * @return array{due: float, paid: float, pending: float, pending_count: int, balance: float, fees: int, settled: int}
     */
    public static function totalsForLearner(int $learnerId): array
    {
        $groups = static::feeGroupsForLearner($learnerId);

        return [
            'due' => (float) $groups->sum('due'),
            'paid' => (float) $groups->sum('paid'),
            'pending' => (float) $groups->sum('pending'),
            'pending_count' => (int) $groups->sum('pending_count'),
            'balance' => (float) $groups->sum('balance'),
            'fees' => $groups->count(),
            'settled' => $groups->filter(function (array $group) {
                return $group['due'] > 0 && $group['balance'] <= 0;
            })->count(),
        ];
    }

    /** Human label for the fee this payment belongs to. */
    public function feeLabel(): string
    {
        $parts = array_filter([
            $this->term ?: null,
            $this->schoolClass->name ?? null,
        ]);

        return $parts ? implode(' · ', $parts) : 'General';
    }
}
