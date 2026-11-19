@extends('layouts.app')

@section('title', 'Register')

@section('content')
@php
    // The public form only registers learners, so the role is posted for the
    // controller's validation; nothing else on the page depends on it.
    $learnerRoleId = optional($roles->first())->id;
@endphp
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0a2550] via-[#0d3070] to-[#1d4ed8] py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 border-t-4 border-orange-500">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="mx-auto h-20 w-auto">
            <p class="text-gray-500 mt-2">Register to learn</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <!-- Learners are the only accounts the public form can create. Tutors
                 are staff: an administrator or the CEO adds them from Users > Add user. -->
            <input type="hidden" name="role_id" value="{{ $learnerRoleId }}" data-role-slug="learner">
            @error('role_id')
                <p class="text-orange-700 text-xs">{{ $message }}</p>
            @enderror

            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-base font-semibold text-gray-700">Learner Details</h3>
                <p class="text-xs text-gray-500 mt-1">The learner is the child who attends classes — use the child's own name, a username they can remember, and their parent/guardian's details at the end.</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Learner Full Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                    placeholder="Enter the learner's full name"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('name') border-orange-500 @enderror">
                @error('name')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">
                    Learner Username <span class="text-orange-700">*</span>
                </label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required
                    placeholder="e.g. tolu_okafor"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('username') border-orange-500 @enderror">
                @error('username')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Letters, numbers, dashes and underscores. This is what the learner signs in with, so pick something they will remember.</p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Learner Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    placeholder="Optional — the learner's own email, if any"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('email') border-orange-500 @enderror">
                @error('email')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Optional for a learner with no email — the parent/guardian's email below is used instead.</p>
            </div>

            <div>
                <label for="grade_id" class="block text-sm font-medium text-gray-700">Current Class / Grade <span class="text-orange-700">*</span></label>
                <select id="grade_id" name="grade_id" required
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('grade_id') border-orange-500 @enderror">
                    <option value="">Select the learner's class / grade</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}" {{ (string) old('grade_id') === (string) $grade->id ? 'selected' : '' }}>{{ $grade->label() }}</option>
                    @endforeach
                </select>
                @error('grade_id')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">This is how the school places the learner in the right class.</p>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Learner Phone (optional)</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                    placeholder="Enter the learner's phone number"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Parent/Guardian details: every learner must have a reachable guardian. -->
            <div id="parent-details" class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                <h3 class="text-base font-semibold text-gray-700 mb-3">Parent/Guardian Details</h3>
                <p class="text-xs text-gray-600 mb-4">A parent account will be created automatically and the login details sent to their email address.</p>

                <div class="space-y-4">
                    <div>
                        <label for="parent_name" class="block text-sm font-medium text-gray-700">Parent/Guardian Full Name <span class="text-orange-700">*</span></label>
                        <input id="parent_name" type="text" name="parent_name" value="{{ old('parent_name') }}" required
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('parent_name') border-orange-500 @enderror"
                            placeholder="Enter the parent/guardian's full name">
                        @error('parent_name')
                            <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="parent_email" class="block text-sm font-medium text-gray-700">Parent/Guardian Email <span class="text-orange-700">*</span></label>
                        <input id="parent_email" type="email" name="parent_email" value="{{ old('parent_email') }}" required
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('parent_email') border-orange-500 @enderror"
                            placeholder="Enter the parent/guardian's email address">
                        @error('parent_email')
                            <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Learner Password</label>
                <input id="password" type="password" name="password" required
                    placeholder="Create a password for the learner (min 8 characters)"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('password') border-orange-500 @enderror">
                @error('password')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password-confirm" class="block text-sm font-medium text-gray-700">Confirm Learner Password</label>
                <input id="password-confirm" type="password" name="password_confirmation" required
                    placeholder="Confirm the learner's password"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium text-lg transition">
                    Create Learner Account
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
            </p>
        </div>
    </div>
</div>

@endsection
