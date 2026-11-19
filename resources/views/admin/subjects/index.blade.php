@extends('layouts.app')
@section('title', 'Subjects')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Subjects</h1>
            <p class="text-gray-600 mt-1">The subject list behind the class form. Add Biology, Physics, Chemistry — any subject the school teaches.</p>
        </div>
        <a href="{{ route('admin.classes.index') }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left mr-1"></i> Back to Classes</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-orange-100 border border-orange-400 text-orange-800 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Add a subject</h2>
        <p class="text-sm text-gray-600 mb-4">
            A subject added here appears in every class form. You can also type a brand-new subject
            directly on the class form and it is added to this list automatically.
        </p>
        <form action="{{ route('admin.subjects.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Biology"
                class="block w-full sm:max-w-sm border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">
                <i class="fas fa-plus mr-1"></i> Add subject
            </button>
        </form>
        @error('name')<p class="mt-2 text-xs text-orange-700">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Subject list ({{ $subjects->count() }})</h2>
            <p class="text-xs text-gray-500 mt-1">Rename a subject, switch it off so it stops appearing on new class forms, or delete it when no class teaches it.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Classes</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Teachers</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($subjects as $subject)
                    @php
                        $teachers = $subject->classSubjects
                            ->map(function ($row) {
                                return optional(optional($row->tutor)->user)->name;
                            })
                            ->filter()
                            ->unique()
                            ->values();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('admin.subjects.update', $subject) }}" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-2">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $subject->name }}" required aria-label="Subject name"
                                    class="block w-full sm:w-56 border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                <select name="is_active" aria-label="Status" class="block w-full sm:w-32 border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="1" {{ $subject->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ ! $subject->is_active ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs whitespace-nowrap" title="Save changes">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $subject->class_subjects_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            @if($teachers->isEmpty())
                                <span class="text-gray-400">—</span>
                            @else
                                {{ $teachers->take(3)->implode(', ') }}{{ $teachers->count() > 3 ? ' +' . ($teachers->count() - 3) . ' more' : '' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($subject->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" class="inline" onsubmit="return confirm('Delete this subject from the list?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-orange-700 hover:text-orange-900 text-xs"><i class="fas fa-trash mr-1"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No subjects yet. Add the first one above — for example Mathematics.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
