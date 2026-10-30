@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
@php
    $selectedRoleId = old('role_id', $user->role_id);
    $selectedRoleSlug = optional($roles->firstWhere('id', $selectedRoleId))->slug;
    $selectedParentId = old('parent_id', optional($user->learner)->parent_id);
@endphp
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit User: {{ $user->name }}</h1>
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input id="name" type="text" name="name" value="{{ $user->name }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <div class="mt-1 flex gap-2">
                        <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}"
                            class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g. tolu_okafor">
                        <button type="button" id="suggest-username" class="shrink-0 rounded-lg border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">Suggest</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Required for parents and learners — the login field accepts it or the email.</p>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email <span id="email-required-mark" class="text-orange-700 {{ $selectedRoleSlug === 'learner' ? 'hidden' : '' }}">*</span></label>
                    <input id="email" type="email" name="email" value="{{ $user->email }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Optional for a child with no email — the guardian's address is used instead.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" value="{{ $user->phone }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role_id" name="role_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-slug="{{ $role->slug }}" {{ $selectedRoleId == $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="learner-parent-field" class="md:col-span-2 {{ $selectedRoleSlug === 'learner' ? '' : 'hidden' }}">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent / Guardian (learners only)</label>
                    <select id="parent_id" name="parent_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select the parent/guardian</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ (string) $selectedParentId === (string) $parent->id ? 'selected' : '' }}>
                                {{ $parent->user->name }} ({{ $parent->user->email }}){{ $parent->user->is_active ? '' : ' - inactive' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($parents->isEmpty())
                            No parent accounts exist yet. Create one first (choose the <strong>Parent</strong> role) — every child must have a guardian whose email receives their login details.
                        @else
                            Every child must stay linked to a guardian. Password resets and account details go to the guardian's email.
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="is_active" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-1"></i> Update User
            </button>
        </form>

        @if($user->isParent())
            @php $children = optional($user->parent)->children ?? collect(); @endphp
            <div class="mt-8 border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-1">
                    <i class="fas fa-user-graduate text-sun-ink mr-2"></i>Children &amp; logins
                </h2>
                <p class="text-sm text-gray-500 mb-3">
                    Add a child account with its own username. The login details are emailed to this guardian
                    (@if($user->email){{ $user->email }}@else <span class="text-sun-ink">add an email to this guardian first</span>@endif),
                    and the child chooses their own password at first sign-in.
                </p>

                <form action="{{ route('admin.users.children.store', $user) }}" method="POST"
                    class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="child_name" class="block text-xs font-medium text-gray-600">Child's name</label>
                            <input id="child_name" type="text" name="name" value="{{ old('name') }}" required
                                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="child_username" class="block text-xs font-medium text-gray-600">Username</label>
                            <input id="child_username" type="text" name="username" value="{{ old('username') }}" required
                                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g. tolu_okafor">
                        </div>
                        <div>
                            <label for="child_email" class="block text-xs font-medium text-gray-600">Email (optional)</label>
                            <input id="child_email" type="email" name="email" value="{{ old('email') }}"
                                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="child_password" class="block text-xs font-medium text-gray-600">Temporary password</label>
                            <input id="child_password" type="text" name="password" required
                                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="At least 8 characters">
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                        <i class="fas fa-user-plus mr-1"></i> Add child
                    </button>
                </form>

                @if($children->count())
                    <ul class="space-y-2">
                        @foreach($children as $child)
                            <li class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                                <span class="text-sm text-gray-700">
                                    {{ optional($child->user)->name ?? 'Deleted learner' }}
                                    @if(optional($child->user)->username)
                                        <span class="text-xs text-gray-500 font-mono">{{ optional($child->user)->username }}</span>
                                    @endif
                                    @if(optional($child->user)->email)
                                        <span class="text-xs text-gray-500">({{ $child->user->email }})</span>
                                    @else
                                        <span class="text-xs text-gray-500">(no email — via this guardian)</span>
                                    @endif
                                    @if(optional($child->user)->must_change_password)
                                        <span class="ml-1 inline-block px-2 py-0.5 text-[10px] rounded-full bg-sun-light text-sun-ink">Awaiting first sign-in</span>
                                    @endif
                                </span>
                                @if($child->user)
                                    <a href="{{ route('admin.users.edit', $child->user) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-edit"></i> Manage
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-600 bg-sun-light border border-sun-edge rounded-lg px-4 py-3">
                        No children are linked to this guardian yet. Use the <strong>Add child</strong> form above to create one.
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role_id');
        const parentField = document.getElementById('learner-parent-field');
        const parentSelect = document.getElementById('parent_id');
        const emailInput = document.getElementById('email');
        const emailMark = document.getElementById('email-required-mark');
        const nameInput = document.getElementById('name');

        function toggleParentField() {
            const selected = roleSelect.options[roleSelect.selectedIndex];
            const isLearner = !!selected && selected.dataset.slug === 'learner';

            parentField.classList.toggle('hidden', !isLearner);
            parentSelect.required = isLearner;

            // A child without an email is normal; staff and parents need one.
            if (emailInput) {
                emailInput.required = !isLearner;
            }
            if (emailMark) {
                emailMark.classList.toggle('hidden', isLearner);
            }

            if (!isLearner) {
                parentSelect.value = '';
            }
        }

        function slugify(value) {
            return value
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[^a-z0-9\s_-]/g, '')
                .trim()
                .replace(/[\s-]+/g, '_')
                .replace(/_+/g, '_')
                .substring(0, 30);
        }

        const suggestButton = document.getElementById('suggest-username');
        if (suggestButton) {
            suggestButton.addEventListener('click', function () {
                const username = document.getElementById('username');
                if (username && !username.value && nameInput && nameInput.value) {
                    username.value = slugify(nameInput.value);
                }
            });
        }

        roleSelect.addEventListener('change', toggleParentField);
        toggleParentField();
    });
</script>
@endpush
