@extends('layouts.app')
@section('title', 'Create Assignment')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.assignments.index') }}" class="text-blue-700 hover:text-blue-900 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Assignment</h1>
        <form action="{{ route('tutor.assignments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700">Title</label><input type="text" name="title" value="{{ old('title') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Class</label><select name="class_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Due Date</label><input type="date" name="due_date" value="{{ old('due_date') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700">Total Points</label><input type="number" name="total_points" value="{{ old('total_points', 100) }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Description</label><textarea name="description" rows="5" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Pictures (optional)</label>
                <input type="file" name="images[]" accept="image/*" multiple class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Up to 6 images (JPG, PNG or WebP, 2 MB each). They appear with the assignment for learners, parents and administrators.</p>
            </div>
            <button type="submit" class="bg-blue-700 text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-save mr-1"></i> Create Assignment</button>
        </form>
    </div>
</div>
@endsection
