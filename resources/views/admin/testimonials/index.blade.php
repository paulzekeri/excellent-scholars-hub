@extends('layouts.app')

@section('title', 'Testimonials')

@section('content')
@php
    $tabs = [
        'pending' => ['label' => 'Waiting', 'count' => $counts['pending']],
        'approved' => ['label' => 'Approved', 'count' => $counts['approved']],
        'rejected' => ['label' => 'Rejected', 'count' => $counts['rejected']],
        'all' => ['label' => 'All', 'count' => $counts['all']],
    ];
@endphp
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Testimonials</h1>
        <p class="text-gray-600 mt-1">
            Members send these from <strong>Messages</strong> (choose the <em>Testimonial</em> type) or from <strong>My profile</strong>.
            Fix the wording if needed, then approve — only approved testimonials appear on the landing page, three at a time.
        </p>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($tabs as $key => $tab)
            <a href="{{ route('admin.testimonials.index', ['status' => $key]) }}"
                class="px-3 py-2 rounded-lg text-sm font-medium border transition {{ $status === $key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                {{ $tab['label'] }} <span class="ml-1 text-xs {{ $status === $key ? 'text-blue-100' : 'text-gray-500' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse($testimonials as $testimonial)
            <div class="bg-white rounded-xl shadow-md p-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        @if($testimonial->user && $testimonial->user->photoUrl())
                            <img src="{{ $testimonial->user->photoUrl() }}" alt="{{ $testimonial->user->name }}" class="h-10 w-10 rounded-full object-cover border border-gray-200">
                        @elseif($testimonial->user)
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-sm font-bold">{{ $testimonial->user->initials() }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800">
                                {{ optional($testimonial->user)->name ?? 'Deleted member' }}
                                <span class="text-xs text-gray-500">· {{ $testimonial->roleLabel() }}</span>
                            </p>
                            <p class="text-xs text-gray-500">
                                Submitted {{ $testimonial->updated_at->diffForHumans() }}
                                @if($testimonial->publish_photo)
                                    · <i class="fas fa-image mr-1"></i>photo publication requested
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 flex flex-col items-start sm:items-end gap-2">
                        <span class="px-2 py-1 text-xs rounded-full {{ $testimonial->statusBadge() }}">{{ $testimonial->statusLabel() }}</span>
                        @if($testimonial->approved_at && $testimonial->approvedBy)
                            <span class="text-xs text-gray-500">by {{ $testimonial->approvedBy->name }} · {{ $testimonial->approved_at->format('M j, Y') }}</span>
                        @endif
                    </div>
                </div>

                <blockquote class="mt-4 text-gray-700 border-l-4 border-orange-200 pl-4">{{ $testimonial->body }}</blockquote>

                <details class="mt-3 group">
                    <summary class="cursor-pointer text-sm font-semibold text-blue-700 hover:text-blue-900 list-none">
                        <i class="fas fa-pen-to-square mr-1"></i> Edit the wording
                    </summary>
                    <form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST" class="mt-3 rounded-lg border border-blue-100 bg-[#EAF2FB]/60 p-4">
                        @csrf
                        @method('PATCH')
                        <label for="body_{{ $testimonial->id }}" class="block text-xs font-semibold uppercase tracking-wide text-[#0a2550]">Published text</label>
                        <textarea id="body_{{ $testimonial->id }}" name="body" rows="3" maxlength="1000" required
                            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('body', $testimonial->body) }}</textarea>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                            <label class="flex items-center gap-2 text-xs text-gray-600">
                                <input type="hidden" name="publish_photo" value="0">
                                <input type="checkbox" name="publish_photo" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                    @checked($testimonial->publish_photo)>
                                Publish this member's photo with the quote
                            </label>
                            <button type="submit" class="bg-[#0a2550] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#0d3070]">
                                <i class="fas fa-save mr-1"></i> Save wording
                            </button>
                        </div>
                    </form>
                </details>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-end gap-3">
                    @if($testimonial->status !== \App\Models\Testimonial::STATUS_APPROVED)
                        <form action="{{ route('admin.testimonials.approve', $testimonial) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
                                <i class="fas fa-check mr-1"></i> Approve for the website
                            </button>
                        </form>
                    @endif

                    @if($testimonial->status !== \App\Models\Testimonial::STATUS_REJECTED)
                        <form action="{{ route('admin.testimonials.reject', $testimonial) }}" method="POST" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label for="review_note_{{ $testimonial->id }}" class="block text-xs text-gray-500">Reason (shown to the member)</label>
                                <input id="review_note_{{ $testimonial->id }}" type="text" name="review_note" maxlength="500"
                                    class="mt-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Optional">
                            </div>
                            <button type="submit" class="text-sm border border-orange-300 text-orange-800 px-4 py-2 rounded-lg hover:bg-orange-50">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-md p-10 text-center">
                <i class="fas fa-quote-left text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-600">No testimonials in this view.</p>
            </div>
        @endforelse
    </div>

    @if($testimonials->hasPages())
        <div class="mt-4">{{ $testimonials->links() }}</div>
    @endif
</div>
@endsection
