@extends('layouts.app')
@section('title', 'Assignments')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Assignments</h1><p class="text-gray-600 mt-1">Manage your assignments</p></div>
        <a href="{{ route('tutor.assignments.create') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-plus mr-1"></i> New Assignment</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submissions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($assignments as $assignment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">{{ $assignment->title }}</td>
                        <td class="px-6 py-4 text-sm">{{ $assignment->class->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm">{{ $assignment->total_points }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($assignment->status == 'active') bg-green-100 text-green-800 @else bg-gray-100 text-gray-800 @endif">{{ ucfirst($assignment->status) }}</span></td>
                        <td class="px-6 py-4 text-sm"><a href="{{ route('tutor.assignments.submissions', $assignment) }}" class="text-indigo-600 hover:text-indigo-800"><i class="fas fa-eye"></i> View ({{ $assignment->submissions->count() }})</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $assignments->links() }}</div>
    </div>
</div>
@endsection
