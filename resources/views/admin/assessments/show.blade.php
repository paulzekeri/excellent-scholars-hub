@extends('layouts.app')

@section('title', 'Assessment for ' . optional($assessment->enquiry)->child_name)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.assessments.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to assessments
    </a>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ optional($assessment->enquiry)->child_name }}</h1>
            <p class="text-gray-600 mt-1">
                {{ $assessment->scheduleLabel() }} · {{ $assessment->platformLabel() }}
                · <span class="px-2 py-0.5 text-xs rounded-full {{ $assessment->statusBadge() }}">{{ $assessment->statusLabel() }}</span>
            </p>
            <p class="text-sm text-gray-500 mt-1">
                Guardian: {{ optional($assessment->enquiry)->guardian_name }}
                @if(optional($assessment->enquiry)->guardian_phone)
                    · <a href="tel:{{ $assessment->enquiry->guardian_phone }}" class="text-blue-700">{{ $assessment->enquiry->guardian_phone }}</a>
                @endif
                · Tutor: {{ optional(optional($assessment->tutor)->user)->name ?? '—' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($assessment->enquiry)
                <a href="{{ route('admin.enquiries.show', $assessment->enquiry) }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-user mr-2"></i> Open enquiry
                </a>
            @endif
            @if($assessment->joinUrl())
                <a href="{{ $assessment->joinUrl() }}" target="_blank" rel="noopener" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-video mr-2"></i> Join call
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if($assessment->status === \App\Models\AssessmentSession::STATUS_COMPLETED)
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-clipboard-check text-green-600 mr-2"></i>Recorded result</h2>
                    <p class="text-sm text-gray-600 mb-2">
                        Outcome: <span class="px-2 py-0.5 text-xs rounded-full {{ $assessment->outcomeBadge() }}">{{ $assessment->outcomeLabel() }}</span>
                        · Recorded by {{ optional($assessment->conductedBy)->name ?? 'unknown' }}
                        @if($assessment->completed_at) on {{ $assessment->completed_at->format('M j, Y g:i a') }} @endif
                    </p>
                    @if($assessment->strengths)
                        <p class="text-sm text-gray-700 mt-3"><strong>Strengths:</strong> {{ $assessment->strengths }}</p>
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
                    @if($assessment->notes)
                        <p class="text-sm text-gray-700 mt-2"><strong>Internal notes:</strong> {{ $assessment->notes }}</p>
                    @endif
                    @if($assessment->attachment_path)
                        <a href="{{ route('admin.assessments.report', $assessment) }}" class="inline-flex items-center mt-3 text-sm text-blue-700 hover:text-blue-900">
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
                    @include('partials.assessment-result-form', ['action' => route('admin.assessments.result', $assessment), 'assessment' => $assessment, 'classes' => $classes])
                @else
                    <p class="text-sm text-gray-600">
                        This assessment is {{ strtolower($assessment->statusLabel()) }} and cannot be edited. Book a new assessment from the enquiry.
                    </p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-md p-6 text-sm text-gray-600 space-y-2">
                <p><strong>Reference:</strong> {{ optional($assessment->enquiry)->reference }}</p>
                <p><strong>Enquiry status:</strong> {{ optional($assessment->enquiry)->statusLabel() }}</p>
                <p><strong>Duration:</strong> {{ $assessment->duration_minutes }} minutes</p>
                @if($assessment->meeting_link)
                    <p class="break-all"><strong>Link:</strong> {{ $assessment->meeting_link }}</p>
                @endif
            </div>

            @if($assessment->isScheduled())
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Need to call it off?</h2>
                    <form action="{{ route('admin.assessments.cancel', $assessment) }}" method="POST" onsubmit="return confirm('Cancel this assessment?')">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                            <i class="fas fa-ban mr-1"></i> Cancel assessment
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
