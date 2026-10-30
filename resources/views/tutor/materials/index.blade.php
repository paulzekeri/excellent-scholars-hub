@extends('layouts.app')
@section('title', 'Learning Materials')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Learning Materials</h1><p class="text-gray-600 mt-1">Uploaded materials for your classes</p></div>
        <a href="{{ route('tutor.materials.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-upload mr-1"></i> Upload Material</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Type</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Size</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Uploaded</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($materials as $material)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">{{ $material->title }}</td>
                        <td class="px-6 py-4 text-sm">{{ $material->class->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $material->file_type }}</td>
                        <td class="px-6 py-4 text-sm">{{ round($material->file_size / 1024, 2) }} KB</td>
                        <td class="px-6 py-4 text-sm">{{ $material->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-download"></i> Download</a>
                            <form action="{{ route('tutor.materials.destroy', $material) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">@csrf @method('DELETE')<button type="submit" class="text-orange-700 hover:text-orange-900"><i class="fas fa-trash"></i></button></form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $materials->links() }}</div>
    </div>
</div>
@endsection
