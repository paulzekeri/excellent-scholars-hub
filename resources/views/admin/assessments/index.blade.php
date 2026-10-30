@extends('layouts.app')

@section('title', 'Assessments')

@section('content')
@php
    $tabs = [
        'today' => ['label' => 'Today', 'count' => $counts['today']],
        'upcoming' => ['label' => 'Upcoming', 'count' => $counts['upcoming']],
        'past' => ['label' => 'Past', 'count' => $counts['past']],
        'all' => ['label' => 'All', 'count' => $counts['all']],
    ];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Assessments</h1>
            <p class="text-gray-600 mt-1">Video calls the school has set up for prospective learners. Everyone joins from the same Meet/Zoom link.</p>
        </div>
        <a href="{{ route('admin.enquiries.index') }}" class="inline-flex items-center justify-center bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-50">
            <i class="fas fa-user-plus mr-2"></i> Admissions pipeline
        </a>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($tabs as $key => $tab)
            <a href="{{ route('admin.assessments.index', array_filter(['scope' => $key, 'tutor_id' => $tutorId])) }}"
                class="px-3 py-2 rounded-lg text-sm font-medium border transition {{ $scope === $key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                {{ $tab['label'] }} <span class="ml-1 text-xs {{ $scope === $key ? 'text-blue-100' : 'text-gray-400' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach

        <form method="GET" action="{{ route('admin.assessments.index') }}" class="ml-auto flex gap-2">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <select name="tutor_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All tutors</option>
                @foreach($tutors as $tutor)
                    <option value="{{ $tutor->id }}" {{ (string) $tutorId === (string) $tutor->id ? 'selected' : '' }}>{{ optional($tutor->user)->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded-lg text-sm hover:bg-gray-900"><i class="fas fa-filter"></i></button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Child</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guardian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tutor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($assessments as $assessment)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-800">{{ $assessment->scheduleLabel() }}</span>
                                @if($assessment->isToday())
                                    <span class="ml-2 px-2 py-0.5 text-[10px] rounded-full bg-yellow-200 text-yellow-900">Today</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($assessment->enquiry)
                                    <a href="{{ route('admin.enquiries.show', $assessment->enquiry) }}" class="font-medium text-gray-800 hover:text-blue-700">{{ $assessment->enquiry->child_name }}</a>
                                    <span class="block text-xs text-gray-500 font-mono">{{ $assessment->enquiry->reference }}</span>
                                @else
                                    <span class="text-gray-400">Enquiry deleted</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ optional($assessment->enquiry)->guardian_name }}
                                @if(optional($assessment->enquiry)->guardian_phone)
                                    <a href="tel:{{ $assessment->enquiry->guardian_phone }}" class="block text-xs text-gray-500 hover:text-blue-700">{{ $assessment->enquiry->guardian_phone }}</a>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ optional(optional($assessment->tutor)->user)->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $assessment->platformBadge() }}">{{ $assessment->platformLabel() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $assessment->statusBadge() }}">{{ $assessment->statusLabel() }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.assessments.show', $assessment) }}" class="text-xs text-blue-700 hover:text-blue-900 mr-3">Open</a>
                                @if($assessment->joinUrl())
                                    <a href="{{ $assessment->joinUrl() }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-blue-700">
                                        <i class="fas fa-video mr-1"></i> Join
                                    </a>
                                @elseif($assessment->isOnline())
                                    <span class="text-xs text-amber-600">No link</span>
                                @else
                                    <span class="text-xs text-gray-500">{{ $assessment->platformLabel() }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-video text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-600">No assessments in this view.</p>
                                <a href="{{ route('admin.enquiries.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">Book one from an enquiry</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assessments->hasPages())
            <div class="p-4 border-t">{{ $assessments->links() }}</div>
        @endif
    </div>
</div>
@endsection
