@extends('layouts.app')
@section('title', 'Payment ' . ($payment->reference ?? $payment->id))
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.payments.index') }}" class="text-blue-700 hover:text-blue-900 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> All payments</a>

    <div class="bg-white rounded-xl shadow-md p-8 mb-6">
        <div class="flex flex-wrap justify-between items-start gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Reference</p>
                <h1 class="text-2xl font-bold text-gray-800">{{ $payment->reference ?? 'PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</h1>
                <p class="text-gray-600 mt-1">
                    {{ $payment->learner->user->name ?? $payment->user->name }} · {{ $payment->feeLabel() }}
                </p>
            </div>
            <span class="px-3 py-1 text-sm rounded-full {{ $payment->statusBadge() }}">
                {{ $payment->statusLabel() }}
            </span>
        </div>

        @if($payment->isPending())
            <div class="mt-6 rounded-lg border-l-4 border-orange-500 bg-orange-50 p-4">
                <p class="text-sm font-semibold text-orange-800"><i class="fas fa-magnifying-glass-dollar mr-1"></i>Waiting for your bank check</p>
                <p class="text-sm text-gray-700 mt-1">
                    {{ $payment->user->name ?? 'The parent' }} submitted this payment on {{ $payment->created_at->format('M d, Y H:i') }}
                    by <strong>{{ strtolower($payment->methodLabel()) }}</strong>@if($payment->paid_into) into {{ $payment->paid_into }}@endif.
                    @if($payment->reference) Reference: <strong>{{ $payment->reference }}</strong>. @endif
                </p>
                <p class="text-sm text-gray-700 mt-1"><i class="fas fa-circle-info mr-1 text-orange-700"></i>{{ $payment->verificationHint() }}</p>
                <div class="mt-4 flex flex-wrap items-start gap-3">
                    <form method="POST" action="{{ route('admin.payments.verify', $payment) }}">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-green-700 transition">
                            <i class="fas fa-check-double mr-1"></i> Payment received — mark complete
                        </button>
                    </form>
                    <details class="rounded-lg bg-white/70 px-4 py-2">
                        <summary class="cursor-pointer text-sm font-medium text-orange-800 select-none">
                            <i class="fas fa-ban mr-1"></i> Cannot match it in the bank?
                        </summary>
                        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="mt-3 space-y-2">
                            @csrf
                            <label class="block text-xs font-medium text-gray-600">Tell the parent why</label>
                            <input type="text" name="reason" required maxlength="500" placeholder="e.g. No transfer of this amount on 27 Oct" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-orange-500 focus:border-orange-500">
                            <button type="submit" class="bg-orange-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-800 transition">Reject payment</button>
                        </form>
                    </details>
                </div>
            </div>
        @endif

        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 text-sm">
            <div><dt class="text-gray-500">Installment</dt><dd class="font-semibold text-gray-800">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</dd></div>
            <div><dt class="text-gray-500">Payment date</dt><dd class="font-semibold text-gray-800">{{ $payment->payment_date->format('M d, Y') }}</dd></div>
            <div>
                <dt class="text-gray-500">Method</dt>
                <dd class="mt-1"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $payment->methodBadge() }}">{{ $payment->methodLabel() }}</span></dd>
            </div>
            <div>
                <dt class="text-gray-500">Paid into</dt>
                <dd class="font-semibold text-gray-800">
                    {{ $payment->paidIntoLabel() }}
                    @if($payment->paymentAccount)
                        <span class="block text-xs font-normal text-gray-500">{{ $payment->paymentAccount->account_name }}</span>
                    @endif
                    @if($payment->gateway_reference)
                        <span class="block text-xs font-normal text-gray-500">Paystack: {{ $payment->gateway_reference }}</span>
                    @endif
                </dd>
            </div>
            <div><dt class="text-gray-500">Type</dt><dd class="font-semibold text-gray-800">{{ ucfirst($payment->payment_type) }}</dd></div>
            <div><dt class="text-gray-500">Term</dt><dd class="font-semibold text-gray-800">{{ $payment->term ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Class</dt><dd class="font-semibold text-gray-800">{{ $payment->schoolClass->name ?? 'Not class-specific' }}</dd></div>
            <div><dt class="text-gray-500">Recorded by</dt><dd class="font-semibold text-gray-800">{{ $payment->user->name ?? 'N/A' }}</dd></div>
            <div>
                <dt class="text-gray-500">Verified</dt>
                <dd class="font-semibold text-gray-800">
                    @if($payment->verified_at)
                        {{ $payment->verified_at->format('M d, Y H:i') }}
                        <span class="block text-xs font-normal text-gray-500">by {{ $payment->verifiedBy->name ?? 'CEO' }}</span>
                    @else
                        <span class="text-gray-500">Not yet</span>
                    @endif
                </dd>
            </div>
        </dl>

        @if($payment->notes)
            <div class="mt-6 border-l-4 border-orange-400 bg-orange-50 p-4 rounded-r-lg">
                <p class="text-xs uppercase text-orange-700 font-semibold">Notes</p>
                <p class="text-sm text-gray-700">{{ $payment->notes }}</p>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-md p-8 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-scale-balanced text-blue-600 mr-2"></i>Fee balance</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4 text-sm">
            <div><p class="text-gray-500">Total fee</p><p class="text-xl font-bold text-gray-800">₦{{ number_format($summary['due'], 2) }}</p></div>
            <div><p class="text-gray-500">Paid so far</p><p class="text-xl font-bold text-green-700">₦{{ number_format($summary['paid'], 2) }}</p></div>
            <div><p class="text-gray-500">Balance</p><p class="text-xl font-bold {{ $summary['balance'] > 0 ? 'text-orange-700' : 'text-gray-800' }}">₦{{ number_format($summary['balance'], 2) }}</p></div>
        </div>
        @if($summary['percent'] !== null)
            <div class="w-full bg-gray-100 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $summary['percent'] }}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ $summary['percent'] }}% settled over {{ $summary['installments'] }} installment(s).</p>
            @if($summary['balance'] <= 0)
                <p class="mt-3 inline-block rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                    <i class="fas fa-circle-check mr-1"></i> Payment complete — this fee is fully paid
                </p>
            @endif
        @else
            <p class="text-sm text-gray-500">No total fee was entered for this record, so no balance is tracked.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-md p-8 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-paperclip text-orange-700 mr-2"></i>Receipt</h2>
        @if($payment->receipt_path)
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="text-gray-700"><i class="fas fa-file-lines mr-1"></i>{{ $payment->receipt_name ?? basename($payment->receipt_path) }}</span>
                <a href="{{ route('admin.payments.receipt', $payment) }}" class="bg-orange-500 text-blue-950 px-4 py-2 rounded-lg hover:bg-orange-400 transition"><i class="fas fa-download mr-1"></i> Download receipt</a>
            </div>
        @else
            <p class="text-sm text-gray-500">No receipt uploaded for this installment.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold text-gray-800">Installment history for this fee</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Payer</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($installments as $installment)
                        <tr class="{{ $installment->id === $payment->id ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-3">{{ $installment->payment_date->format('M d, Y') }}</td>
                            <td class="px-6 py-3 font-semibold">{{ number_format($installment->amount, 2) }}</td>
                            <td class="px-6 py-3">{{ $installment->user->name ?? '—' }}</td>
                            <td class="px-6 py-3">{{ $installment->statusLabel() }}</td>
                            <td class="px-6 py-3">
                                @if($installment->receipt_path)
                                    <a href="{{ route('admin.payments.receipt', $installment) }}" class="text-orange-700 hover:text-orange-800"><i class="fas fa-download"></i></a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
