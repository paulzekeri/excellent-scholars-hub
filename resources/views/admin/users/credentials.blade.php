@if(session('provisioned_credentials'))
    @php $provisioned = session('provisioned_credentials'); @endphp
    <div class="bg-white rounded-xl shadow-md border-l-4 border-orange-500 p-5 mb-6">
        <p class="font-semibold text-gray-800"><i class="fas fa-id-card text-orange-700 mr-1"></i> Login details for {{ $provisioned['name'] }}</p>
        <p class="text-sm text-gray-600 mt-1">
            No email could be sent @if($provisioned['sent_to'])(intended for {{ $provisioned['sent_to'] }})@endif,
            so hand these details over privately.
            @if(! empty($provisioned['must_change_password']))
                The owner will be asked to choose their own password at first sign-in.
            @else
                Ask them to change this password after they sign in.
            @endif
        </p>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                <span class="block text-xs uppercase tracking-wide text-gray-500">Username</span>
                <span class="font-mono text-gray-800">{{ $provisioned['username'] }}</span>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                <span class="block text-xs uppercase tracking-wide text-gray-500">Password</span>
                <span class="font-mono text-gray-800">{{ $provisioned['password'] }}</span>
            </div>
        </div>
    </div>
@endif
