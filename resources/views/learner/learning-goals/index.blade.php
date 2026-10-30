@extends('layouts.app')

@section('title', 'My Learning Goals')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">My Learning Goals</h1>
            <p class="text-gray-600 mt-1">Goals your school shared with you — your own goals and goals for your classes.</p>
        </div>
        <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center justify-center bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Dashboard
        </a>
    </div>

    <div class="space-y-4">
        @forelse($goals as $goal)
            <div class="bg-white rounded-xl shadow-md p-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-gray-800">{{ $goal->title }}</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $goal->schoolClass->name ?? 'General goal' }}
                            @if($goal->term) · {{ $goal->term }} @endif
                            @if($goal->tutor && $goal->tutor->user) · set by {{ $goal->tutor->user->name }} @endif
                        </p>
                    </div>
                    @if($goal->shared_at)
                        <span class="shrink-0 text-xs text-gray-500">Shared {{ $goal->shared_at->format('M j, Y') }}</span>
                    @endif
                </div>

                @if($goal->target)
                    <p class="mt-3 text-sm text-gray-700"><strong>Target:</strong> {{ $goal->target }}</p>
                @endif

                @if($goal->description)
                    <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $goal->description }}</p>
                @endif

                @if($goal->file_path)
                    <a href="{{ route('learner.learning-goals.download', $goal) }}"
                        class="mt-3 inline-flex items-center text-sm text-orange-700 hover:text-orange-800">
                        <i class="fas fa-download mr-1"></i> Download attachment
                    </a>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-md p-10 text-center">
                <i class="fas fa-bullseye text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-600">No goals have been shared with you yet.</p>
                <p class="text-sm text-gray-500 mt-1">Your school shares them here so you know what to aim for.</p>
            </div>
        @endforelse
    </div>

    @if($goals->hasPages())
        <div class="mt-6">{{ $goals->links() }}</div>
    @endif
</div>
@endsection
