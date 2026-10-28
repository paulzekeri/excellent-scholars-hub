@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="mx-auto h-20 w-auto">
            <p class="text-gray-500 mt-2">Excellent Scholars Hub</p>
            <h2 class="text-xl font-semibold text-gray-700 mt-4">Forgot your password?</h2>
            <p class="text-sm text-gray-500 mt-2">Enter the email address on your account and we will send you a one-time link to choose a new password.</p>
        </div>

        @if(session('status'))
            <div class="mb-6 rounded-lg border-l-4 border-green-500 bg-green-50 p-4 text-sm text-green-800" role="alert">
                <i class="fas fa-circle-check mr-1"></i>{{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-1"></i> Please check the form</p>
                <ul class="mt-1 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                    placeholder="Enter your email">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium text-lg transition">
                    <i class="fas fa-paper-plane mr-2"></i> Email reset link
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                <i class="fas fa-arrow-left mr-1"></i> Back to sign in
            </a>
        </div>

        <div class="mt-6 rounded-lg bg-gray-50 p-4 text-xs text-gray-600">
            <p class="font-semibold text-gray-700"><i class="fas fa-circle-info mr-1"></i> No email after a few minutes?</p>
            <p class="mt-1">Check your spam folder, then ask the school administrator — they can generate a one-time reset link for you from the Users screen. The link expires after 60 minutes and works once.</p>
        </div>
    </div>
</div>
@endsection
