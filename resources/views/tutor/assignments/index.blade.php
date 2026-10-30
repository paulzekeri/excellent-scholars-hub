@extends('layouts.app')
@section('title', 'Assignments')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Assignments</h1><p class="text-gray-600 mt-1">Manage your assignments</p></div>
        <a href="{{ route('tutor.assignments.create') }}" class="bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-plus mr-1"></i> New Assignment</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Due Date</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Points</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Submissions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($assignments as $assignment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">
                            {{ $assignment->title }}
                            @if($assignment->hasImages())
                                <div class="mt-1 flex gap-1">
                                    @foreach($assignment->imagePaths() as $image)
                                        <a href="{{ Storage::url($image) }}" target="_blank" rel="noopener" title="Open picture">
                                            <img src="{{ Storage::url($image) }}" alt="Assignment picture" class="h-8 w-8 object-cover rounded border border-gray-200">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $assignment->class->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm">{{ $assignment->total_points }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($assignment->status == 'active') bg-green-100 text-green-800 @else bg-gray-100 text-gray-800 @endif">{{ ucfirst($assignment->status) }}</span></td>
                        <td class="px-6 py-4 text-sm"><a href="{{ route('tutor.assignments.submissions', $assignment) }}" class="text-blue-700 hover:text-orange-700"><i class="fas fa-eye"></i> View ({{ $assignment->submissions->count() }})</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $assignments->links() }}</div>
    </div>
</div>
@endsection
