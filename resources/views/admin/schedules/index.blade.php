@extends('layouts.app')
@section('title', 'Manage Schedules')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Manage Schedules</h1><p class="text-gray-600 mt-1">All class schedules</p></div>
        <a href="{{ route('admin.schedules.create') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-plus mr-1"></i> New Schedule</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tutor</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($schedules as $schedule)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">{{ $schedule->title }}</td>
                        <td class="px-6 py-4 text-sm">{{ $schedule->class->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $schedule->tutor->user->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($schedule->status == 'scheduled') bg-blue-100 text-blue-800 @elseif($schedule->status == 'ongoing') bg-yellow-100 text-yellow-800 @elseif($schedule->status == 'completed') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">{{ ucfirst($schedule->status) }}</span></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.schedules.edit', $schedule) }}" class="text-indigo-600 hover:text-indigo-800 mr-3"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">@csrf @method('DELETE')<button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></form>
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
