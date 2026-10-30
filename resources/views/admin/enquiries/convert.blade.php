@extends('layouts.app')

@section('title', 'Convert enquiry')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $enquiry->reference }}
    </a>

    <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Convert {{ $enquiry->child_name }} into a learner</h1>
        <p class="text-sm text-gray-600 mb-6">
            This creates the parent and learner accounts, enrols the child in the class and marks the enquiry
            <strong>Enrolled</strong>. Both accounts get a temporary password and must choose their own at first sign-in.
        </p>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 text-sm text-gray-700 space-y-1">
            <p><strong>Reference:</strong> {{ $enquiry->reference }} · <strong>Guardian:</strong> {{ $enquiry->guardian_name }} ({{ $enquiry->guardian_email }})</p>
            @if($assessment && $assessment->recommendedClass)
                <p><strong>Recommended class:</strong> {{ $assessment->recommendedClass->name }} · {{ $assessment->outcomeLabel() }}</p>
            @endif
        </div>

        <form action="{{ route('admin.enquiries.convert.store', $enquiry) }}" method="POST" class="space-y-6">
            @csrf

            <div class="space-y-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">The child</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="child_username" class="block text-sm font-medium text-gray-700">Username <span class="text-red-500">*</span></label>
                        <input id="child_username" type="text" name="child_username" required
                            value="{{ old('child_username', $suggestedUsername) }}"
                            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">How the child signs in. Letters, numbers, dashes and underscores.</p>
                    </div>
                    <div>
                        <label for="child_email" class="block text-sm font-medium text-gray-700">Child email (optional)</label>
                        <input id="child_email" type="email" name="child_email" value="{{ old('child_email') }}"
                            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Leave blank if the child has no mailbox — the guardian receives the details.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3 border-t border-gray-100 pt-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">The parent / guardian</h2>

                @if($existingParent)
                    <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-900">
                        Linked to the existing parent account <strong>{{ $existingParent->name }}</strong> ({{ $existingParent->email }}).
                        No new parent account is created.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="parent_username" class="block text-sm font-medium text-gray-700">Guardian username <span class="text-red-500">*</span></label>
                            <input id="parent_username" type="text" name="parent_username" required
                                value="{{ old('parent_username', $suggestedParentUsername) }}"
                                class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Guardian email</label>
                            <input type="email" value="{{ $enquiry->guardian_email }}" readonly
                                class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 text-gray-600">
                            <p class="text-xs text-gray-500 mt-1">Taken from the enquiry — it receives both sets of login details.</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-3 border-t border-gray-100 pt-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Placement</h2>
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700">Class to enrol in <span class="text-red-500">*</span></label>
                    <select id="class_id" name="class_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Choose a class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) old('class_id', $recommendedClassId) === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}{{ $class->subject ? ' — ' . $class->subject : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 border-t border-gray-100 pt-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-user-graduate mr-1"></i> Create accounts and enrol
                </button>
                <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
