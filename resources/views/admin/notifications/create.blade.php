@extends('layouts.app')
@section('title', 'Send Notification')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.notifications.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Send Notification</h1>
        <form action="{{ route('admin.notifications.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., Class Reminder"></div>
                <div><label class="block text-sm font-medium text-gray-700">Type</label><select name="type" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"><option value="info">Information</option><option value="reminder">Reminder</option><option value="alert">Alert</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700">Recipient</label><select name="user_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"><option value="">All Users</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role->name }})</option>@endforeach</select></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Message</label><textarea name="message" rows="5" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea></div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-paper-plane mr-1"></i> Send Notification</button>
        </form>
    </div>
</div>
@endsection
