@extends('layouts.plain')

@section('title', 'Choose a new password')

@section('content')
    <div class="text-center mb-6">
        <div class="mx-auto h-12 w-12 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center">
            <i class="fas fa-lock text-xl"></i>
        </div>
        <h1 class="mt-4 text-xl font-bold text-gray-800">Choose your own password</h1>
        <p class="mt-2 text-sm text-gray-600">
            Your account was created by the school with a temporary password.
            Pick a new one now — you will not be able to use the rest of the app until you do.
        </p>
    </div>

    <div class="mb-6 rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-900">
        Signing in as
        <strong>{{ auth()->user()->username ?: auth()->user()->email }}</strong>
        @if(auth()->user()->username && auth()->user()->email)
            <span class="text-blue-700">({{ auth()->user()->email }})</span>
        @endif
    </div>

    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
        @csrf

        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700">Temporary password</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password" autofocus
                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('current_password') border-orange-500 @enderror"
                placeholder="Enter the password from your email">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('password') border-orange-500 @enderror"
                placeholder="At least 8 characters">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                placeholder="Repeat the new password">
        </div>

        <button type="submit"
            class="w-full py-3 px-4 rounded-lg text-white bg-blue-600 hover:bg-blue-700 font-semibold transition">
            <i class="fas fa-check mr-1"></i> Save new password
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-sign-out-alt mr-1"></i> Sign out instead
        </button>
    </form>
@endsection
