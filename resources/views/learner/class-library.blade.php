@extends('layouts.app')
@section('title', 'Class Library')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Class Library</h1>
        <p class="text-gray-600 mt-1">
            Videos, PDFs and documents your school and tutors shared for your classes.
            Click a title to open it &mdash; resources open in a new tab.
        </p>
    </div>

    @php
        // Group the links under the class they belong to, classes in name order.
        $byClass = $resources->groupBy(function ($resource) {
            return $resource->class->name ?? 'Your class';
        })->sortKeys();
    @endphp

    @forelse($byClass as $className => $classResources)
        <section class="mb-8">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-chalkboard text-blue-700 mr-2"></i>{{ $className }}
                </h2>
                <span class="text-xs text-gray-500">{{ $classResources->count() }} {{ $classResources->count() === 1 ? 'resource' : 'resources' }}</span>
            </div>

            <div class="space-y-3">
                @foreach($classResources as $resource)
                    <div class="bg-white rounded-xl shadow-md p-5 hover:shadow-lg transition">
                        <div class="flex items-start gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                <i class="fas {{ $resource->typeIcon() }}"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- The title is the link; the URL itself is never printed. --}}
                                    <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer"
                                       class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                                        {{ $resource->title }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                    </a>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-blue-100 text-blue-800">{{ $resource->typeLabel() }}</span>
                                </div>
                                @if($resource->description)
                                    <p class="text-sm text-gray-600 mt-2">{{ $resource->description }}</p>
                                @endif
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="far fa-calendar mr-1"></i> Added {{ $resource->created_at->format('M d, Y') }}
                                    @if($resource->addedBy) &middot; by {{ $resource->addedBy->name }} @endif
                                    &middot; opens in a new tab
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="bg-white rounded-xl shadow-md p-10 text-center">
            <i class="fas fa-book-open text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-600">Your class library is empty for now.</p>
            <p class="text-sm text-gray-500 mt-1">
                When your school or tutor adds a Google Drive link, video, PDF or document for one of your classes,
                it appears here.
            </p>
        </div>
    @endforelse
</div>
@endsection
