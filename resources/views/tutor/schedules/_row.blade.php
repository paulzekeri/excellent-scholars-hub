{{--
    One session row for a tutor. Shared by the "All schedules" history table
    (which shows the date) and the week table (which groups rows under a day
    heading, so the date would only repeat itself).
--}}
@php
    $isToday = $schedule->start_time->isToday();
    $showDate = $showDate ?? true;
@endphp
<tr class="{{ $isToday ? 'bg-sun-light hover:bg-sun-light' : 'hover:bg-gray-50' }}">
    <td class="px-6 py-4 text-sm font-medium {{ $isToday ? 'text-sun-ink' : '' }}">{{ $schedule->title }}
        @if($schedule->isRecurring())
            <span class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-blue-100 text-blue-800" title="{{ $schedule->seriesLabel() }}">Weekly</span>
        @endif
    </td>
    <td class="px-6 py-4 text-sm">{{ $schedule->class->name }}</td>
    <td class="px-6 py-4 text-sm {{ $isToday ? 'text-sun-ink font-semibold' : '' }}">
        {{ $schedule->start_time->format($showDate ? 'M d, Y h:i A' : 'h:i A') }}@if($isToday)<span class="ml-2 px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-sun text-blue-950">Today</span>@endif
    </td>
    <td class="px-6 py-4 text-sm">{{ $schedule->end_time->format('h:i A') }}</td>
    <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($schedule->status == 'scheduled') bg-blue-100 text-blue-800 @elseif($schedule->status == 'completed') bg-green-100 text-green-800 @else bg-sun-light text-sun-ink @endif">{{ ucfirst($schedule->status) }}</span></td>
    <td class="px-6 py-4 text-sm space-x-2">
        <a href="{{ route('tutor.attendance.index', $schedule) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-check-circle"></i> Attendance</a>
        @if($schedule->status != 'completed')
        <form action="{{ route('tutor.schedules.complete', $schedule) }}" method="POST" class="inline" onsubmit="return confirmComplete(this, '{{ $schedule->end_time }}')">@csrf<button type="submit" class="text-green-600 hover:text-green-800"><i class="fas fa-check"></i> Complete</button></form>
        @endif
        @if($schedule->meeting_link)<a href="{{ $schedule->meeting_link }}" target="_blank" class="text-blue-600 hover:text-blue-800"><i class="fas fa-video"></i> Join</a>@endif
    </td>
</tr>
