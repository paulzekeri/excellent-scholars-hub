@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New User</h1>
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role_id" name="role_id" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-slug="{{ $role->slug }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="learner-parent-field" class="md:col-span-2 {{ old('role_id') && optional(\App\Models\Role::find(old('role_id')))->slug === 'learner' ? '' : 'hidden' }}">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent / Guardian (learners only)</label>
                    <select id="parent_id" name="parent_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">No parent linked yet</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->user->name }} ({{ $parent->user->email }}){{ $parent->user->is_active ? '' : ' - inactive' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($parents->isEmpty())
                            No parent accounts exist yet. Create one first (choose the <strong>Parent</strong> role), then link this learner to it - a parent can be linked to many learners.
                        @else
                            The learner only appears on this parent's dashboard once linked. Leave as "No parent linked yet" to link them later from the learner's Edit page.
                        @endif
                    </p>
                </div>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition w-full md:w-auto">
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
