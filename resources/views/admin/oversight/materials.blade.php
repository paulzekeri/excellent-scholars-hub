@extends('layouts.app')
@section('title', 'Materials Oversight')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Learning Materials Oversight</h1>
        <p class="text-gray-600 mt-1">View all uploaded learning materials across classes</p>
    </div>

    <!-- Filter by Class -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.oversight.materials') }}" class="flex flex-wrap items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Filter by Class:</label>
            <select name="class_id" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ isset($selectedClass) && $selectedClass->id == $class->id ? 'selected' : '' }}>
                        {{ $class->name }} ({{ $class->subject }})
                    </option>
                @endforeach
            </select>
            @if(isset($selectedClass))
                <a href="{{ route('admin.oversight.materials') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Clear filter</a>
            @endif
        </form>
    </div>

    <!-- Materials List -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uploaded By</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">File Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($materials as $material)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-800">{{ $material->title }}</p>
                                @if($material->description)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($material->description, 60) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $material->class->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $material->tutor->user->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800 font-medium">
                                    {{ strtoupper($material->file_type ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $material->created_at ? \Carbon\Carbon::parse($material->created_at)->format('M d, Y') : '-' }}</td>
                            <td class="px-4 py-3">
                                @if($material->file_path)
                                    <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                @else
                                    <span class="text-gray-400 text-sm">No file</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-folder-open text-4xl text-gray-300 mb-3 block"></i>
                                No learning materials found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($materials, 'links'))
            <div class="px-4 py-3 border-t">
                {{ $materials->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
