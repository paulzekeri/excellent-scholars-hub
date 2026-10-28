@extends('layouts.app')
@section('title', 'Submit Assignment')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('learner.assignments') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $assignment->title }}</h1>
        <p class="text-gray-600 mb-6">{{ $assignment->description }}</p>
        <div class="bg-gray-50 p-4 rounded-lg mb-6"><p class="text-sm text-gray-600">Due: {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }} | Total Points: {{ $assignment->total_points }}</p></div>
        <form action="{{ route('learner.assignments.submit.store', $assignment) }}" method="POST" enctype="multipart/form-data" class="space-y-6">@csrf
            <div><label class="block text-sm font-medium text-gray-700">Your Submission (Upload File)</label><input type="file" name="file" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700">Notes (optional)</label><textarea name="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"></textarea></div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-upload mr-1"></i> Submit</button>
        </form>
    </div>
</div>
@endsection
