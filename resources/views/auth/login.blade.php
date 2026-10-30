@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0a2550] via-[#0d3070] to-[#1d4ed8] py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 border-t-4 border-orange-500">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="mx-auto h-20 w-auto">
            <p class="text-gray-500 mt-2">Excellent Scholars Hub</p>
            <h2 class="text-xl font-semibold text-gray-700 mt-4">Sign in to your account</h2>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <div>
                <label for="login" class="block text-sm font-medium text-gray-700">Email or username</label>
                <input id="login" type="text" name="login" value="{{ old('login') }}" required autocomplete="username" autofocus
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('login') border-orange-500 @enderror"
                    placeholder="Email address or username">
                @error('login')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('password') border-orange-500 @enderror"
                    placeholder="Enter your password">
                @error('password')
                    <p class="text-orange-700 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ old('remember') ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Forgot your password?</a>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium text-lg transition">
                    Sign in
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-500">Register here</a>
            </p>
        </div>
    </div>
</div>
@endsection
