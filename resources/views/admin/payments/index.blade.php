@extends('layouts.app')
@section('title', 'Payments')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Payments</h1><p class="text-gray-600 mt-1">All payment records</p></div>
        <a href="{{ route('admin.payments.create') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-plus mr-1"></i> Record Payment</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">{{ $payment->reference ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $payment->user->name }}@if($payment->learner)<br><span class="text-xs text-gray-500">{{ $payment->learner->user->name }}</span>@endif</td>
                        <td class="px-6 py-4 text-sm">{{ ucfirst($payment->payment_type) }}</td>
                        <td class="px-6 py-4 text-sm font-semibold">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                        <td class="px-6 py-4 text-sm">{{ $payment->payment_method ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($payment->status == 'completed') bg-green-100 text-green-800 @elseif($payment->status == 'pending') bg-yellow-100 text-yellow-800 @elseif($payment->status == 'failed') bg-red-100 text-red-800 @else bg-gray-100 text-gray-800 @endif">{{ ucfirst($payment->status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
