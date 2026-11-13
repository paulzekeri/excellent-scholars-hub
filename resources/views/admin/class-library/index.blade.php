@extends('layouts.app')
@section('title', 'Class Library')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Class Library</h1>
        <p class="text-gray-600 mt-1">
            Google Drive folders, videos, PDFs and documents for a class. Learners find them under
            <strong>My Learning &rarr; Class Library</strong> &mdash; the title is the link, so the address itself is never shown.
        </p>
    </div>

    {{-- Add a resource link --}}
    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-plus-circle text-blue-700 mr-1"></i> Add a resource link</h2>
        <form method="POST" action="{{ route('admin.class-library.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="library-class" class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                    <select id="library-class" name="class_id" required class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Choose a class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) old('class_id', $classFilter) === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="library-title" class="block text-xs font-medium text-gray-600 mb-1">Title (the clickable name)</label>
                    <input id="library-title" type="text" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="Fractions video library" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="library-url" class="block text-xs font-medium text-gray-600 mb-1">Link (Google Drive, YouTube, PDF…)</label>
                    <input id="library-url" type="url" name="url" value="{{ old('url') }}" required maxlength="2048" placeholder="https://drive.google.com/..." class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="library-type" class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                    <select id="library-type" name="type" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach(\App\Models\ClassLibraryResource::types() as $value => $label)
                            <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="library-description" class="block text-xs font-medium text-gray-600 mb-1">Description (optional)</label>
                <textarea id="library-description" name="description" rows="2" maxlength="2000" placeholder="What is inside, and when to use it" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition">
                <i class="fas fa-link mr-1"></i> Add to class library
            </button>
        </form>
    </div>

    {{-- Filter + counts --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span>Showing <strong class="text-gray-700">{{ $resources->total() }}</strong> link{{ $resources->total() === 1 ? '' : 's' }}
            @if($classFilter)
                &middot; for <strong class="text-gray-700">{{ $classes->firstWhere('id', $classFilter)?->name ?? 'the selected class' }}</strong>
            @endif
        </span>
        <form method="GET" action="{{ route('admin.class-library.index') }}" class="flex items-center gap-2">
            <label for="library-filter" class="sr-only">Filter by class</label>
            <select id="library-filter" name="class_id" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-blue-500 focus:border-blue-500">
                <option value="">All classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ (string) $classFilter === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
            @if($classFilter)
                <a href="{{ route('admin.class-library.index') }}" class="font-semibold text-blue-700 hover:text-blue-900">Reset</a>
            @endif
        </form>
    </div>

    {{-- The links --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Resource</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Added by</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Added</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($resources as $resource)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-700 hover:text-blue-900">
                                <i class="fas {{ $resource->typeIcon() }} mr-1"></i>{{ $resource->title }} <i class="fas fa-external-link-alt text-[10px]"></i>
                            </a>
                            <span class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-blue-100 text-blue-800">{{ $resource->typeLabel() }}</span>
                            @if($resource->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $resource->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $resource->class->name ?? 'Class removed' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $resource->addedBy->name ?? 'School' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $resource->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('admin.class-library.destroy', $resource) }}" method="POST" class="inline" onsubmit="return confirm('Remove this link from the class library?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-orange-700 hover:text-orange-900" title="Remove link"><i class="fas fa-trash"></i> Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                            <i class="fas fa-book-open text-gray-400 mr-2"></i>
                            No links in the class library yet. Add the first one above &mdash; learners see it immediately.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($resources->hasPages())
            <div class="p-4 border-t">{{ $resources->links() }}</div>
        @endif
    </div>
</div>
@endsection
