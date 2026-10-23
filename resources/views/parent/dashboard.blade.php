@extends('layouts.app')

@section('title', 'Parent Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Parent Dashboard</h1>
        <p class="text-gray-600 mt-1">Monitor your children's progress</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($children as $child)
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="bg-indigo-600 px-6 py-4">
                    <h3 class="text-white text-lg font-semibold">{{ $child->user->name }}</h3>
                    <p class="text-indigo-200 text-sm">Grade: {{ $child->grade_level ?? 'N/A' }}</p>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-2"></i>{{ $child->user->email }}</p>
                        <p class="text-sm text-gray-600"><i class="fas fa-phone mr-2"></i>{{ $child->phone ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700">Enrolled Classes: {{ $child->classes->count() }}</p>
                    </div>
                    <a href="{{ route('parent.child.progress', $child) }}" class="block w-full text-center bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-chart-line mr-1"></i> View Progress
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-child text-gray-400 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">No children linked to your account yet.</p>
                <p class="text-gray-400">Please contact the administrator.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
