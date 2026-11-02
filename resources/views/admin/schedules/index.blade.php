@extends('layouts.app')
@section('title', 'Manage Schedules')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Manage Schedules</h1><p class="text-gray-600 mt-1">All class schedules &mdash; upcoming sessions first, then most recent past sessions. <span class="text-sun-ink font-medium">Sessions scheduled for today are highlighted in yellow.</span></p></div>
        <a href="{{ route('admin.schedules.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-plus mr-1"></i> New Schedule</a>
    </div>

    <form method="GET" action="{{ route('admin.schedules.index') }}" class="bg-white rounded-xl shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label for="schedule-class-filter" class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                <select id="schedule-class-filter" name="class_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ (string) $classFilter === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="schedule-tutor-filter" class="block text-xs font-medium text-gray-600 mb-1">Tutor</label>
                <select id="schedule-tutor-filter" name="tutor_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All tutors</option>
                    @foreach($tutors as $tutor)
                        <option value="{{ $tutor->id }}" {{ (string) $tutorFilter === (string) $tutor->id ? 'selected' : '' }}>{{ $tutor->user->name ?? 'Tutor' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="schedule-date-filter" class="block text-xs font-medium text-gray-600 mb-1">Start Time (day)</label>
                <input id="schedule-date-filter" type="date" name="date" value="{{ $dateFilter }}" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="schedule-status-filter" class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="schedule-status-filter" name="status" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Any status</option>
                    @foreach(['scheduled' => 'Scheduled', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $statusFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-filter mr-1"></i> Filter</button>
                <a href="{{ route('admin.schedules.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Reset</a>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
            <span>
                Showing <strong class="text-gray-700">{{ $schedules->total() }}</strong> of {{ $totalSessions }} sessions
                @if($dateFilter)
                    &middot; on <strong class="text-gray-700">{{ \Carbon\Carbon::parse($dateFilter)->format('D, M d, Y') }}</strong>
                @endif
            </span>
            <span class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.schedules.index', array_filter(['class_id' => $classFilter, 'tutor_id' => $tutorFilter, 'status' => $statusFilter, 'date' => now()->toDateString()])) }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-calendar-day mr-1"></i>Today</a>
                <a href="{{ route('admin.schedules.index', array_filter(['class_id' => $classFilter, 'tutor_id' => $tutorFilter, 'status' => $statusFilter])) }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-calendar mr-1"></i>Any day</a>
            </span>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Tutor</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Start Time</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($schedules as $schedule)
                    @php $isToday = \Carbon\Carbon::parse($schedule->start_time)->isToday(); @endphp
                    <tr class="{{ $isToday ? 'bg-sun-light hover:bg-sun-light' : 'hover:bg-gray-50' }}">
                        <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.schedules.show', $schedule) }}" class="{{ $isToday ? 'text-sun-ink hover:text-sun-ink' : 'text-gray-800 hover:text-blue-600' }}">{{ $schedule->title }}</a>
                            @if($schedule->isRecurring())
                                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-blue-100 text-blue-800" title="{{ $schedule->seriesLabel() }}">Weekly</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $schedule->class->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $schedule->tutor->user->name }}</td>
                        <td class="px-6 py-4 text-sm {{ $isToday ? 'text-sun-ink font-semibold' : '' }}">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}@if($isToday)<span class="ml-2 px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-sun text-blue-950">Today</span>@endif</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($schedule->status == 'scheduled') bg-blue-100 text-blue-800 @elseif($schedule->status == 'ongoing') bg-sun-light text-sun-ink @elseif($schedule->status == 'completed') bg-green-100 text-green-800 @else bg-orange-100 text-orange-900 @endif">{{ ucfirst($schedule->status) }}</span></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.schedules.show', $schedule) }}" class="text-blue-600 hover:text-blue-800 mr-3" title="View session &amp; join"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.schedules.edit', $schedule) }}" class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></a>
                            @if($schedule->status !== 'completed')
                                <form action="{{ route('admin.schedules.complete', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Mark this class as completed? It will count on the tutor report.')">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 mr-3" title="Mark completed"><i class="fas fa-check"></i></button>
                                </form>
                            @else
                                <form action="{{ route('admin.schedules.reopen', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Reopen this session? It will stop counting on the tutor report.')">
                                    @csrf
                                    <button type="submit" class="text-sun-ink hover:text-sun-ink mr-3" title="Reopen"><i class="fas fa-undo"></i></button>
                                </form>
                            @endif
                            <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">@csrf @method('DELETE')<button type="submit" class="text-orange-700 hover:text-orange-900"><i class="fas fa-trash"></i></button></form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                            <i class="fas fa-calendar-times mr-2 text-gray-400"></i>
                            No sessions match these filters.
                            <a href="{{ route('admin.schedules.index') }}" class="font-semibold text-blue-700 hover:text-blue-900">Clear the filters</a>
                            to see everything.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $schedules->links() }}</div>
    </div>
</div>
@endsection
