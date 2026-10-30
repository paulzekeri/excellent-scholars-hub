@extends('layouts.app')

@section('title', 'Affirmations')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Affirmations</h1>
        <p class="text-gray-600 mt-1">Write the motivation the learners see on their dashboards. The newest active post is shown; the built-in rotation is only a fallback.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-sun text-orange-700 mr-2"></i>Publish a new affirmation</h2>
        <form action="{{ route('admin.affirmations.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label for="body" class="block text-sm font-medium text-gray-700">Message</label>
                <textarea id="body" name="body" rows="3" required maxlength="500"
                    class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g. Every page you read this week makes next week easier.">{{ old('body') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Up to 500 characters. It replaces the current affirmation on every dashboard immediately.</p>
            </div>
            <button type="submit" class="bg-orange-500 text-blue-950 px-5 py-2.5 rounded-lg hover:bg-orange-400 transition">
                <i class="fas fa-paper-plane mr-1"></i> Publish affirmation
            </button>
        </form>
    </div>

    <div class="rounded-xl shadow-md p-6 mb-6 text-white bg-gradient-to-r from-[#0a2550] via-[#0d3070] to-[#1d4ed8]">
        @if($current)
            <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Showing on every dashboard now</p>
            <p class="mt-2 text-lg font-semibold">&ldquo;{{ $current->body }}&rdquo;</p>
            <p class="mt-2 text-xs text-white/80">
                {{ optional($current->author)->name ?? 'The school' }}
                @if($current->published_at) · {{ $current->published_at->format('M j, Y g:i a') }} @endif
            </p>
        @else
            <p class="text-sm">No affirmation is active, so the dashboards are using the built-in daily rotation. Publish one above to take over.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">History</h2>
        </div>
        <ul class="divide-y divide-gray-100">
            @forelse($affirmations as $affirmation)
                <li class="px-6 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-800">&ldquo;{{ $affirmation->body }}&rdquo;</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ optional($affirmation->author)->name ?? 'The school' }}
                                @if($affirmation->published_at) · {{ $affirmation->published_at->format('M j, Y g:i a') }} @endif
                            </p>
                        </div>
                        <div class="shrink-0 flex items-center gap-3">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $affirmation->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $affirmation->is_active ? 'Active' : 'Switched off' }}
                            </span>
                            <form action="{{ route('admin.affirmations.toggle', $affirmation) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs {{ $affirmation->is_active ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-700 hover:text-green-900' }}">
                                    {{ $affirmation->is_active ? 'Switch off' : 'Switch on' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.affirmations.destroy', $affirmation) }}" method="POST" onsubmit="return confirm('Delete this affirmation?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-orange-700 hover:text-orange-900">Delete</button>
                            </form>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-gray-600">Nothing posted yet.</li>
            @endforelse
        </ul>
        @if($affirmations->hasPages())
            <div class="p-4 border-t">{{ $affirmations->links() }}</div>
        @endif
    </div>
</div>
@endsection
