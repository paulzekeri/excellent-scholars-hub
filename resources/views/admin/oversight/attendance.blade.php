@extends('layouts.app')
@section('title', 'Attendance Oversight')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Attendance Oversight</h1>
        <p class="text-gray-600 mt-1">View all attendance records across classes</p>
    </div>

    <!-- Filter by Class -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.oversight.attendance') }}" class="flex flex-wrap items-center gap-4">
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
                <a href="{{ route('admin.oversight.attendance') }}" class="text-sm text-blue-600 hover:text-blue-800">Clear filter</a>
            @endif
        </form>
    </div>

    <!-- Attendance Records -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Learner</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($attendance as $record)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $record->learner->user->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $record->classSchedule->class->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($record->start_time)->format('M d, Y h:i A') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full font-medium
                                    @if($record->status == 'present') bg-green-100 text-green-800
                                    @elseif($record->status == 'absent') bg-orange-100 text-orange-900
                                    @elseif($record->status == 'late') bg-sun-light text-sun-ink
                                    @elseif($record->status == 'excused') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $record->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3 block"></i>
                                No attendance records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($attendance, 'links'))
            <div class="px-4 py-3 border-t">
                {{ $attendance->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
