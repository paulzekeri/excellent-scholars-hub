@extends('layouts.app')
@section('title', 'My Classes')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">My Classes</h1>
    <p class="text-gray-600 mb-6">Your enrolled classes and schedules</p>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @foreach($classes as $class)
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800">{{ $class->name }}</h2>
                <p class="text-gray-600">{{ $class->subject }} - {{ $class->grade_level }}</p>
                @if($class->description)<p class="text-sm text-gray-500 mt-2">{{ $class->description }}</p>@endif
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Schedules</h3>
            @forelse($class->schedules as $schedule)
            <div class="bg-white rounded-xl shadow-md p-6 mb-4 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-800">{{ $schedule->title }}</h4>
                        <p class="text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::parse($schedule->start_time)->format('l, M d, Y') }}</p>
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}</p>
                    </div>
                    <div class="text-right">
                        @if($schedule->meeting_link && $schedule->status == 'scheduled')
                            <a href="{{ $schedule->meeting_link }}" target="_blank" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center"><i class="fas fa-video mr-2"></i> Join Class</a>
                        @else
                            <span class="text-gray-500"><i class="fas fa-check-circle"></i> {{ ucfirst($schedule->status) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <p class="text-gray-500">No upcoming schedules.</p>
            @endforelse
        </div>
        @endforeach
    </div>
</div>
@endsection
