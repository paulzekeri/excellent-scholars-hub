@extends('layouts.app')
@section('title', 'Review Submissions')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.oversight.assignments') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to Assignments Oversight
    </a>

    <div class="bg-white rounded-xl shadow-md p-8 mb-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $assignment->title }}</h1>
                <p class="text-sm text-gray-500 mt-2">
                    <span class="inline-flex items-center mr-4"><i class="fas fa-chalkboard mr-1"></i> {{ $assignment->class->name ?? 'N/A' }}</span>
                    <span class="inline-flex items-center mr-4"><i class="fas fa-chalkboard-teacher mr-1"></i> {{ $assignment->tutor->user->name ?? 'N/A' }}</span>
                    <span class="inline-flex items-center"><i class="fas fa-clock mr-1"></i> Due: {{ $assignment->due_date ? $assignment->due_date->format('M d, Y') : 'N/A' }}</span>
                </p>
            </div>
            <span class="px-3 py-1 text-sm rounded-full self-start
                @if($assignment->status == 'active') bg-green-100 text-green-800
                @else bg-gray-100 text-gray-800 @endif">
                {{ ucfirst($assignment->status) }}
            </span>
        </div>

        @if($assignment->description)
            <p class="text-gray-600 text-sm mb-2">{{ $assignment->description }}</p>
        @endif

        <p class="text-sm text-gray-500">
            Total Points: <strong>{{ $assignment->total_points }}</strong> &middot;
            Submissions: <strong>{{ $submissions->count() }}</strong> &middot;
            Graded: <strong>{{ $submissions->where('status', 'graded')->count() }}</strong>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Learner Submissions</h2>
            <p class="text-sm text-gray-500 mt-1">Review submitted work, download files and grade on behalf of the tutor.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Submitted File</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Submitted At</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Score</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($submissions as $submission)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">{{ $submission->learner->user->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($submission->file_path)
                                <a href="{{ route('admin.oversight.submissions.download', $submission) }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            @else
                                <span class="text-gray-500">No file</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm max-w-xs">
                            @if($submission->content)
                                <p class="text-gray-600 truncate" title="{{ $submission->content }}">{{ $submission->content }}</p>
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : $submission->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($submission->score !== null)
                                <span class="font-medium">{{ $submission->score }}/{{ $assignment->total_points }}</span>
                            @else
                                <span class="text-gray-500">Not graded</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($submission->status == 'graded') bg-green-100 text-green-800
                                @elseif($submission->status == 'submitted') bg-sun-light text-sun-ink
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($submission->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($submission->score === null)
                                <form action="{{ route('admin.oversight.submissions.grade', $submission) }}" method="POST" class="flex items-center space-x-2">
                                    @csrf
                                    <input type="number" name="score" min="0" max="{{ $assignment->total_points }}" required
                                        class="w-20 border border-gray-300 rounded px-2 py-1 text-sm" placeholder="Score">
                                    <input type="text" name="feedback" class="border border-gray-300 rounded px-2 py-1 text-sm flex-1" placeholder="Feedback">
                                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Grade</button>
                                </form>
                            @else
                                <div class="text-sm">
                                    <span class="text-green-600 font-medium">Score: {{ $submission->score }}/{{ $assignment->total_points }}</span>
                                    @if($submission->feedback)
                                        <p class="text-gray-500 text-xs mt-1">Feedback: {{ $submission->feedback }}</p>
                                    @endif
                                    <form action="{{ route('admin.oversight.submissions.grade', $submission) }}" method="POST" class="flex items-center space-x-2 mt-2">
                                        @csrf
                                        <input type="number" name="score" min="0" max="{{ $assignment->total_points }}" required
                                            value="{{ $submission->score }}" class="w-20 border border-gray-300 rounded px-2 py-1 text-sm">
                                        <input type="text" name="feedback" value="{{ $submission->feedback }}"
                                            class="border border-gray-300 rounded px-2 py-1 text-sm flex-1" placeholder="Feedback">
                                        <button type="submit" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">Update</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No submissions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

