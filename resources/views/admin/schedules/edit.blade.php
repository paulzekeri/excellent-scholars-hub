@extends('layouts.app')
@section('title', 'Edit Schedule')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.schedules.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Schedule</h1>
        <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST" class="space-y-6">@csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" value="{{ $schedule->title }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Class</label><select name="class_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">@foreach($classes as $class)<option value="{{ $class->id }}" {{ $schedule->class_id == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Tutor</label><select name="tutor_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">@foreach($tutors as $tutor)<option value="{{ $tutor->id }}" {{ $schedule->tutor_id == $tutor->id ? 'selected' : '' }}>{{ $tutor->user->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Status</label><select name="status" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"><option value="scheduled" {{ $schedule->status == 'scheduled' ? 'selected' : '' }}>Scheduled</option><option value="ongoing" {{ $schedule->status == 'ongoing' ? 'selected' : '' }}>Ongoing</option><option value="completed" {{ $schedule->status == 'completed' ? 'selected' : '' }}>Completed</option><option value="cancelled" {{ $schedule->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700">Start Time</label><input type="datetime-local" name="start_time" value="{{ \Carbon\Carbon::parse($schedule->start_time)->format('Y-m-d\TH:i') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">End Time</label><input type="datetime-local" name="end_time" value="{{ \Carbon\Carbon::parse($schedule->end_time)->format('Y-m-d\TH:i') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Meeting Link</label><input type="url" name="meeting_link" value="{{ $schedule->meeting_link }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-save mr-1"></i> Update Schedule</button>
        </form>
    </div>
</div>
@endsection
