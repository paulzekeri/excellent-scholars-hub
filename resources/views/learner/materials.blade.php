@extends('layouts.app')
@section('title', 'My Materials')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">My Materials</h1>
        <p class="text-gray-600 mt-1">Access your course materials</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    @forelse($materials as $material)
        <div class="bg-white rounded-xl shadow-md p-6 mb-4 hover:shadow-lg transition">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-lg text-gray-800">{{ $material->title }}</h3>
                        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">{{ strtoupper($material->file_type) }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $material->class->name }}</p>
                    @if($material->description)
                        <p class="text-sm text-gray-600 mt-2">{{ $material->description }}</p>
                    @endif
                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
                        <span><i class="far fa-file mr-1"></i> {{ round($material->file_size / 1024, 2) }} KB</span>
                        <span><i class="far fa-calendar mr-1"></i> {{ $material->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
                <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" 
                    class="ml-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center">
                    <i class="fas fa-download mr-1"></i> Download
                </a>
            </div>
        </div>
    @empty
        <div class="text-center py-12">
            <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No materials available yet.</p>
            <p class="text-gray-400 text-sm mt-1">Materials will appear here when your tutors upload them.</p>
        </div>
    @endforelse

    <div class="mt-6">
        {{ $materials->links() }}
    </div>
</div>
@endsection
