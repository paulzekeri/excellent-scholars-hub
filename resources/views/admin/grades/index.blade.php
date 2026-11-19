@extends('layouts.app')
@section('title', 'Grades')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Grades</h1>
            <p class="text-gray-600 mt-1">The class/grade list used by learner registration, admin user creation, assessment booking, enquiries and classes.</p>
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
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Add a grade</h2>
        <p class="text-sm text-gray-600 mb-4">
            Type the grade exactly as you want families to see it — for example <em>KG1 - Pre Primary</em>,
            <em>Primary 3</em> or <em>JSS1 - Junior Secondary</em>.
        </p>
        <form action="{{ route('admin.grades.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
            @csrf
            <div>
                <label for="grade_name" class="block text-xs font-medium text-gray-600 mb-1">Grade name</label>
                <input id="grade_name" type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. KG1 - Pre Primary"
                    class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="grade_sort" class="block text-xs font-medium text-gray-600 mb-1">Sort order</label>
                <input id="grade_sort" type="number" min="0" name="sort_order" value="{{ old('sort_order') }}" placeholder="e.g. 10"
                    class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i> Add grade
                </button>
            </div>
        </form>
        @error('name')<p class="mt-2 text-xs text-orange-700">{{ $message }}</p>@enderror
        @error('sort_order')<p class="mt-2 text-xs text-orange-700">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Grade list ({{ $grades->count() }})</h2>
            <p class="text-xs text-gray-500 mt-1">Rename, reorder, switch off or delete a grade. A grade in use cannot be deleted — switch it off so it stops appearing in new forms.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Grade</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Learners</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Classes</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Bookings</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($grades as $grade)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('admin.grades.update', $grade) }}" method="POST" class="flex flex-col xl:flex-row xl:items-center gap-2">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $grade->name }}" required aria-label="Grade name"
                                    class="block w-full xl:w-64 border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                <input type="number" min="0" name="sort_order" value="{{ $grade->sort_order }}" aria-label="Sort order"
                                    class="block w-full xl:w-24 border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                <select name="is_active" aria-label="Status" class="block w-full xl:w-28 border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="1" {{ $grade->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ ! $grade->is_active ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs whitespace-nowrap" title="Save changes">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $grade->learners_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $grade->classes_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $grade->enquiries_count }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($grade->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('admin.grades.destroy', $grade) }}" method="POST" class="inline" onsubmit="return confirm('Delete this grade from the list?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-orange-700 hover:text-orange-900 text-xs"><i class="fas fa-trash mr-1"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No grades yet. Add the first one above — for example KG1.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

