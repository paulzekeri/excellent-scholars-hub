@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
@php
    $selectedRoleId = old('role_id', $user->role_id);
    $selectedRoleSlug = optional($roles->firstWhere('id', $selectedRoleId))->slug;
    $selectedParentId = old('parent_id', optional($user->learner)->parent_id);
@endphp
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit User: {{ $user->name }}</h1>
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" value="{{ $user->name }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ $user->email }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" value="{{ $user->phone }}" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role_id" name="role_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-slug="{{ $role->slug }}" {{ $selectedRoleId == $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="learner-parent-field" class="md:col-span-2 {{ $selectedRoleSlug === 'learner' ? '' : 'hidden' }}">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent / Guardian (learners only)</label>
                    <select id="parent_id" name="parent_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">No parent linked yet</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ (string) $selectedParentId === (string) $parent->id ? 'selected' : '' }}>
                                {{ $parent->user->name }} ({{ $parent->user->email }}){{ $parent->user->is_active ? '' : ' - inactive' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($parents->isEmpty())
                            No parent accounts exist yet. Create one first (choose the <strong>Parent</strong> role), then link this learner to it - a parent can be linked to many learners.
                        @else
                            The learner only appears on this parent's dashboard once linked. Select "No parent linked yet" to unlink them.
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="is_active" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-save mr-1"></i> Update User
            </button>
        </form>

        @if($user->isParent())
            @php $children = optional($user->parent)->children ?? collect(); @endphp
            <div class="mt-8 border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-1">
                    <i class="fas fa-user-graduate text-yellow-600 mr-2"></i>Linked learners
                </h2>
                <p class="text-sm text-gray-500 mb-3">Learners linked to this parent. Links are managed from the learner's own Edit page.</p>
                @if($children->count())
                    <ul class="space-y-2">
                        @foreach($children as $child)
                            <li class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                                <span class="text-sm text-gray-700">
                                    {{ optional($child->user)->name ?? 'Deleted learner' }}
                                    @if(optional($child->user)->email)
                                        <span class="text-xs text-gray-400">({{ $child->user->email }})</span>
                                    @endif
                                </span>
                                @if($child->user)
                                    <a href="{{ route('admin.users.edit', $child->user) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                        <i class="fas fa-edit"></i> Manage
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-600 bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3">
                        No learners are linked to this parent yet. Open a learner's Edit page and choose this parent in the <strong>Parent / Guardian</strong> field.
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

        function toggleParentField() {
            const selected = roleSelect.options[roleSelect.selectedIndex];
            const isLearner = !!selected && selected.dataset.slug === 'learner';

            parentField.classList.toggle('hidden', !isLearner);

            if (!isLearner) {
                parentSelect.value = '';
            }
        }

        roleSelect.addEventListener('change', toggleParentField);
        toggleParentField();
    });
</script>
@endpush
