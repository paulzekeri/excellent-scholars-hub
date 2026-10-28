@extends('layouts.app')
@section('title', 'Send Notification')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.notifications.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Send Notification</h1>
        <form action="{{ route('admin.notifications.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" value="{{ old('title') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Class Reminder"></div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                    <select id="type" name="type" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        @foreach(\App\Models\Notification::COMPOSABLE_TYPES as $type)
                            <option value="{{ $type }}" {{ old('type', \App\Models\Notification::TYPE_INFO) === $type ? 'selected' : '' }}>{{ \App\Models\Notification::metaFor($type)['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Recipient</label><select name="user_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"><option value="">All Users</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ (string) old('user_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->role->name }})</option>@endforeach</select></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach(\App\Models\Notification::COMPOSABLE_TYPES as $type)
                    @php $typeMeta = \App\Models\Notification::metaFor($type); @endphp
                    <div class="border border-gray-200 rounded-lg p-3">
                        <p class="text-sm font-medium text-gray-800"><i class="fas {{ $typeMeta['icon'] }} {{ $typeMeta['icon_class'] }} mr-1"></i>{{ $typeMeta['label'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $typeMeta['description'] }}</p>
                    </div>
                @endforeach
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Message</label><textarea name="message" rows="5" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">{{ old('message') }}</textarea></div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-paper-plane mr-1"></i> Send Notification</button>
        </form>
    </div>
</div>
@endsection
