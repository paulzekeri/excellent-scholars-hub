@extends('layouts.app')
@section('title', 'Create Schedule')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.schedules.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create Class Schedule</h1>
        <form action="{{ route('tutor.schedules.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Class</label><select name="class_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Start Time</label><input type="datetime-local" name="start_time" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">End Time</label><input type="datetime-local" name="end_time" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Meeting Platform</label><select name="meeting_platform" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"><option value="">None</option><option value="google_meet">Google Meet</option><option value="zoom">Zoom</option></select></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Meeting Link</label><input type="url" name="meeting_link" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://meet.google.com/..."></div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea></div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-save mr-1"></i> Create Schedule</button>
        </form>
    </div>
</div>
@endsection
