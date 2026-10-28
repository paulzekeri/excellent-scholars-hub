@extends('layouts.app')
@section('title', 'Submissions')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.assignments.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $assignment->title }}</h1>
        <p class="text-gray-600 mb-2">{{ $assignment->description }}</p>
        <p class="text-sm text-gray-500 mb-6">Total Points: {{ $assignment->total_points }} | Due: {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}</p>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted File</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted At</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($submissions as $submission)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">{{ $submission->learner->user->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($submission->file_path)
                                <a href="{{ route('tutor.submissions.download', $submission) }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            @else
                                <span class="text-gray-400">No file</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm max-w-xs">
                            @if($submission->content)
                                <p class="text-gray-600 truncate" title="{{ $submission->content }}">{{ $submission->content }}</p>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $submission->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $submission->score ?? 'Not graded' }}/{{ $assignment->total_points }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full @if($submission->status == 'graded') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">
                                {{ ucfirst($submission->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if(!$submission->score)
                            <form action="{{ route('tutor.submissions.grade', $submission) }}" method="POST" class="flex items-center space-x-2">
                                @csrf
                                <input type="number" name="score" min="0" max="{{ $assignment->total_points }}" required class="w-20 border border-gray-300 rounded px-2 py-1 text-sm" placeholder="Score">
                                <input type="text" name="feedback" class="border border-gray-300 rounded px-2 py-1 text-sm flex-1" placeholder="Feedback">
                                <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Grade</button>
                            </form>
                            @else
                            <div class="text-sm">
                                <span class="text-green-600 font-medium">Score: {{ $submission->score }}/{{ $assignment->total_points }}</span>
                                @if($submission->feedback)
                                    <p class="text-gray-500 text-xs mt-1">Feedback: {{ $submission->feedback }}</p>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No submissions yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
