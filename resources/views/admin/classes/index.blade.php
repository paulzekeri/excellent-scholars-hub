@extends('layouts.app')
@section('title', 'Manage Classes')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Manage Classes</h1><p class="text-gray-600 mt-1">All classes on the platform</p></div>
        <a href="{{ route('admin.classes.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-plus mr-1"></i> New Class</a>
    </div>
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Name</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Code</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Tutor</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Subject</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Students</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($classes as $class)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">{{ $class->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $class->code }}</td>
                        <td class="px-6 py-4 text-sm">{{ $class->tutor->user->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $class->subject ?? 'General' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $class->learners_count ?? $class->learners->count() }} / {{ $class->max_students ?? 'No limit' }}</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.classes.show', $class) }}" class="text-green-600 hover:text-green-800 mr-3" title="Manage Students"><i class="fas fa-users"></i></a>
                            <a href="{{ route('admin.classes.edit', $class) }}" class="text-blue-600 hover:text-blue-800 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">@csrf @method('DELETE')<button type="submit" class="text-orange-700 hover:text-orange-900" title="Delete"><i class="fas fa-trash"></i></button></form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $classes->links() }}</div>
    </div>
</div>
@endsection
