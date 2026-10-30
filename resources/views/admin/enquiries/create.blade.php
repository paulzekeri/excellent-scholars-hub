@extends('layouts.app')

@section('title', 'Capture Enquiry')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.enquiries.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to admissions
    </a>

    <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Capture an enquiry</h1>
        <p class="text-sm text-gray-600 mb-6">
            Just the details from the WhatsApp message or call — no account is created yet. You can book the
            assessment and convert the child to a learner later.
        </p>

        <form action="{{ route('admin.enquiries.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.enquiries._form', ['enquiry' => $enquiry, 'owners' => $owners])

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-user-plus mr-1"></i> Save enquiry
                </button>
                <a href="{{ route('admin.enquiries.index') }}" class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
