@extends('layouts.app')
@section('title', 'Create Class')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.classes.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Class</h1>
        <form action="{{ route('admin.classes.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Class Name</label><input type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Class Code <span class="text-gray-500 font-normal">(optional)</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="Leave blank to auto-generate">
                    <p class="text-xs text-gray-500 mt-1">A unique code is created from the class name when you leave this blank.</p>
                    @error('code')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Lead Tutor</label>
                    <select name="tutor_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">@foreach($tutors as $tutor)<option value="{{ $tutor->id }}" {{ (string) old('tutor_id') === (string) $tutor->id ? 'selected' : '' }}>{{ $tutor->user->name }}</option>@endforeach</select>
                    <p class="text-xs text-gray-500 mt-1">The class coordinator. The teacher for each subject is chosen below.</p>
                </div>
                @include('partials.class-subject-rows', ['tutors' => $tutors, 'subjectRows' => $subjectRows, 'subjects' => $subjects])
                <div>
                    <label class="block text-sm font-medium text-gray-700">Grade Level</label>
                    <select name="grade_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Not set</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" {{ (string) old('grade_id') === (string) $grade->id ? 'selected' : '' }}>{{ $grade->label() }}{{ $grade->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Managed under Academics → Grades.</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Max Students <span class="text-gray-500 font-normal">(optional)</span></label><input type="number" min="1" name="max_students" placeholder="Leave blank for no limit" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></textarea></div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-save mr-1"></i> Create Class</button>
        </form>
    </div>
</div>
@endsection
