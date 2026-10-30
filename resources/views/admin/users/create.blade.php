@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create New User</h1>
        <p class="text-sm text-gray-600 mb-6">
            The account is created with a temporary password. The login details are emailed, and the owner
            chooses their own password the first time they sign in.
        </p>

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username <span class="text-orange-700">*</span></label>
                    <div class="mt-1 flex gap-2">
                        <input id="username" type="text" name="username" value="{{ old('username') }}"
                            class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g. tolu_okafor">
                        <button type="button" id="suggest-username" class="shrink-0 rounded-lg border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">Suggest</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Letters, numbers, dashes and underscores. Required for parents and learners — it is how they sign in.</p>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email <span id="email-required-mark" class="text-orange-700">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Required for staff and parents. <strong>Optional for a child</strong> with no email — the guardian's address is used instead.</p>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Temporary password</label>
                    <div class="mt-1 flex gap-2">
                        <input id="password" type="text" name="password" required
                            class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="At least 8 characters">
                        <button type="button" id="generate-password" class="shrink-0 rounded-lg border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">Generate</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Emailed once, then replaced by the account owner at first sign-in.</p>
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role_id" name="role_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-slug="{{ $role->slug }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="learner-parent-field" class="md:col-span-2 hidden">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent / Guardian (learners only)</label>
                    <select id="parent_id" name="parent_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select the parent/guardian</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->user->name }} ({{ $parent->user->email }}){{ $parent->user->is_active ? '' : ' - inactive' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($parents->isEmpty())
                            No parent accounts exist yet. Create one first (choose the <strong>Parent</strong> role) — every child must have a guardian whose email receives their login details.
                        @else
                            Every child must be linked to a guardian. The login details and future password resets go to the guardian's email.
                        @endif
                    </p>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition w-full md:w-auto">
                <i class="fas fa-save mr-1"></i> Create User
            </button>
        </form>
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

        function isLearnerRole() {
            const selected = roleSelect.options[roleSelect.selectedIndex];
            return !!selected && selected.dataset.slug === 'learner';
        }

        function toggleLearnerFields() {
            const learner = isLearnerRole();

            parentField.classList.toggle('hidden', !learner);
            parentSelect.required = learner;

            // A child without an email is normal; staff and parents need one.
            emailInput.required = !learner;
            emailMark.classList.toggle('hidden', learner);

            if (!learner) {
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

        document.getElementById('suggest-username').addEventListener('click', function () {
            const username = document.getElementById('username');
            if (!username.value && nameInput.value) {
                username.value = slugify(nameInput.value);
            }
        });

        document.getElementById('generate-password').addEventListener('click', function () {
            const alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            const values = new Uint32Array(12);
            window.crypto.getRandomValues(values);
            let password = '';
            values.forEach(function (value) {
                password += alphabet[value % alphabet.length];
            });
            document.getElementById('password').value = password;
        });

        roleSelect.addEventListener('change', toggleLearnerFields);
        toggleLearnerFields();
    });
</script>
@endpush
