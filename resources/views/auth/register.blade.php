@extends('layouts.app')

@section('title', 'Register')

@section('content')
@php
    // The chosen role drives every label on the form, so resolve the old input once.
    $oldRole = old('role_id') ? \App\Models\Role::find(old('role_id')) : null;
    $oldIsLearner = $oldRole && $oldRole->slug === 'learner';
@endphp
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0a2550] via-[#0d3070] to-[#1d4ed8] py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 border-t-4 border-orange-500">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="mx-auto h-20 w-auto">
            <p class="text-gray-500 mt-2">Create your account</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <!-- Question one: who is registering? Everything below adapts to this choice. -->
            <fieldset>
                <legend class="block text-sm font-medium text-gray-700">
                    I am a <span class="text-orange-700">*</span>
                </legend>
                <div class="mt-2 grid grid-cols-2 gap-3">
                    @foreach($roles as $role)
                        <label class="block cursor-pointer">
                            <input type="radio" id="role_{{ $role->slug }}" name="role_id" value="{{ $role->id }}"
                                data-role-slug="{{ $role->slug }}"
                                class="peer sr-only"
                                @if($loop->first) required @endif
                                {{ (string) old('role_id') === (string) $role->id ? 'checked' : '' }}>
                            <span class="flex h-full flex-col items-center justify-center gap-1 rounded-xl border-2 border-gray-200 bg-white px-3 py-4 text-center transition hover:border-blue-300 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-800 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400">
                                <i class="fas {{ $role->slug === 'learner' ? 'fa-user-graduate' : 'fa-chalkboard-teacher' }} text-lg"></i>
                                <span class="text-sm font-semibold">{{ ucfirst($role->name) }}</span>
                                <span class="text-[11px] leading-tight text-gray-500">{{ $role->slug === 'learner' ? 'Enrolling a child' : 'I want to teach' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('role_id')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-base font-semibold text-gray-700"
                    data-label-learner="Learner Details"
                    data-label-default="Your Details">Your Details</h3>
                <p class="text-xs text-gray-500 mt-1"
                    data-label-learner="The learner is the child who attends classes — use the child's own name, a username they can remember, and their parent/guardian's details at the end."
                    data-label-default="Your name, sign-in details and contact information.">Your name, sign-in details and contact information.</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    <span data-label-learner="Learner Full Name" data-label-default="Full Name">Full Name</span>
                </label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                    data-placeholder-learner="Enter the learner's full name"
                    data-placeholder-default="Enter your full name"
                    placeholder="{{ $oldIsLearner ? "Enter the learner's full name" : 'Enter your full name' }}"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('name') border-orange-500 @enderror">
                @error('name')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">
                    <span data-label-learner="Learner Username" data-label-default="Username">Username</span>
                    <span id="username-required-mark" class="text-orange-700 {{ $oldIsLearner ? '' : 'hidden' }}">*</span>
                </label>
                <input id="username" type="text" name="username" value="{{ old('username') }}"
                    data-placeholder-learner="e.g. tolu_okafor"
                    data-placeholder-default="e.g. tolu_okafor"
                    placeholder="e.g. tolu_okafor"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('username') border-orange-500 @enderror">
                @error('username')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1"
                    data-label-learner="Letters, numbers, dashes and underscores. This is what the learner signs in with, so pick something they will remember."
                    data-label-default="Letters, numbers, dashes and underscores. Optional — you can also sign in with your email.">Letters, numbers, dashes and underscores. Optional — you can also sign in with your email.</p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    <span data-label-learner="Learner Email" data-label-default="Email address">Email address</span>
                    <span id="email-required-mark" class="text-orange-700 {{ $oldIsLearner ? 'hidden' : '' }}">*</span>
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    data-placeholder-learner="Optional — the learner's own email, if any"
                    data-placeholder-default="Enter your email"
                    placeholder="{{ $oldIsLearner ? "Optional — the learner's own email, if any" : 'Enter your email' }}"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('email') border-orange-500 @enderror">
                @error('email')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1"
                    data-label-learner="Optional for a learner with no email — the parent/guardian's email below is used instead."
                    data-label-default="We use this for your login details and password resets.">We use this for your login details and password resets.</p>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">
                    <span data-label-learner="Learner Phone (optional)" data-label-default="Phone (optional)">Phone (optional)</span>
                </label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                    data-placeholder-learner="Enter the learner's phone number"
                    data-placeholder-default="Enter your phone number"
                    placeholder="{{ $oldIsLearner ? "Enter the learner's phone number" : 'Enter your phone number' }}"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Parent/Guardian details - learners only, kept clearly separate from the learner fields above -->
            <div id="parent-details" class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 {{ $oldIsLearner ? '' : 'hidden' }}">
                <h3 class="text-base font-semibold text-gray-700 mb-3">Parent/Guardian Details</h3>
                <p class="text-xs text-gray-600 mb-4">A parent account will be created automatically and the login details sent to their email address.</p>

                <div class="space-y-4">
                    <div>
                        <label for="parent_name" class="block text-sm font-medium text-gray-700">Parent/Guardian Full Name</label>
                        <input id="parent_name" type="text" name="parent_name" value="{{ old('parent_name') }}"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('parent_name') border-orange-500 @enderror"
                            placeholder="Enter the parent/guardian's full name">
                        @error('parent_name')
                            <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="parent_email" class="block text-sm font-medium text-gray-700">Parent/Guardian Email</label>
                        <input id="parent_email" type="email" name="parent_email" value="{{ old('parent_email') }}"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('parent_email') border-orange-500 @enderror"
                            placeholder="Enter the parent/guardian's email address">
                        @error('parent_email')
                            <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    <span data-label-learner="Learner Password" data-label-default="Password">Password</span>
                </label>
                <input id="password" type="password" name="password" required
                    data-placeholder-learner="Create a password for the learner (min 8 characters)"
                    data-placeholder-default="Create a password (min 8 characters)"
                    placeholder="{{ $oldIsLearner ? 'Create a password for the learner (min 8 characters)' : 'Create a password (min 8 characters)' }}"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('password') border-orange-500 @enderror">
                @error('password')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password-confirm" class="block text-sm font-medium text-gray-700">
                    <span data-label-learner="Confirm Learner Password" data-label-default="Confirm Password">Confirm Password</span>
                </label>
                <input id="password-confirm" type="password" name="password_confirmation" required
                    data-placeholder-learner="Confirm the learner's password"
                    data-placeholder-default="Confirm your password"
                    placeholder="{{ $oldIsLearner ? "Confirm the learner's password" : 'Confirm your password' }}"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium text-lg transition">
                    <span data-label-learner="Create Learner Account" data-label-default="Create Account">Create Account</span>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleInputs = Array.prototype.slice.call(document.querySelectorAll('input[name="role_id"]'));
        const parentDetails = document.getElementById('parent-details');
        const parentName = document.getElementById('parent_name');
        const parentEmail = document.getElementById('parent_email');
        const usernameInput = document.getElementById('username');
        const usernameMark = document.getElementById('username-required-mark');
        const emailInput = document.getElementById('email');
        const emailMark = document.getElementById('email-required-mark');

        // Learners and tutors share the same inputs, so every label carries both
        // wordings and we swap the visible text when the choice changes.
        function swapWording(isLearner) {
            document.querySelectorAll('[data-label-learner]').forEach(function (element) {
                const text = isLearner
                    ? element.getAttribute('data-label-learner')
                    : element.getAttribute('data-label-default');

                if (text) {
                    element.textContent = text;
                }
            });

            document.querySelectorAll('[data-placeholder-learner]').forEach(function (element) {
                const text = isLearner
                    ? element.getAttribute('data-placeholder-learner')
                    : element.getAttribute('data-placeholder-default');

                if (text) {
                    element.setAttribute('placeholder', text);
                }
            });
        }

        function selectedRoleSlug() {
            const checked = roleInputs.filter(function (input) {
                return input.checked;
            })[0];

            return checked ? (checked.getAttribute('data-role-slug') || '').toLowerCase() : '';
        }

        function applyRole() {
            const slug = selectedRoleSlug();
            const isLearner = slug === 'learner';
            const isTutor = slug === 'tutor';

            // Only a learner needs parent/guardian details.
            if (parentDetails) {
                parentDetails.classList.toggle('hidden', !isLearner);
            }
            if (parentName) {
                parentName.required = isLearner;
            }
            if (parentEmail) {
                parentEmail.required = isLearner;
            }

            // A child signs in by username and may have no email at all.
            if (usernameInput) {
                usernameInput.required = isLearner;
            }
            if (usernameMark) {
                usernameMark.classList.toggle('hidden', !isLearner);
            }
            if (emailInput) {
                emailInput.required = isTutor;
            }
            if (emailMark) {
                emailMark.classList.toggle('hidden', isLearner);
            }

            swapWording(isLearner);
        }

        roleInputs.forEach(function (input) {
            input.addEventListener('change', applyRole);
        });

        // Restore the right state after a validation redirect or a page refresh.
        applyRole();
    });
</script>
@endpush
@endsection
