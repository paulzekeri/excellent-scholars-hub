@extends('layouts.app')
@section('title', 'Take Attendance')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.schedules.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back to Schedules</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Take Attendance</h1>
        <p class="text-gray-600 mb-6">{{ $schedule->title }} - {{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}</p>
        
        <form action="{{ route('tutor.attendance.store', $schedule) }}" method="POST">@csrf
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($learners as $learner)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium">{{ $learner->user->name }}</td>
                            <td class="px-4 py-3">
                                <select name="attendance[{{ $learner->id }}][status]" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="present" {{ isset($attendance[$learner->id]) && $attendance[$learner->id]->status == 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="absent" {{ isset($attendance[$learner->id]) && $attendance[$learner->id]->status == 'absent' ? 'selected' : '' }}>Absent</option>
                                    <option value="late" {{ isset($attendance[$learner->id]) && $attendance[$learner->id]->status == 'late' ? 'selected' : '' }}>Late</option>
                                    <option value="excused" {{ isset($attendance[$learner->id]) && $attendance[$learner->id]->status == 'excused' ? 'selected' : '' }}>Excused</option>
                                </select>
                            </td>
                            <td class="px-4 py-3"><input type="text" name="attendance[{{ $learner->id }}][notes]" value="{{ $attendance[$learner->id]->notes ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full focus:ring-indigo-500 focus:border-indigo-500" placeholder="Optional notes"></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-4 py-3 text-center text-gray-500">No students enrolled in this class</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(count($learners) > 0)
            <button type="submit" class="mt-6 bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-save mr-1"></i> Save Attendance</button>
            @endif
        </form>
    </div>
</div>
@endsection
