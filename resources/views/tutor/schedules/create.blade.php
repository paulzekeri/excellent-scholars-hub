@extends('layouts.app')
@section('title', 'Create Schedule')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.schedules.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create Class Schedule</h1>
        <form action="{{ route('tutor.schedules.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" value="{{ old('title') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Class</label><select name="class_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Start Time</label><input type="datetime-local" name="start_time" value="{{ old('start_time') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">End Time</label><input type="datetime-local" name="end_time" value="{{ old('end_time') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meeting Platform</label>
                    <select name="meeting_platform" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">None</option>
                        <option value="jitsi" {{ old('meeting_platform') === 'jitsi' ? 'selected' : '' }}>Automatic video room (Jitsi Meet)</option>
                        <option value="google_meet" {{ old('meeting_platform') === 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                        <option value="zoom" {{ old('meeting_platform') === 'zoom' ? 'selected' : '' }}>Zoom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Repeat</label>
                    <select name="repeat" id="schedule-repeat" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="none" {{ old('repeat', 'none') === 'none' ? 'selected' : '' }}>Does not repeat</option>
                        <option value="weekly" {{ old('repeat') === 'weekly' ? 'selected' : '' }}>Every week (same day &amp; time)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Set the first lesson's date in Start Time — it is repeated the same weekday and time until the date you choose.</p>
                </div>
                <div id="repeat-until-row">
                    <label class="block text-sm font-medium text-gray-700">Repeat until</label>
                    <input type="date" name="repeat_until" value="{{ old('repeat_until') }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    @error('repeat_until')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Meeting Link</label>
                <input type="url" name="meeting_link" value="{{ old('meeting_link') }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="https://meet.google.com/...">
                <p class="text-xs text-gray-500 mt-1">Leave this blank with <strong>Automatic video room</strong> selected and a free Jitsi Meet link is generated for you (the same link for every session in the series).</p>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea></div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-save mr-1"></i> Create Schedule</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const repeat = document.getElementById('schedule-repeat');
        const untilRow = document.getElementById('repeat-until-row');
        const until = untilRow ? untilRow.querySelector('input') : null;

        function sync() {
            if (!repeat || !untilRow) return;
            const weekly = repeat.value === 'weekly';
            untilRow.classList.toggle('hidden', !weekly);
            if (until) until.required = weekly;
        }

        if (repeat) repeat.addEventListener('change', sync);
        sync();
    });
</script>
@endpush
@endsection
