@extends('layouts.app')
@section('title', 'Create Schedule')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.schedules.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Schedule</h1>
        <form action="{{ route('admin.schedules.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" value="{{ old('title') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Class &amp; Subject</label>
                    <select name="class_subject_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Choose a class and subject —</option>
                        @foreach($classSubjects->groupBy(function ($row) { return $row->class->name ?? 'Class'; }) as $className => $rows)
                            <optgroup label="{{ $className }}">
                                @foreach($rows as $row)
                                    <option value="{{ $row->id }}" {{ (string) old('class_subject_id') === (string) $row->id ? 'selected' : '' }}>
                                        {{ $row->subject->name ?? 'Subject' }} — {{ $row->tutor->user->name ?? 'No teacher' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('class_subject_id')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500 mt-1">
                        Each session teaches <strong>one subject</strong>, so a class with Mathematics, Physics and English gets one schedule per subject —
                        on the same day at different times or on different days. The session is linked automatically to that subject's teacher.
                    </p>
                    @if($classSubjects->isEmpty())
                        <p class="mt-2 text-xs text-orange-700">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            No subject has a teacher yet.
                            <a href="{{ route('admin.classes.index') }}" class="font-semibold underline">Assign teachers on the class page</a>
                            before scheduling.
                        </p>
                    @endif
                    @if($subjectsMissingTeacher->isNotEmpty())
                        <p class="mt-2 text-xs text-gray-600">
                            Waiting for a teacher (not shown above):
                            {{ $subjectsMissingTeacher->map(function ($row) { return ($row->class->name ?? 'Class') . ' → ' . ($row->subject->name ?? 'Subject'); })->implode(', ') }}.
                        </p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meeting Platform</label>
                    <select name="meeting_platform" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">None</option>
                        <option value="jitsi" {{ old('meeting_platform') === 'jitsi' ? 'selected' : '' }}>Automatic video room (Jitsi Meet)</option>
                        <option value="google_meet" {{ old('meeting_platform') === 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                        <option value="zoom" {{ old('meeting_platform') === 'zoom' ? 'selected' : '' }}>Zoom</option>
                    </select>
                </div>
                {{-- Repeat: a one-off lesson, or a weekly series on several days & times --}}
                @include('partials.schedule-repeat')
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Meeting Link (optional)</label>
                <input type="url" name="meeting_link" value="{{ old('meeting_link') }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="https://meet.google.com/...">
                <p class="text-xs text-gray-500 mt-1">Leave this blank with <strong>Automatic video room</strong> selected and a free Jitsi Meet link is generated and reused for every session in the series.</p>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea></div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-save mr-1"></i> Create Schedule</button>
        </form>
    </div>
</div>
@endsection
