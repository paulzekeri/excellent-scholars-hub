@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Users</h1>
            <p class="text-gray-600 mt-1">All registered platform users</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-1"></i> New User
        </a>
    </div>

    @if(session('provisioned_credentials'))
        @php $provisioned = session('provisioned_credentials'); @endphp
        <div class="bg-white rounded-xl shadow-md border-l-4 border-orange-500 p-5 mb-6">
            <p class="font-semibold text-gray-800"><i class="fas fa-id-card text-orange-500 mr-1"></i> Login details for {{ $provisioned['name'] }}</p>
            <p class="text-sm text-gray-600 mt-1">
                Email delivery is not configured on this installation @if($provisioned['sent_to'])(target: {{ $provisioned['sent_to'] }}) @endif
                so hand these over privately. The owner will be asked to choose their own password at first sign-in.
            </p>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                    <span class="block text-xs uppercase tracking-wide text-gray-500">Username</span>
                    <span class="font-mono text-gray-800">{{ $provisioned['username'] }}</span>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                    <span class="block text-xs uppercase tracking-wide text-gray-500">Temporary password</span>
                    <span class="font-mono text-gray-800">{{ $provisioned['password'] }}</span>
                </div>
            </div>
        </div>
    @endif

    @if(session('password_reset_link'))
        <div class="bg-white rounded-xl shadow-md border-l-4 border-orange-500 p-5 mb-6">
            <p class="font-semibold text-gray-800"><i class="fas fa-key text-orange-500 mr-1"></i> One-time reset link for {{ session('password_reset_user') }}</p>
            <p class="text-sm text-gray-600 mt-1">Send this to the user privately. It works once and expires in 60 minutes, so do not post it in a group chat.</p>
            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                <input id="password-reset-link" type="text" readonly value="{{ session('password_reset_link') }}" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-xs font-mono bg-gray-50">
                <button type="button" id="copy-reset-link" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-copy mr-1"></i> Copy</button>
                <a href="{{ session('password_reset_link') }}" class="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition"><i class="fas fa-arrow-up-right-from-square mr-1"></i> Open</a>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent / Guardian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($user->username)
                                    <span class="font-mono text-gray-800">{{ $user->username }}</span>
                                @else
                                    <span class="text-gray-400">&mdash;</span>
                                @endif
                                @if($user->isLearner())
                                    @php $guardianEmail = optional(optional(optional($user->learner)->parent)->user)->email; @endphp
                                    @if($guardianEmail)
                                        <span class="block text-xs text-gray-400">Guardian: {{ $guardianEmail }}</span>
                                    @else
                                        <span class="block text-xs text-amber-600">No guardian email</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($user->email)
                                    {{ $user->email }}
                                @else
                                    <span class="text-gray-400">No email &mdash; via guardian</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $user->phone ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($user->role->slug == 'ceo') bg-purple-100 text-purple-800
                                    @elseif($user->role->slug == 'admin') bg-red-100 text-red-800
                                    @elseif($user->role->slug == 'tutor') bg-green-100 text-green-800
                                    @elseif($user->role->slug == 'learner') bg-blue-100 text-blue-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($user->role->name) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($user->isLearner())
                                    @if($user->learner && $user->learner->parent && $user->learner->parent->user)
                                        {{ $user->learner->parent->user->name }}
                                    @else
                                        <span class="text-amber-600"><i class="fas fa-exclamation-circle mr-1"></i>Not linked</span>
                                    @endif
                                @elseif($user->isParent())
                                    @if($user->parent)
                                        @php $childCount = $user->parent->children->count(); @endphp
                                        <span class="text-gray-400">Parent of {{ $childCount }} {{ \Illuminate\Support\Str::plural('learner', $childCount) }}</span>
                                    @else
                                        <span class="text-gray-400">No parent profile</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($user->must_change_password)
                                    <span class="mt-1 block px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">
                                        Awaiting first sign-in
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('admin.users.password-reset', $user) }}" method="POST" class="inline mr-3" onsubmit="return confirm('Send a one-time password reset link for this user?')">
                                    @csrf
                                    <button type="submit" class="text-orange-600 hover:text-orange-800"><i class="fas fa-key"></i> Reset password</button>
                                </form>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">
            {{ $users->links() }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('copy-reset-link');
        const field = document.getElementById('password-reset-link');

        if (!button || !field) return;

        button.addEventListener('click', function () {
            const done = function () {
                const original = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-1"></i> Copied';
                setTimeout(function () { button.innerHTML = original; }, 1500);
            };

            field.select();

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(field.value).then(done).catch(function () {});
                return;
            }

            try { document.execCommand('copy'); done(); } catch (error) {}
        });
    });
</script>
@endsection
