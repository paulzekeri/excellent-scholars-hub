@extends('layouts.app')
@section('title', 'Assignments Oversight')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Assignments Oversight</h1>
        <p class="text-gray-600 mt-1">View all assignments and submissions across classes</p>
    </div>

    <!-- Filter by Class -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.oversight.assignments') }}" class="flex flex-wrap items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Filter by Class:</label>
            <select name="class_id" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ isset($selectedClass) && $selectedClass->id == $class->id ? 'selected' : '' }}>
                        {{ $class->name }} ({{ $class->subject }})
                    </option>
                @endforeach
            </select>
            @if(isset($selectedClass))
                <a href="{{ route('admin.oversight.assignments') }}" class="text-sm text-blue-600 hover:text-blue-800">Clear filter</a>
            @endif
        </form>
    </div>

    <!-- Assignments List -->
    <div class="space-y-6">
        @forelse($assignments as $assignment)
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ $assignment->title }}</h3>
                            <p class="text-sm text-gray-500">
                                <span class="inline-flex items-center mr-3"><i class="fas fa-chalkboard mr-1"></i> {{ $assignment->class->name ?? 'N/A' }}</span>
                                <span class="inline-flex items-center mr-3"><i class="fas fa-chalkboard-teacher mr-1"></i> {{ $assignment->tutor->user->name ?? 'N/A' }}</span>
                                <span class="inline-flex items-center"><i class="fas fa-clock mr-1"></i> Due: {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}</span>
                            </p>
                        </div>
                        <span class="px-3 py-1 text-sm rounded-full 
                            @if($assignment->status == 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($assignment->status) }}
                        </span>
                    </div>

                    @if($assignment->description)
                        <p class="text-gray-600 text-sm mb-4">{{ Str::limit($assignment->description, 200) }}</p>
                @if($assignment->hasImages())
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach($assignment->imagePaths() as $image)
                            <a href="{{ Storage::url($image) }}" target="_blank" rel="noopener" title="Open picture">
                                <img src="{{ Storage::url($image) }}" alt="Assignment picture" class="h-14 w-14 object-cover rounded-lg border border-gray-200">
                            </a>
                        @endforeach
                    </div>
                @endif
                    @endif

                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <a href="{{ route('admin.oversight.assignments.submissions', $assignment) }}"
                            class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                            <i class="fas fa-clipboard-check mr-2"></i> Review &amp; Grade Submissions ({{ $assignment->submissions->count() }})
                        </a>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Submissions ({{ $assignment->submissions->count() }})</h4>
                        @if($assignment->submissions->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead>
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Student</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Submitted</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Score</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($assignment->submissions as $submission)
                                            <tr>
                                                <td class="px-3 py-2 text-sm">{{ $submission->learner->user->name ?? 'N/A' }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-600">{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y') : '-' }}</td>
                                                <td class="px-3 py-2 text-sm">
                                                    @if($submission->score !== null)
                                                        <span class="font-medium">{{ $submission->score }}/{{ $assignment->total_points }}</span>
                                                    @else
                                                        <span class="text-gray-500">Not graded</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2">
                                                    <span class="px-2 py-0.5 text-xs rounded-full
                                                        @if($submission->status == 'graded') bg-green-100 text-green-800
                                                        @elseif($submission->status == 'submitted') bg-sun-light text-sun-ink
                                                        @else bg-gray-100 text-gray-800 @endif">
                                                        {{ ucfirst($submission->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic">No submissions yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-tasks text-5xl text-gray-300 mb-4 block"></i>
                <p class="text-gray-500">No assignments found.</p>
            </div>
        @endforelse

        @if(method_exists($assignments, 'links'))
            <div class="mt-4">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
