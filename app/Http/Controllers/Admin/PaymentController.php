<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Learner;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Payments are CEO-only; routes are also protected by the role:ceo middleware.
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->check() && auth()->user()->canManagePayments(), 403, 'Only the CEO can access payments.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $filters = [
            'learner_id' => $request->query('learner_id'),
            'term' => $request->query('term'),
            'class_id' => $request->query('class_id'),
            'status' => $request->query('status'),
            'method' => $request->query('method'),
        ];

        $payments = Payment::with('user', 'learner.user', 'schoolClass', 'verifiedBy', 'paymentAccount')
            ->when($filters['learner_id'], function ($query, $learnerId) {
                $query->where('learner_id', $learnerId);
            })
            ->when($filters['term'], function ($query, $term) {
                $query->where('term', $term);
            })
            ->when($filters['class_id'], function ($query, $classId) {
                $query->where('class_id', $classId);
            })
            ->when($filters['status'], function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['method'], function ($query, $method) {
                $query->where('payment_method', $method);
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $completed = Payment::completed()
            ->select('learner_id', 'term', 'class_id', 'total_fee', 'amount')
            ->get();

        $outstanding = $completed
            ->groupBy(function (Payment $payment) {
                return $payment->learner_id . '|' . $payment->term . '|' . $payment->class_id;
            })
            ->sum(function ($rows) {
                return max((float) $rows->max('total_fee') - (float) $rows->sum('amount'), 0);
            });

        $totals = [
            'recorded' => (float) Payment::completed()->sum('amount'),
            'pending' => (float) Payment::pending()->sum('amount'),
            'pending_count' => Payment::pending()->count(),
            'outstanding' => (float) $outstanding,
            'receipts' => Payment::whereNotNull('receipt_path')->count(),
        ];

        $terms = Payment::TERMS;
        foreach (Payment::whereNotNull('term')->distinct()->pluck('term') as $term) {
            if ($term !== '' && ! in_array($term, $terms, true)) {
                $terms[] = $term;
            }
        }

        return view('admin.payments.index', [
            'payments' => $payments,
            'filters' => $filters,
            'totals' => $totals,
            'terms' => $terms,
            'methods' => Payment::METHODS,
            'accounts' => [
                'active' => PaymentAccount::active()->count(),
                'total' => PaymentAccount::count(),
            ],
            'learners' => Learner::with('user')->where('is_active', true)->get(),
            'classes' => Classes::orderBy('name')->get(),
        ]);
    }

    /**
     * The CEO checked the bank and the money is there: the installment becomes
     * `completed` (which is what clears the learner's fee) and the parent is
     * told.
     */
    public function verify(Payment $payment)
    {
        if ($payment->isVerified()) {
            return back()->with('error', 'This payment has already been verified.');
        }

        $payment->markVerified(auth()->user());

        $summary = Payment::summaryFor(
            (int) $payment->learner_id,
            $payment->term,
            $payment->class_id ? (int) $payment->class_id : null
        );

        $this->notifyPayer(
            $payment,
            Notification::TYPE_INFO,
            'Payment verified',
            sprintf(
                'Your %s%s payment for %s (%s) has been verified.%s',
                number_format($payment->amount, 2),
                $payment->currency ? ' ' . $payment->currency : '',
                $payment->learner->user->name ?? 'your child',
                $payment->feeLabel(),
                $summary['due'] > 0 && $summary['balance'] <= 0
                    ? ' That fee is now paid in full.'
                    : ' Balance left: ' . number_format($summary['balance'], 2) . '.'
            )
        );

        return back()->with(
            'success',
            $summary['due'] > 0 && $summary['balance'] <= 0
                ? 'Payment verified — ' . ($payment->learner->user->name ?? 'the learner') . ' has completed this fee.'
                : 'Payment verified and added to the balance.'
        );
    }

    /**
     * The money could not be matched with the bank statement. The parent sees
     * the reason and can submit the payment again.
     */
    public function reject(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($payment->isVerified()) {
            return back()->with('error', 'A verified payment cannot be rejected.');
        }

        $payment->markRejected(auth()->user(), $validated['reason']);

        $this->notifyPayer(
            $payment,
            Notification::TYPE_ALERT,
            'Payment could not be verified',
            sprintf(
                'We could not verify your %s%s payment for %s (%s), paid by %s. Reason: %s. Please check the details and submit it again.',
                number_format($payment->amount, 2),
                $payment->currency ? ' ' . $payment->currency : '',
                $payment->learner->user->name ?? 'your child',
                $payment->feeLabel(),
                strtolower($payment->methodLabel()),
                $validated['reason']
            )
        );

        return back()->with('success', 'Payment rejected. The parent has been told why.');
    }

    public function show(Payment $payment)
    {
        $payment->load('user', 'learner.user', 'schoolClass', 'verifiedBy', 'paymentAccount');

        return view('admin.payments.show', [
            'payment' => $payment,
            'summary' => Payment::summaryFor(
                (int) $payment->learner_id,
                $payment->term,
                $payment->class_id ? (int) $payment->class_id : null
            ),
            'installments' => Payment::with('user')
                ->when($payment->learner_id, function ($query) use ($payment) {
                    $query->where('learner_id', $payment->learner_id);
                })
                ->when($payment->term, function ($query) use ($payment) {
                    $query->where('term', $payment->term);
                })
                ->when($payment->class_id, function ($query) use ($payment) {
                    $query->where('class_id', $payment->class_id);
                })
                ->orderBy('payment_date')
                ->get(),
        ]);
    }

    public function receipt(Payment $payment)
    {
        if (! $payment->receipt_path || ! Storage::disk('public')->exists($payment->receipt_path)) {
            return redirect()->route('admin.payments.show', $payment)
                ->with('error', 'No receipt has been uploaded for this payment.');
        }

        return Storage::disk('public')->download(
            $payment->receipt_path,
            $payment->receipt_name ?: basename($payment->receipt_path)
        );
    }

    /**
     * Tells the family what happened to the payment they submitted.
     */
    private function notifyPayer(Payment $payment, string $type, string $title, string $message): void
    {
        $payer = $payment->user;

        if (! $payer) {
            return;
        }

        Notification::create([
            'user_id' => $payer->id,
            'type' => $type,
            'title' => $title,
            'message' => Str::limit($message, 250),
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
