@extends('layouts.app')

@section('title', 'My Assessments')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">My Assessments</h1>
        <p class="text-gray-600 mt-1">Assessment calls the school has assigned to you. Join from the same link the parent receives.</p>
    </div>

    @if(! $tutor)
        <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-lg">
            Your account has no tutor profile yet, so no assessments can be assigned to you. Ask an administrator to update your role.
        </div>
    @else
        @php
            $sections = [
                ['title' => 'Today', 'items' => $today, 'empty' => 'No assessment calls today.'],
                ['title' => 'Upcoming', 'items' => $upcoming, 'empty' => 'Nothing scheduled yet.'],
                ['title' => 'History', 'items' => $history, 'empty' => 'No past assessments.'],
            ];
        @endphp

        <div class="space-y-6">
            @foreach($sections as $section)
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ $section['title'] }}</h2>

                    @if($section['items']->isEmpty())
                        <p class="text-sm text-gray-500">{{ $section['empty'] }}</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($section['items'] as $assessment)
                                <li class="border border-gray-200 rounded-lg p-4 {{ $assessment->isToday() && $assessment->isScheduled() ? 'border-orange-300 bg-orange-50/40' : '' }}">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-gray-800">{{ $assessment->scheduleLabel() }}</p>
                                            <p class="text-sm text-gray-600 mt-0.5">
                                                {{ optional($assessment->enquiry)->child_name ?? 'Deleted enquiry' }}
                                                @if(optional($assessment->enquiry)->guardian_name)
                                                    · guardian {{ $assessment->enquiry->guardian_name }}
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $assessment->platformLabel() }} · {{ $assessment->statusLabel() }}
                                                @if(optional($assessment->enquiry)->reference)
                                                    · {{ $assessment->enquiry->reference }}
                                                @endif
                                            </p>
                                            @if(optional($assessment->enquiry)->guardian_phone)
                                                <a href="tel:{{ $assessment->enquiry->guardian_phone }}" class="text-xs text-blue-700 hover:text-blue-900">
                                                    <i class="fas fa-phone mr-1"></i>{{ $assessment->enquiry->guardian_phone }}
                                                </a>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if($assessment->joinUrl())
                                                <a href="{{ $assessment->joinUrl() }}" target="_blank" rel="noopener"
                                                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                                                    <i class="fas fa-video mr-2"></i> Join call
                                                </a>
                                            @elseif($assessment->isOnline())
                                                <span class="text-xs text-amber-600">The school has not added the link yet.</span>
                                            @else
                                                <span class="text-xs text-gray-500">{{ $assessment->platformLabel() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($assessment->meeting_link)
                                        <p class="mt-2 text-xs text-gray-500 break-all">{{ $assessment->meeting_link }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
