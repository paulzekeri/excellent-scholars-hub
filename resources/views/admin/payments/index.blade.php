@extends('layouts.app')
@section('title', 'Payments')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Payments</h1>
            <p class="text-gray-600 mt-1">Parents record what they paid; you check the bank and verify it</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.payment-accounts.index') }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-building-columns"></i> Bank accounts
                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800">{{ $accounts['active'] }}</span>
            </a>
            <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 bg-orange-500 text-blue-950 px-5 py-2 rounded-lg hover:bg-orange-400 transition">
                <i class="fas fa-clock"></i> Awaiting verification
                @if($totals['pending_count'] > 0)
                    <span class="bg-white text-orange-700 text-xs font-bold rounded-full px-2 py-0.5">{{ $totals['pending_count'] }}</span>
                @endif
            </a>
            <a href="{{ route('admin.payments.index') }}" class="bg-white border border-gray-300 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-list mr-1"></i> All payments
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-600">
            <p class="text-gray-500 text-sm">Total Recorded</p>
            <p class="text-2xl font-bold text-gray-800">₦{{ number_format($totals['recorded'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Completed installments only</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-orange-500">
            <p class="text-gray-500 text-sm">Pending</p>
            <p class="text-2xl font-bold text-gray-800">₦{{ number_format($totals['pending'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $totals['pending_count'] }} submission{{ $totals['pending_count'] === 1 ? '' : 's' }} to check</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-orange-500">
            <p class="text-gray-500 text-sm">Outstanding</p>
            <p class="text-2xl font-bold text-gray-800">₦{{ number_format($totals['outstanding'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Balance left across every fee</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-gray-400">
            <p class="text-gray-500 text-sm">Receipts Uploaded</p>
            <p class="text-2xl font-bold text-gray-800">{{ $totals['receipts'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Downloadable from this page</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.payments.index') }}" class="bg-white rounded-xl shadow-md p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Learner</label>
            <select name="learner_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All learners</option>
                @foreach($learners as $learner)
                    <option value="{{ $learner->id }}" @selected((string) $filters['learner_id'] === (string) $learner->id)>{{ $learner->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Term</label>
            <select name="term" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All terms</option>
                @foreach($terms as $term)
                    <option value="{{ $term }}" @selected($filters['term'] === $term)>{{ $term }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
            <select name="class_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Any status</option>
                @foreach(\App\Models\Payment::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Method</label>
            <select name="method" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Any method</option>
                @foreach($methods as $value => $label)
                    <option value="{{ $value }}" @selected($filters['method'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-filter mr-1"></i> Filter</button>
            <a href="{{ route('admin.payments.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Reset</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Learner</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Fee (term / class)</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Installment</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Receipt</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Check</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-blue-50/40">
                        <td class="px-6 py-4 text-sm font-medium">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-blue-700 hover:text-orange-700">
                                {{ $payment->reference ?? 'PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}
                            </a>
                            <span class="block text-xs text-gray-500">{{ ucfirst($payment->payment_type) }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            {{ $payment->learner->user->name ?? $payment->user->name }}
                            @if($payment->learner && $payment->user && $payment->learner->user_id !== $payment->user_id)
                                <span class="block text-xs text-gray-500">Paid by {{ $payment->user->name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->feeLabel() }}</td>
                        <td class="px-6 py-4 text-sm font-semibold">
                            {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                            @if($payment->total_fee)
                                <span class="block text-xs font-normal text-gray-500">of {{ number_format($payment->total_fee, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $payment->methodBadge() }}">{{ $payment->methodLabel() }}</span>
                            @if($payment->paid_into)
                                <span class="mt-1 block text-xs text-gray-500">to {{ $payment->paid_into }}</span>
                            @elseif($payment->isCash())
                                <span class="mt-1 block text-xs text-gray-500">at the school office</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($payment->receipt_path)
                                <a href="{{ route('admin.payments.receipt', $payment) }}" class="text-orange-700 hover:text-orange-800" title="{{ $payment->receipt_name }}">
                                    <i class="fas fa-file-invoice mr-1"></i> Download
                                </a>
                            @else
                                <span class="text-gray-500 text-xs">None</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full {{ $payment->statusBadge() }}">{{ $payment->statusLabel() }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->isPending())
                                <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-green-700 transition" title="I have seen this money in the bank">
                                        <i class="fas fa-check mr-1"></i> Payment received
                                    </button>
                                </form>
                                <a href="{{ route('admin.payments.show', $payment) }}" class="ml-2 text-xs text-gray-600 hover:text-orange-700">Review</a>
                            @elseif($payment->verified_at)
                                <span class="block text-xs text-gray-600">by {{ $payment->verifiedBy->name ?? 'CEO' }}</span>
                                <span class="block text-xs text-gray-500">{{ $payment->verified_at->format('M d, Y') }}</span>
                            @else
                                <span class="text-xs text-gray-500">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-6 py-10 text-center text-gray-500">No payments match these filters yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
