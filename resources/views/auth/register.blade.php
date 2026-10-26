@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="mx-auto h-20 w-auto">
            <p class="text-gray-500 mt-2">Create your account</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror"
                    placeholder="Enter your full name">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror"
                    placeholder="Enter your email">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Enter your phone number">
            </div>

            <div>
                <label for="role_id" class="block text-sm font-medium text-gray-700">I am a</label>
                <select id="role_id" name="role_id" required
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('role_id') border-red-500 @enderror">
                    <option value="">Select your role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
                @error('role_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Parent Details Section - shown only for Learners -->
            <div id="parent-details" class="border-t border-gray-200 pt-4 {{ old('role_id') && \App\Models\Role::find(old('role_id'))?->slug === 'learner' ? '' : 'hidden' }}">
                <h3 class="text-md font-semibold text-gray-700 mb-3">Parent/Guardian Information</h3>
                <p class="text-xs text-gray-500 mb-4">A parent account will be created automatically and login details sent to their email.</p>
                
                <div class="space-y-4">
                    <div>
                        <label for="parent_name" class="block text-sm font-medium text-gray-700">Parent/Guardian Name</label>
                        <input id="parent_name" type="text" name="parent_name" value="{{ old('parent_name') }}"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('parent_name') border-red-500 @enderror"
                            placeholder="Enter parent's full name">
                        @error('parent_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="parent_email" class="block text-sm font-medium text-gray-700">Parent/Guardian Email</label>
                        <input id="parent_email" type="email" name="parent_email" value="{{ old('parent_email') }}"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('parent_email') border-red-500 @enderror"
                            placeholder="Enter parent's email address">
                        @error('parent_email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-500 @enderror"
                    placeholder="Create a password (min 8 characters)">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password-confirm" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input id="password-confirm" type="password" name="password_confirmation" required
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Confirm your password">
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-medium text-lg transition">
                    Create Account
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role_id');
        const parentDetails = document.getElementById('parent-details');
        const parentName = document.getElementById('parent_name');
        const parentEmail = document.getElementById('parent_email');

        function toggleParentFields() {
            const selectedOption = roleSelect.options[roleSelect.selectedIndex];
            const selectedText = selectedOption ? selectedOption.text.toLowerCase() : '';

            if (selectedText === 'learner') {
                parentDetails.classList.remove('hidden');
                parentName.setAttribute('required', 'required');
                parentEmail.setAttribute('required', 'required');
            } else {
                parentDetails.classList.add('hidden');
                parentName.removeAttribute('required');
                parentEmail.removeAttribute('required');
            }
        }

        roleSelect.addEventListener('change', toggleParentFields);
        
        // Run on page load if there's an old value
        toggleParentFields();
    });
</script>
@endpush
@endsection
