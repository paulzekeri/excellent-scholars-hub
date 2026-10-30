@extends('layouts.app')

@section('title', 'Assessment for ' . optional($assessment->enquiry)->child_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.assessments.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to my assessments
    </a>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ optional($assessment->enquiry)->child_name }}</h1>
            <p class="text-gray-600 mt-1">
                {{ $assessment->scheduleLabel() }} · {{ $assessment->platformLabel() }}
                · <span class="px-2 py-0.5 text-xs rounded-full {{ $assessment->statusBadge() }}">{{ $assessment->statusLabel() }}</span>
            </p>
            <p class="text-sm text-gray-500 mt-1">
                Guardian: {{ optional($assessment->enquiry)->guardian_name }}
                @if(optional($assessment->enquiry)->guardian_phone)
                    · <a href="tel:{{ $assessment->enquiry->guardian_phone }}" class="text-blue-700">{{ $assessment->enquiry->guardian_phone }}</a>
                @endif
                · {{ optional($assessment->enquiry)->reference }}
            </p>
        </div>
        @if($assessment->joinUrl())
            <a href="{{ $assessment->joinUrl() }}" target="_blank" rel="noopener" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-video mr-2"></i> Join call
            </a>
        @endif
    </div>

    @if($assessment->status === \App\Models\AssessmentSession::STATUS_COMPLETED)
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-clipboard-check text-green-600 mr-2"></i>Recorded result</h2>
            <p class="text-sm text-gray-600">
                Outcome: <span class="px-2 py-0.5 text-xs rounded-full {{ $assessment->outcomeBadge() }}">{{ $assessment->outcomeLabel() }}</span>
                @if($assessment->completed_at) · {{ $assessment->completed_at->format('M j, Y g:i a') }} @endif
            </p>
            @if($assessment->strengths)
                <p class="text-sm text-gray-700 mt-2"><strong>Strengths:</strong> {{ $assessment->strengths }}</p>
            @endif
            @if($assessment->focus_areas)
                <p class="text-sm text-gray-700 mt-2"><strong>Focus areas:</strong> {{ $assessment->focus_areas }}</p>
            @endif
            @if($assessment->recommended_level)
                <p class="text-sm text-gray-700 mt-2"><strong>Recommended level:</strong> {{ $assessment->recommended_level }}</p>
            @endif
            @if($assessment->recommendedClass)
                <p class="text-sm text-gray-700 mt-2"><strong>Recommended class:</strong> {{ $assessment->recommendedClass->name }}</p>
            @endif
            @if($assessment->attachment_path)
                <a href="{{ route('tutor.assessments.report', $assessment) }}" class="inline-flex items-center mt-3 text-sm text-blue-700 hover:text-blue-900">
                    <i class="fas fa-file-download mr-2"></i> Download report
                </a>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">
            <i class="fas fa-pen-to-square text-blue-600 mr-2"></i>{{ $assessment->status === \App\Models\AssessmentSession::STATUS_COMPLETED ? 'Update the result' : 'Record the result' }}
        </h2>

        @if($assessment->canRecordResult())
            @include('partials.assessment-result-form', ['action' => route('tutor.assessments.result', $assessment), 'assessment' => $assessment, 'classes' => $classes])
        @else
            <p class="text-sm text-gray-600">
                This assessment is {{ strtolower($assessment->statusLabel()) }} and cannot be edited. The school can book a new one from the enquiry.
            </p>
        @endif
    </div>
</div>
@endsection
