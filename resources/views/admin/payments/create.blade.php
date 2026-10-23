@extends('layouts.app')
@section('title', 'Record Payment')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.payments.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Record Payment</h1>
        <form action="{{ route('admin.payments.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">User</label>
                    <select name="user_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach($learners as $learner)<option value="{{ $learner->user_id }}">{{ $learner->user->name }}</option>@endforeach
                    </select></div>
                <div><label class="block text-sm font-medium text-gray-700">Learner (optional)</label>
                    <select name="learner_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">None</option>@foreach($learners as $learner)<option value="{{ $learner->id }}">{{ $learner->user->name }}</option>@endforeach
                    </select></div>
                <div><label class="block text-sm font-medium text-gray-700">Payment Type</label>
                    <select name="payment_type" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="tuition">Tuition</option><option value="registration">Registration</option><option value="material">Material</option><option value="other">Other</option>
                    </select></div>
                <div><label class="block text-sm font-medium text-gray-700">Amount</label><input type="number" step="0.01" name="amount" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select name="payment_method" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select</option><option value="cash">Cash</option><option value="transfer">Bank Transfer</option><option value="card">Card</option>
                    </select></div>
                <div><label class="block text-sm font-medium text-gray-700">Reference</label><input type="text" name="reference" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="pending">Pending</option><option value="completed">Completed</option><option value="failed">Failed</option>
                    </select></div>
                <div><label class="block text-sm font-medium text-gray-700">Payment Date</label><input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Notes</label><textarea name="notes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea></div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-save mr-1"></i> Record Payment</button>
        </form>
    </div>
</div>
@endsection
