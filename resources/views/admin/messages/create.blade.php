@extends('layouts.app')
@section('title', 'Send a Categorised Message')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.messages.index') }}" class="text-blue-700 hover:text-blue-900 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Broadcast history</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Send a categorised message</h1>
        <p class="text-sm text-gray-600 mb-6">Every recipient gets the message in their inbox and a bell notification. They can reply privately.</p>

        <form action="{{ route('admin.messages.store') }}" method="POST" class="space-y-6">@csrf
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">Send to <span class="text-red-500">*</span></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($audiences as $value => $label)
                        <label class="border rounded-lg p-4 cursor-pointer hover:border-blue-500 hover:bg-blue-50/40 transition {{ old('audience') === $value ? 'border-blue-600 bg-blue-50' : 'border-gray-200' }}">
                            <input type="radio" name="audience" value="{{ $value }}" class="mr-2" @checked(old('audience', 'parents') === $value)>
                            <span class="font-medium text-gray-800">{{ $label }}</span>
                            <span class="block text-xs text-gray-500 mt-1">
                                @if(isset($counts[$value]))
                                    {{ $counts[$value] }} active account(s)
                                @else
                                    Choose one class below
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Class (only for "A single class")</label>
                <select name="class_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select a class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>
                            {{ $class->name }} — {{ $class->tutor->user->name ?? 'No tutor' }} ({{ $class->learners()->count() }} learners)
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">The class message reaches the learners, their linked parents and the class tutor.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Message <span class="text-red-500">*</span></label>
                <textarea name="body" rows="6" required maxlength="2000" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="Write your announcement...">{{ old('body') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Maximum 2,000 characters.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-paper-plane mr-1"></i> Send message</button>
                <a href="{{ route('admin.messages.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
