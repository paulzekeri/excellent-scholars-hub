@extends('layouts.app')

@section('title', 'Edit Enquiry')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $enquiry->reference ?: 'enquiry' }}
    </a>

    <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Edit enquiry {{ $enquiry->reference }}</h1>
        <p class="text-sm text-gray-600 mb-6">Correct a name, phone number or note. The pipeline status is changed from the enquiry page so every move stays on the timeline.</p>

        <form action="{{ route('admin.enquiries.update', $enquiry) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.enquiries._form', ['enquiry' => $enquiry, 'owners' => $owners])

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Save changes
                </button>
                <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
