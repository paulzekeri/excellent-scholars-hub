@extends('layouts.app')

@section('title', 'My profile')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">My profile</h1>
        <p class="text-gray-600 mt-1">Update your name, email and photo, and share a testimonial with the school.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-user text-blue-600 mr-2"></i>Account details</h2>

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-4">
                    @if($user->photoUrl())
                        <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full object-cover border border-gray-200">
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-xl font-bold">{{ $user->initials() }}</span>
                    @endif
                    <div class="min-w-0">
                        <label for="photo" class="block text-sm font-medium text-gray-700">Profile photo</label>
                        <input id="photo" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"
                            class="mt-1 block w-full text-sm text-gray-600">
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG or WebP, up to 4 MB.</p>
                    </div>
                </div>

                @if($user->photo_path)
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_photo" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600">
                        Remove my current photo
                    </label>
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" {{ $user->isLearner() ? '' : 'required' }}
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                    @if($user->isLearner())
                        <p class="text-xs text-gray-500 mt-1">Optional for a learner with no mailbox — your parent/guardian's email stays on file.</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" value="{{ $user->username ?: '—' }}" readonly
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">Your username cannot be changed here — ask an administrator.</p>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Save profile
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-quote-left text-orange-700 mr-2"></i>Your testimonial</h2>

            @if($testimonial)
                <div class="mb-4 rounded-lg bg-gray-50 border border-gray-200 p-4">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $testimonial->statusBadge() }}">{{ $testimonial->statusLabel() }}</span>
                        @if($testimonial->approved_at)
                            <span class="text-xs text-gray-500">{{ $testimonial->approved_at->format('M j, Y') }}</span>
                        @endif
                        @if($testimonial->publish_photo)
                            <span class="text-xs text-gray-500"><i class="fas fa-image mr-1"></i>photo included</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $testimonial->body }}</p>
                    @if($testimonial->review_note)
                        <p class="mt-2 text-xs text-orange-700"><strong>CEO note:</strong> {{ $testimonial->review_note }}</p>
                    @endif
                </div>
            @endif

            <form action="{{ route('profile.testimonial.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700">What would you like to say?</label>
                    <textarea id="body" name="body" rows="4" required maxlength="1000"
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Tell other families about your experience">{{ old('body', optional($testimonial)->body) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Up to 1,000 characters. The CEO reviews it before it appears on the website.</p>
                </div>

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="publish_photo" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600"
                        {{ old('publish_photo', optional($testimonial)->publish_photo) ? 'checked' : '' }}>
                    <span>
                        Publish my profile photo with this testimonial.
                        @if(! $user->photo_path)
                            <span class="block text-xs text-amber-600">Add a profile photo first to use this.</span>
                        @endif
                    </span>
                </label>

                <button type="submit" class="bg-orange-500 text-blue-950 px-5 py-2.5 rounded-lg hover:bg-orange-400 transition">
                    <i class="fas fa-paper-plane mr-1"></i> Send to the CEO
                </button>
                <p class="text-xs text-gray-500">Editing an approved testimonial sends it back for approval.</p>
            </form>
        </div>
    </div>
</div>
@endsection
