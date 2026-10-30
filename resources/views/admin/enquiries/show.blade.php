@extends('layouts.app')

@section('title', 'Enquiry ' . ($enquiry->reference ?: $enquiry->child_name))

@section('content')
@php
    $digits = preg_replace('/\D+/', '', (string) $enquiry->guardian_phone);
    $waNumber = \Illuminate\Support\Str::startsWith($digits, '0') ? '234' . substr($digits, 1) : $digits;
    $waMessage = rawurlencode('Hello ' . $enquiry->guardian_name . ', this is Excellent Scholars Hub about ' . $enquiry->child_name . '.');
@endphp
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.enquiries.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to admissions
    </a>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-3xl font-bold text-gray-800">{{ $enquiry->child_name }}</h1>
                <span class="px-2 py-1 text-xs rounded-full {{ $enquiry->statusBadge() }}">{{ $enquiry->statusLabel() }}</span>
            </div>
            <p class="text-gray-600 mt-1">
                <span class="font-mono text-sm">{{ $enquiry->reference ?: 'reference pending' }}</span>
                · {{ $enquiry->sourceLabel() }}
                · captured {{ $enquiry->created_at->format('M j, Y') }}
            </p>
            @if($enquiry->converted_learner_id && $enquiry->convertedLearner && $enquiry->convertedLearner->user)
                <p class="text-sm text-green-700 mt-1">
                    <i class="fas fa-user-graduate mr-1"></i> Enrolled as
                    <strong>{{ $enquiry->convertedLearner->user->name }}</strong>
                    (username {{ $enquiry->convertedLearner->user->username ?: '—' }})
                    @if($enquiry->converted_at) on {{ $enquiry->converted_at->format('M j, Y') }} @endif
                </p>
            @endif
        </div>
        <a href="{{ route('admin.enquiries.edit', $enquiry) }}" class="inline-flex items-center justify-center bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50">
            <i class="fas fa-edit mr-2"></i> Edit details
        </a>
        @if($enquiry->converted_learner_id && $enquiry->convertedLearner && $enquiry->convertedLearner->user)
            <a href="{{ route('admin.users.edit', $enquiry->convertedLearner->user) }}" class="inline-flex items-center justify-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-user-graduate mr-2"></i> View learner
            </a>
        @elseif($enquiry->status === \App\Models\Enquiry::STATUS_PLACED)
            <a href="{{ route('admin.enquiries.convert', $enquiry) }}" class="inline-flex items-center justify-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-user-graduate mr-2"></i> Convert to learner
            </a>
        @endif
    </div>

    @if($enquiry->declined_reason)
        <div class="bg-orange-50 border-l-4 border-orange-500 text-orange-900 p-4 rounded-lg mb-6">
            <p class="font-semibold">Declined</p>
            <p class="text-sm">{{ $enquiry->declined_reason }}</p>
        </div>
    @endif

    @if(session('conversion_credentials'))
        <div class="bg-white rounded-xl shadow-md border-l-4 border-orange-500 p-5 mb-6">
            <p class="font-semibold text-gray-800"><i class="fas fa-id-card text-orange-700 mr-1"></i> Login details to hand over</p>
            <p class="text-sm text-gray-600 mt-1">
                Email delivery is not configured on this installation, so share these privately. Each person chooses
                their own password at first sign-in.
            </p>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                @foreach(session('conversion_credentials') as $account)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                        <span class="block text-xs uppercase tracking-wide text-gray-500">{{ $account['name'] }}</span>
                        <span class="font-mono text-gray-800">{{ $account['username'] }}</span>
                        <span class="block font-mono text-gray-800">{{ $account['password'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-address-book text-blue-600 mr-2"></i>Parent / guardian
                </h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Name</dt>
                        <dd class="text-gray-800 font-medium">{{ $enquiry->guardian_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Phone</dt>
                        <dd class="text-gray-800">
                            <a href="tel:{{ $enquiry->guardian_phone }}" class="hover:text-blue-700">{{ $enquiry->guardian_phone }}</a>
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Email</dt>
                        <dd class="text-gray-800">
                            @if($enquiry->guardian_email)
                                <a href="mailto:{{ $enquiry->guardian_email }}" class="hover:text-blue-700">{{ $enquiry->guardian_email }}</a>
                            @else
                                <span class="text-amber-600">Not captured yet — needed before conversion</span>
                            @endif
                        </dd>
                    </div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="https://wa.me/{{ $waNumber }}?text={{ $waMessage }}" target="_blank" rel="noopener"
                        class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
                        <i class="fab fa-whatsapp mr-2"></i> WhatsApp {{ $enquiry->guardian_name }}
                    </a>
                    @if($enquiry->guardian_email)
                        <a href="mailto:{{ $enquiry->guardian_email }}?subject={{ rawurlencode('Excellent Scholars Hub — ' . $enquiry->child_name) }}"
                            class="inline-flex items-center border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                            <i class="fas fa-envelope mr-2"></i> Email
                        </a>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-child text-orange-700 mr-2"></i>The child
                </h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Class / year group</dt>
                        <dd class="text-gray-800">{{ $enquiry->child_grade_level ?: 'Not recorded' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Date of birth</dt>
                        <dd class="text-gray-800">{{ optional($enquiry->child_dob)->format('M j, Y') ?: 'Not recorded' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Owner</dt>
                        <dd class="text-gray-800">{{ optional($enquiry->assignedTo)->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Captured by</dt>
                        <dd class="text-gray-800">{{ optional($enquiry->createdBy)->name ?? 'Unknown' }}</dd>
                    </div>
                </dl>
                @if($enquiry->notes)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Notes</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $enquiry->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-video text-blue-600 mr-2"></i>Assessment calls
                </h2>

                @if($enquiry->assessments->isEmpty())
                    <p class="text-sm text-gray-500">No assessment booked yet.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($enquiry->assessments as $assessment)
                            <li class="rounded-lg border border-gray-200 p-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $assessment->scheduleLabel() }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $assessment->platformLabel() }}
                                            · {{ optional(optional($assessment->tutor)->user)->name ?? 'No tutor' }}
                                            · <span class="px-2 py-0.5 rounded-full {{ $assessment->statusBadge() }}">{{ $assessment->statusLabel() }}</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.assessments.show', $assessment) }}" class="text-xs text-blue-700 hover:text-blue-900">Open / record result</a>
                                        @if($assessment->joinUrl())
                                            <a href="{{ $assessment->joinUrl() }}" target="_blank" rel="noopener"
                                                class="inline-flex items-center bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-blue-700">
                                                <i class="fas fa-video mr-1"></i> Join
                                            </a>
                                        @endif
                                        @if($assessment->isScheduled())
                                            <form action="{{ route('admin.assessments.cancel', $assessment) }}" method="POST" onsubmit="return confirm('Cancel this assessment?')">
                                                @csrf
                                                <button type="submit" class="text-xs text-orange-700 hover:text-orange-900">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                @if($assessment->meeting_link)
                                    <p class="mt-2 text-xs text-gray-500 break-all">{{ $assessment->meeting_link }}</p>
                                    <a href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode('Hello ' . $enquiry->guardian_name . ', the assessment for ' . $enquiry->child_name . ' is booked for ' . $assessment->scheduleLabel() . ' (' . $assessment->platformLabel() . '). Join here: ' . $assessment->meeting_link) }}"
                                        target="_blank" rel="noopener"
                                        class="mt-2 inline-flex items-center bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-green-700">
                                        <i class="fab fa-whatsapp mr-1"></i> Send the link on WhatsApp
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($canBookAssessment)
                    <form action="{{ route('admin.enquiries.assessments.store', $enquiry) }}" method="POST"
                        class="mt-6 border-t border-gray-100 pt-4 space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="tutor_id" class="block text-sm font-medium text-gray-700">Tutor who will assess</label>
                                <select id="tutor_id" name="tutor_id" required
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Choose a tutor</option>
                                    @foreach($tutors as $tutor)
                                        <option value="{{ $tutor->id }}" {{ (string) old('tutor_id') === (string) $tutor->id ? 'selected' : '' }}>
                                            {{ optional($tutor->user)->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700">Date and time</label>
                                <input id="scheduled_at" type="datetime-local" name="scheduled_at" required value="{{ old('scheduled_at') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="duration_minutes" class="block text-sm font-medium text-gray-700">Minutes</label>
                                <input id="duration_minutes" type="number" name="duration_minutes" min="15" max="120"
                                    value="{{ old('duration_minutes', 30) }}" required
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="platform" class="block text-sm font-medium text-gray-700">Platform</label>
                                <select id="platform" name="platform" required
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    @foreach(\App\Models\AssessmentSession::PLATFORMS as $value => $label)
                                        <option value="{{ $value }}" {{ old('platform', \App\Models\AssessmentSession::PLATFORM_GOOGLE_MEET) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="meeting_link" class="block text-sm font-medium text-gray-700">Google Meet / Zoom link</label>
                                <input id="meeting_link" type="url" name="meeting_link" value="{{ old('meeting_link') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="https://meet.google.com/...">
                                <p class="text-xs text-gray-500 mt-1">Emailed to the guardian; the WhatsApp button above also works once the call is booked.</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="guardian_email" class="block text-sm font-medium text-gray-700">Guardian email for the invite</label>
                                <input id="guardian_email" type="email" name="guardian_email" required
                                    value="{{ old('guardian_email', $enquiry->guardian_email) }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="assessment_notes" class="block text-sm font-medium text-gray-700">Notes for the tutor (optional)</label>
                                <textarea id="assessment_notes" name="notes" rows="2"
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Anything the assessor should know">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-video mr-1"></i> Book assessment and email the link
                        </button>
                    </form>
                @elseif($enquiry->status === \App\Models\Enquiry::STATUS_NEW)
                    <p class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                        Mark the enquiry as <strong>Contacted</strong> (status panel on the right), then book the assessment here.
                    </p>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-clock-rotate-left text-orange-700 mr-2"></i>Timeline
                </h2>
                @if($enquiry->timeline->isEmpty())
                    <p class="text-sm text-gray-500">Nothing recorded yet. Log the first call or message so the next person has context.</p>
                @else
                    <ol class="space-y-4">
                        @foreach($enquiry->timeline as $entry)
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-500"></span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs rounded-full {{ $entry->typeBadge() }}">{{ $entry->typeLabel() }}</span>
                                        <span class="text-xs text-gray-500">{{ $entry->created_at->format('M j, Y H:i') }}</span>
                                        <span class="text-xs text-gray-400">· {{ optional($entry->user)->name ?? 'System' }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $entry->body }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">
                    <i class="fas fa-forward-step text-blue-600 mr-2"></i>Move the pipeline
                </h2>

                @if(count($nextStatuses))
                    <form action="{{ route('admin.enquiries.status', $enquiry) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Next status</label>
                            <select id="status" name="status" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Choose…</option>
                                @foreach($nextStatuses as $value => $label)
                                    <option value="{{ $value }}" {{ old('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="declined_reason" class="block text-sm font-medium text-gray-700">Reason (required when declining)</label>
                            <textarea id="declined_reason" name="declined_reason" rows="2"
                                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g. Parent chose another school">{{ old('declined_reason') }}</textarea>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Update status
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-600">This enquiry is closed — archived enquiries cannot be moved.</p>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">
                    <i class="fas fa-pen text-orange-700 mr-2"></i>Add to the timeline
                </h2>
                <form action="{{ route('admin.enquiries.notes.store', $enquiry) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">What happened?</label>
                        <select id="type" name="type" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            @foreach(\App\Models\EnquiryNote::MANUAL_TYPES as $value => $label)
                                <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700">Details</label>
                        <textarea id="body" name="body" rows="3" required maxlength="2000"
                            class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="What was discussed?">{{ old('body') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">
                        Add entry
                    </button>
                </form>
            </div>

            <div class="bg-gray-50 rounded-xl border border-gray-200 p-5 text-xs text-gray-600">
                <p><strong>Tip:</strong> the WhatsApp button opens the guardian's chat with a message already drafted, so the first reply is one tap away.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const platform = document.getElementById('platform');
        const link = document.getElementById('meeting_link');

        if (!platform || !link) return;

        function toggleLinkRequirement() {
            const online = ['google_meet', 'zoom'].indexOf(platform.value) !== -1;
            link.required = online;
        }

        platform.addEventListener('change', toggleLinkRequirement);
        toggleLinkRequirement();
    });
</script>
@endpush
