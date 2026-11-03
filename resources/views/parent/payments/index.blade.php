@extends('layouts.app')

@section('title', 'Fees & Payments')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Fees &amp; Payments</h1>
            <p class="text-gray-600 mt-1">Record what you paid — the school checks the bank and confirms it</p>
        </div>
        <a href="{{ route('parent.payments.create') }}" class="inline-flex items-center justify-center bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i> Record a payment
        </a>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-900 p-4 rounded-lg mb-6 text-sm">
        <p class="font-semibold"><i class="fas fa-circle-info mr-1"></i> You record it, the school confirms it</p>
        <p class="mt-1">
            Every payment you submit shows as <strong>Pending verification</strong> until the CEO checks it against
            the bank statement. Only verified payments reduce your child's balance, and a fee with a zero balance is
            marked <strong>Payment complete</strong>.
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-md p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-building-columns text-blue-600 mr-2"></i>How to pay the school</h2>
            <a href="{{ route('parent.payments.create') }}" class="text-sm font-semibold text-blue-700 hover:text-orange-700"><i class="fas fa-arrow-right mr-1"></i> Record a payment</a>
        </div>

        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
            @forelse($accounts as $account)
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-3">
                    <p class="text-sm font-semibold text-gray-800">{{ $account->bank_name }} <span class="text-xs font-normal text-gray-500">· bank transfer</span></p>
                    <p class="text-xs text-gray-500">{{ $account->account_name }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <span class="font-mono text-base font-semibold text-gray-900">{{ $account->account_number }}</span>
                        <button type="button" data-copy="{{ $account->account_number }}" class="rounded-md border border-blue-300 px-2 py-0.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                            <i class="fas fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                    @if($account->instructions)
                        <p class="mt-1 text-xs text-gray-500">{{ $account->instructions }}</p>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-orange-300 bg-orange-50 p-3 text-sm text-orange-800">
                    <i class="fas fa-circle-info mr-1"></i> No school bank account has been listed yet. Please contact the school and try again once it has been added.
                </div>
            @endforelse
        </div>

        <p class="mt-3 text-xs text-gray-500"><i class="fas fa-credit-card mr-1"></i> Card payments via Paystack are coming soon.</p>
    </div>



    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-600">
            <p class="text-gray-500 text-sm">Verified paid</p>
            <p class="text-2xl font-bold text-gray-800">₦{{ number_format($summary['paid'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Confirmed by the school</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-orange-500">
            <p class="text-gray-500 text-sm">Awaiting verification</p>
            <p class="text-2xl font-bold text-gray-800">₦{{ number_format($summary['pending'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $summary['pending_count'] }} submission{{ $summary['pending_count'] === 1 ? '' : 's' }} with the school</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-orange-500">
            <p class="text-gray-500 text-sm">Outstanding</p>
            <p class="text-2xl font-bold text-gray-800">₦{{ number_format($summary['balance'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Balance left across every fee</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-600">
            <p class="text-gray-500 text-sm">Children</p>
            <p class="text-2xl font-bold text-gray-800">{{ $children->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">One payment can cover each of them</p>
        </div>
    </div>

    <h2 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-child text-blue-600 mr-2"></i>Your children's fees</h2>

    @forelse($children as $child)
        @php
            $childFees = $fees[$child->id] ?? collect();
            $childTotals = $totals[$child->id];
        @endphp
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                        <i class="fas fa-user-graduate"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-800">{{ $child->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $child->grade_level ?? 'No grade set' }} · {{ $child->classes->count() }} class{{ $child->classes->count() === 1 ? '' : 'es' }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($childTotals['balance'] <= 0 && $childTotals['due'] > 0)
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800"><i class="fas fa-circle-check mr-1"></i>Fees complete</span>
                    @elseif($childTotals['balance'] > 0)
                        <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-900">₦{{ number_format($childTotals['balance'], 2) }} outstanding</span>
                    @endif
                    @if($childTotals['pending_count'])
                        <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800">{{ $childTotals['pending_count'] }} pending</span>
                    @endif
                </div>
            </div>

            <div class="p-5">
                @forelse($childFees as $fee)
                    <div class="rounded-lg border border-gray-200 p-4 mb-3 last:mb-0">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $fee['label'] }}</p>
                                <p class="text-xs text-gray-500">
                                    @if($fee['due'] > 0)
                                        Total fee ₦{{ number_format($fee['due'], 2) }} · verified ₦{{ number_format($fee['paid'], 2) }}
                                    @else
                                        {{ $fee['installments'] }} verified installment{{ $fee['installments'] === 1 ? '' : 's' }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                @if($fee['due'] > 0 && $fee['balance'] <= 0)
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800"><i class="fas fa-check mr-1"></i>Payment complete</span>
                                @elseif($fee['balance'] > 0)
                                    <span class="text-sm font-semibold text-orange-700">₦{{ number_format($fee['balance'], 2) }} left</span>
                                @endif
                                @if($fee['pending_count'])
                                    <span class="mt-1 block text-xs text-orange-700">₦{{ number_format($fee['pending'], 2) }} awaiting verification</span>
                                @endif
                            </div>
                        </div>

                        @if($fee['percent'] !== null)
                            <div class="mt-3 w-full rounded-full bg-gray-100 h-2">
                                <div class="h-2 rounded-full {{ $fee['balance'] <= 0 ? 'bg-green-600' : 'bg-blue-600' }}" style="width: {{ $fee['percent'] }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $fee['percent'] }}% settled</p>
                        @endif

                        <a href="{{ route('parent.payments.create', ['learner_id' => $child->id, 'term' => $fee['term'], 'class_id' => $fee['class_id']]) }}"
                           class="mt-3 inline-block text-xs font-semibold text-blue-700 hover:text-orange-700">
                            <i class="fas fa-plus-circle mr-1"></i> Add a part payment to this fee
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">
                        No fee has been recorded for {{ $child->user->name }} yet. Record the first payment and the
                        school will set it up as an installment.
                    </p>
                @endforelse

                <a href="{{ route('parent.payments.create', ['learner_id' => $child->id]) }}"
                   class="mt-4 inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    <i class="fas fa-money-bill-wave mr-2"></i> Record a payment for {{ $child->user->name }}
                </a>
            </div>
        </div>
    @empty
        <div class="text-center py-12 bg-white rounded-xl shadow-md">
            <i class="fas fa-child text-gray-500 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No children linked to your account yet.</p>
            <p class="text-gray-500">Please contact the administrator.</p>
        </div>
    @endforelse

    <h2 class="text-lg font-semibold text-gray-800 mt-8 mb-3"><i class="fas fa-clock-rotate-left text-orange-700 mr-2"></i>Your payment submissions</h2>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Child</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Fee</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Receipt</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($submissions as $payment)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4">
                                {{ $payment->payment_date->format('M d, Y') }}
                                <span class="block text-xs text-gray-500">sent {{ $payment->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="px-6 py-4">{{ $payment->learner->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $payment->feeLabel() }}
                                @if($payment->purpose)
                                    <span class="mt-1 block text-xs italic text-gray-500">{{ \Illuminate\Support\Str::limit($payment->purpose, 50) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $payment->methodBadge() }}">{{ $payment->methodLabel() }}</span>
                                @if($payment->paid_into)
                                    <span class="mt-1 block text-xs text-gray-500">to {{ $payment->paid_into }}</span>
                                @endif
                                @if($payment->reference)
                                    <span class="block text-xs text-gray-500">{{ $payment->reference }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($payment->receipt_path)
                                    <a href="{{ route('parent.payments.receipt', $payment) }}" class="text-orange-700 hover:text-orange-800" title="{{ $payment->receipt_name }}">
                                        <i class="fas fa-file-invoice mr-1"></i> Download
                                    </a>
                                @else
                                    <span class="text-xs text-gray-500">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2 py-1 text-xs {{ $payment->statusBadge() }}">{{ $payment->statusLabel() }}</span>
                                @if($payment->isPending())
                                    <span class="mt-1 block text-xs text-gray-500">{{ $payment->pendingNote() }}</span>
                                @endif
                                @if($payment->status === \App\Models\Payment::STATUS_FAILED && $payment->notes)
                                    <span class="mt-1 block text-xs text-orange-700">{{ \Illuminate\Support\Str::limit($payment->notes, 90) }}</span>
                                @endif
                                @if($payment->verified_at && $payment->verifiedBy)
                                    <span class="mt-1 block text-xs text-gray-500">by {{ $payment->verifiedBy->name }} on {{ $payment->verified_at->format('M d, Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500">You have not submitted any payment yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $submissions->links() }}</div>
    </div>
</div>

<script>
    // Copy a school account number straight from the fees page.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                const value = button.dataset.copy || '';
                const original = button.innerHTML;

                function confirmCopy() {
                    button.innerHTML = '<i class="fas fa-check mr-1"></i> Copied';
                    setTimeout(function () { button.innerHTML = original; }, 1500);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(value).then(confirmCopy).catch(function () {});
                    return;
                }

                const helper = document.createElement('textarea');
                helper.value = value;
                document.body.appendChild(helper);
                helper.select();
                try { document.execCommand('copy'); confirmCopy(); } catch (error) {}
                document.body.removeChild(helper);
            });
        });
    });
</script>
@endsection

