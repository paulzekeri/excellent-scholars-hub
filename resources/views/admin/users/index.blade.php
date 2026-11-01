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
            <p class="font-semibold text-gray-800"><i class="fas fa-id-card text-orange-700 mr-1"></i> Login details for {{ $provisioned['name'] }}</p>
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
            <p class="font-semibold text-gray-800"><i class="fas fa-key text-orange-700 mr-1"></i> One-time reset link for {{ session('password_reset_user') }}</p>
            <p class="text-sm text-gray-600 mt-1">Send this to the user privately. It works once and expires in 60 minutes, so do not post it in a group chat.</p>
            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                <input id="password-reset-link" type="text" readonly value="{{ session('password_reset_link') }}" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-xs font-mono bg-gray-50">
                <button type="button" id="copy-reset-link" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-copy mr-1"></i> Copy</button>
                <a href="{{ session('password_reset_link') }}" class="bg-white border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition"><i class="fas fa-arrow-up-right-from-square mr-1"></i> Open</a>
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="bg-white rounded-xl shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label for="role-filter" class="block text-xs font-medium text-gray-600 mb-1">Role</label>
                <select id="role-filter" name="role" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->slug }}" {{ $roleFilter === $role->slug ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }} ({{ $roleCounts[$role->id] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status-filter" class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="status-filter" name="status" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Any status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="user-search" class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input id="user-search" type="search" name="q" value="{{ $search }}" placeholder="Name, username or email"
                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-filter mr-1"></i> Filter</button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Reset</a>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
            <span class="text-gray-500">Quick filter:</span>
            <a href="{{ route('admin.users.index') }}"
                class="rounded-full px-3 py-1 font-semibold {{ empty($roleFilter) || $roleFilter === 'all' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                All users ({{ $roleCounts->sum() }})
            </a>
            @foreach($roles as $role)
                <a href="{{ route('admin.users.index', array_filter(['role' => $role->slug, 'q' => $search ?: null, 'status' => $statusFilter ?: null])) }}"
                    class="rounded-full px-3 py-1 font-semibold {{ $roleFilter === $role->slug ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ ucfirst($role->name) }} ({{ $roleCounts[$role->id] ?? 0 }})
                </a>
            @endforeach
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Parent / Guardian</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($user->username)
                                    <span class="font-mono text-gray-800">{{ $user->username }}</span>
                                @else
                                    <span class="text-gray-500">&mdash;</span>
                                @endif
                                @if($user->isLearner())
                                    @php $guardianEmail = optional(optional(optional($user->learner)->parent)->user)->email; @endphp
                                    @if($guardianEmail)
                                        <span class="block text-xs text-gray-500">Guardian: {{ $guardianEmail }}</span>
                                    @else
                                        <span class="block text-xs text-sun-ink">No guardian email</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($user->email)
                                    {{ $user->email }}
                                @else
                                    <span class="text-gray-500">No email &mdash; via guardian</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $user->phone ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($user->role->slug == 'ceo') bg-orange-100 text-orange-800
                                    @elseif($user->role->slug == 'admin') bg-orange-100 text-orange-900
                                    @elseif($user->role->slug == 'tutor') bg-green-100 text-green-800
                                    @elseif($user->role->slug == 'learner') bg-blue-100 text-blue-800
                                    @else bg-sun-light text-sun-ink @endif">
                                    {{ ucfirst($user->role->name) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($user->isLearner())
                                    @if($user->learner && $user->learner->parent && $user->learner->parent->user)
                                        {{ $user->learner->parent->user->name }}
                                    @else
                                        <span class="text-sun-ink"><i class="fas fa-exclamation-circle mr-1"></i>Not linked</span>
                                    @endif
                                @elseif($user->isParent())
                                    @if($user->parent)
                                        @php $childCount = $user->parent->children->count(); @endphp
                                        <span class="text-gray-500">Parent of {{ $childCount }} {{ \Illuminate\Support\Str::plural('learner', $childCount) }}</span>
                                    @else
                                        <span class="text-gray-500">No parent profile</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-900' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($user->must_change_password)
                                    <span class="mt-1 block px-2 py-1 text-xs rounded-full bg-sun-light text-sun-ink">
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
                                    <button type="submit" class="text-orange-700 hover:text-orange-800"><i class="fas fa-key"></i> Reset password</button>
                                </form>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-orange-700 hover:text-orange-900"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                No users match this filter.
                            </td>
                        </tr>
                    @endforelse
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
