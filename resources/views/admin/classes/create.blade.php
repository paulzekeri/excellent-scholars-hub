@extends('layouts.app')
@section('title', 'Create Class')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.classes.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Class</h1>
        <form action="{{ route('admin.classes.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Class Name</label><input type="text" name="name" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Class Code</label><input type="text" name="code" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Tutor</label><select name="tutor_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">@foreach($tutors as $tutor)<option value="{{ $tutor->id }}">{{ $tutor->user->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Subject</label><input type="text" name="subject" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Grade Level</label><input type="text" name="grade_level" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Max Students <span class="text-gray-400 font-normal">(optional)</span></label><input type="number" min="1" name="max_students" placeholder="Leave blank for no limit" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></textarea></div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-save mr-1"></i> Create Class</button>
        </form>
    </div>
</div>
@endsection
