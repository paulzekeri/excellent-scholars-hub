@extends('layouts.app')
@section('title', 'Message Broadcasts')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Categorised Messages</h1>
            <p class="text-gray-600 mt-1">Send to parents, tutors, students, a single class or everyone</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('messages.index') }}" class="bg-white border border-blue-600 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-inbox mr-1"></i> My inbox</a>
            <a href="{{ route('admin.messages.create') }}" class="bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-paper-plane mr-1"></i> New message</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-600">
            <p class="text-gray-500 text-sm">Broadcasts sent</p>
            <p class="text-2xl font-bold text-gray-800">{{ $batches->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-orange-500">
            <p class="text-gray-500 text-sm">Delivered copies</p>
            <p class="text-2xl font-bold text-gray-800">{{ $batches->sum('recipients') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500">
            <p class="text-gray-500 text-sm">Unread copies</p>
            <p class="text-2xl font-bold text-gray-800">{{ $unreadTotal }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Sent</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Audience</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Recipients</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Read</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Sender</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $batch['sent_at']->format('M d, Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $batch['audience_label'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($batch['body'], 90) }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $batch['recipients'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $batch['read'] }} / {{ $batch['recipients'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $batch['sender'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            No categorised messages have been sent yet. Use <strong>New message</strong> to reach a group.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
