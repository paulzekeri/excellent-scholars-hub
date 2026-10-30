@extends('layouts.app')

@section('title', 'Admissions')

@section('content')
@php
    $tabs = [
        'open' => ['label' => 'Open', 'count' => $counts['open']],
        'all' => ['label' => 'All', 'count' => $counts['all']],
        'new' => ['label' => 'New', 'count' => $counts['new']],
        'contacted' => ['label' => 'Contacted', 'count' => $counts['contacted']],
        'declined' => ['label' => 'Declined', 'count' => $counts['declined']],
        'archived' => ['label' => 'Archived', 'count' => $counts['archived']],
    ];
    $filters = array_filter(['search' => $search, 'source' => $source], function ($value) {
        return $value !== '' && $value !== null;
    });
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Admissions</h1>
            <p class="text-gray-600 mt-1">Prospective learners — captured from WhatsApp and phone calls, before any account exists.</p>
        </div>
        <a href="{{ route('admin.enquiries.create') }}" class="inline-flex items-center justify-center bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i> Capture enquiry
        </a>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($tabs as $key => $tab)
            <a href="{{ route('admin.enquiries.index', array_merge($filters, ['status' => $key])) }}"
                class="px-3 py-2 rounded-lg text-sm font-medium border transition {{ $status === $key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                {{ $tab['label'] }}
                <span class="ml-1 text-xs {{ $status === $key ? 'text-blue-100' : 'text-gray-400' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.enquiries.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <label for="search" class="sr-only">Search</label>
                <input id="search" type="search" name="search" value="{{ $search }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Child, guardian, phone, email or reference">
            </div>
            <div class="flex gap-2">
                <select name="source" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Any source</option>
                    @foreach(\App\Models\Enquiry::SOURCES as $value => $label)
                        <option value="{{ $value }}" {{ $source === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2.5 rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Child</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guardian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($enquiries as $enquiry)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-mono text-xs text-blue-700 hover:text-blue-900">
                                    {{ $enquiry->reference ?: '—' }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-medium text-gray-800 hover:text-blue-700">{{ $enquiry->child_name }}</a>
                                @if($enquiry->child_grade_level)
                                    <span class="block text-xs text-gray-500">{{ $enquiry->child_grade_level }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $enquiry->guardian_name }}
                                <a href="tel:{{ $enquiry->guardian_phone }}" class="block text-xs text-gray-500 hover:text-blue-700">{{ $enquiry->guardian_phone }}</a>
                                @if($enquiry->guardian_email)
                                    <span class="block text-xs text-gray-400">{{ $enquiry->guardian_email }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">{{ $enquiry->sourceLabel() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $enquiry->statusBadge() }}">{{ $enquiry->statusLabel() }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ optional($enquiry->assignedTo)->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ $enquiry->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="text-blue-600 hover:text-blue-800">
                                    Open <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <i class="fas fa-user-plus text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-600">No enquiries match this view yet.</p>
                                <a href="{{ route('admin.enquiries.create') }}" class="text-blue-600 hover:text-blue-800 text-sm">Capture the first enquiry</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($enquiries->hasPages())
            <div class="p-4 border-t">{{ $enquiries->links() }}</div>
        @endif
    </div>
</div>
@endsection
