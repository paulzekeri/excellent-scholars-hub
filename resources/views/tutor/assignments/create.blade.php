@extends('layouts.app')
@section('title', 'Create Assignment')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.assignments.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Assignment</h1>
        <form action="{{ route('tutor.assignments.store') }}" method="POST" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Class</label><select name="class_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Due Date</label><input type="date" name="due_date" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Total Points</label><input type="number" name="total_points" value="100" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea name="description" rows="5" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea></div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-save mr-1"></i> Create Assignment</button>
        </form>
    </div>
</div>
@endsection
