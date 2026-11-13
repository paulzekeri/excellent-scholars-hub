{{--
    One row per broadcast batch. Shared by the broadcast report and its
    auto-refresh endpoint (admin/messages/updates).
--}}
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
