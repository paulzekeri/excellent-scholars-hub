@extends('layouts.app')
@section('title', 'My Schedules')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">My Schedules</h1><p class="text-gray-600 mt-1">Your class schedules</p></div>
        <a href="{{ route('tutor.schedules.create') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-plus mr-1"></i> New Schedule</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($schedules as $schedule)
                    @php $isToday = \Carbon\Carbon::parse($schedule->start_time)->isToday(); @endphp
                    <tr class="{{ $isToday ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }}">
                        <td class="px-6 py-4 text-sm font-medium {{ $isToday ? 'text-red-700' : '' }}">{{ $schedule->title }}</td>
                        <td class="px-6 py-4 text-sm">{{ $schedule->class->name }}</td>
                        <td class="px-6 py-4 text-sm {{ $isToday ? 'text-red-700 font-semibold' : '' }}">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}@if($isToday)<span class="ml-2 px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-red-600 text-white">Today</span>@endif</td>
                        <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($schedule->status == 'scheduled') bg-blue-100 text-blue-800 @elseif($schedule->status == 'completed') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">{{ ucfirst($schedule->status) }}</span></td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="{{ route('tutor.attendance.index', $schedule) }}" class="text-indigo-600 hover:text-indigo-800"><i class="fas fa-check-circle"></i> Attendance</a>
                            @if($schedule->status != 'completed')
                            <form action="{{ route('tutor.schedules.complete', $schedule) }}" method="POST" class="inline" onsubmit="return confirmComplete(this, '{{ $schedule->end_time }}')">@csrf<button type="submit" class="text-green-600 hover:text-green-800"><i class="fas fa-check"></i> Complete</button></form>
                            @endif
                            @if($schedule->meeting_link)<a href="{{ $schedule->meeting_link }}" target="_blank" class="text-blue-600 hover:text-blue-800"><i class="fas fa-video"></i> Join</a>@endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $schedules->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmComplete(form, endTime) {
    var now = new Date();
    var end = new Date(endTime);
    if (now < end) {
        alert('This class session has not ended yet. You can only mark it as completed after the scheduled end time (' + end.toLocaleString() + ').');
        return false;
    }
    return confirm('Mark this class as completed?');
}
</script>
@endpush