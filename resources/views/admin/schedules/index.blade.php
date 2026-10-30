@extends('layouts.app')
@section('title', 'Manage Schedules')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Manage Schedules</h1><p class="text-gray-600 mt-1">All class schedules &mdash; upcoming sessions first, then most recent past sessions. <span class="text-yellow-800 font-medium">Sessions scheduled for today are highlighted in yellow.</span></p></div>
        <a href="{{ route('admin.schedules.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-plus mr-1"></i> New Schedule</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tutor</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($schedules as $schedule)
                    @php $isToday = \Carbon\Carbon::parse($schedule->start_time)->isToday(); @endphp
                    <tr class="{{ $isToday ? 'bg-yellow-50 hover:bg-yellow-100' : 'hover:bg-gray-50' }}">
                        <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.schedules.show', $schedule) }}" class="{{ $isToday ? 'text-yellow-900 hover:text-yellow-800' : 'text-gray-800 hover:text-blue-600' }}">{{ $schedule->title }}</a></td>
                        <td class="px-6 py-4 text-sm">{{ $schedule->class->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $schedule->tutor->user->name }}</td>
                        <td class="px-6 py-4 text-sm {{ $isToday ? 'text-yellow-900 font-semibold' : '' }}">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}@if($isToday)<span class="ml-2 px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-yellow-400 text-blue-950">Today</span>@endif</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($schedule->status == 'scheduled') bg-blue-100 text-blue-800 @elseif($schedule->status == 'ongoing') bg-yellow-100 text-yellow-800 @elseif($schedule->status == 'completed') bg-green-100 text-green-800 @else bg-orange-100 text-orange-900 @endif">{{ ucfirst($schedule->status) }}</span></td>
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
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 mr-3" title="Reopen"><i class="fas fa-undo"></i></button>
                                </form>
                            @endif
                            <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">@csrf @method('DELETE')<button type="submit" class="text-orange-700 hover:text-orange-900"><i class="fas fa-trash"></i></button></form>
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
