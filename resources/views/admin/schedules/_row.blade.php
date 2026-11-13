{{--
    One session row. Shared by the "All schedules" history table (which shows the
    date) and the week/day tables (which group rows under a day heading, so the
    date would only repeat itself).
--}}
@php
    $isToday = $schedule->start_time->isToday();
    $showDate = $showDate ?? true;
@endphp
<tr class="{{ $isToday ? 'bg-sun-light hover:bg-sun-light' : 'hover:bg-gray-50' }}">
    <td class="px-6 py-4 text-sm font-medium">
        <a href="{{ route('admin.schedules.show', $schedule) }}" class="{{ $isToday ? 'text-sun-ink hover:text-sun-ink' : 'text-gray-800 hover:text-blue-600' }}">{{ $schedule->title }}</a>
        @if($schedule->isRecurring())
            <span class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-blue-100 text-blue-800" title="{{ $schedule->seriesLabel() }}">Weekly</span>
        @endif
    </td>
    <td class="px-6 py-4 text-sm">{{ $schedule->class->name }}</td>
    <td class="px-6 py-4 text-sm">{{ $schedule->tutor->user->name }}</td>
    <td class="px-6 py-4 text-sm {{ $isToday ? 'text-sun-ink font-semibold' : '' }}">
        {{ $schedule->start_time->format($showDate ? 'M d, Y h:i A' : 'h:i A') }}@if($isToday)<span class="ml-2 px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-sun text-blue-950">Today</span>@endif
    </td>
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
