@extends('layouts.app')
@section('title', 'My Assignments')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">My Assignments</h1>
    <p class="text-gray-600 mb-6">View and submit your assignments</p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($assignments as $assignment)
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="font-semibold text-lg text-gray-800">{{ $assignment->title }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $assignment->class->name }}</p>
                    <p class="text-xs text-gray-400 mt-1">Due: {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }} | Points: {{ $assignment->total_points }}</p>
                </div>
                <span class="text-3xl @if($assignment->submissions->where('learner_id', auth()->user()->learner->id)->first()) text-green-400 @else text-orange-400 @endif"><i class="fas @if($assignment->submissions->where('learner_id', auth()->user()->learner->id)->first()) fa-check-circle @else fa-clock @endif"></i></span>
            </div>
            @if($assignment->description)<p class="text-sm text-gray-600 mt-3">{{ Str::limit($assignment->description, 100) }}</p>@endif
            @if($assignment->hasImages())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($assignment->imagePaths() as $image)
                        <a href="{{ Storage::url($image) }}" target="_blank" rel="noopener" title="Open picture">
                            <img src="{{ Storage::url($image) }}" alt="{{ $assignment->title }} picture" class="h-16 w-16 object-cover rounded-lg border border-gray-200 hover:ring-2 hover:ring-blue-400">
                        </a>
                    @endforeach
                </div>
            @endif
            @php $submission = $assignment->submissions->where('learner_id', auth()->user()->learner->id)->first(); @endphp
            @if($submission)
                <div class="mt-4 p-3 bg-green-50 rounded-lg"><p class="text-sm text-green-700"><i class="fas fa-check-circle mr-1"></i> Submitted {{ $submission->created_at->format('M d, Y') }}</p>
                @if($submission->score)<p class="text-sm font-semibold text-green-800 mt-1">Score: {{ $submission->score }}/{{ $assignment->total_points }}</p>@endif
                @if($submission->feedback)<p class="text-xs text-gray-600 mt-1">Feedback: {{ $submission->feedback }}</p>@endif
                </div>
            @else
                <a href="{{ route('learner.assignments.submit', $assignment) }}" class="mt-4 inline-flex items-center text-blue-700 hover:text-orange-700"><i class="fas fa-upload mr-1"></i> Submit Assignment</a>
            @endif
        </div>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500"><i class="fas fa-tasks text-4xl mb-4 text-gray-300"></i><p>No assignments assigned yet.</p></div>
        @endforelse
    </div>
</div>
@endsection
