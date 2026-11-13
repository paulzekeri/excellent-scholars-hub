<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassLibraryResource;
use Illuminate\Http\Request;

/**
 * Class Library — the school's Google Drive videos, PDFs and documents, attached
 * to a class so the enrolled learners can open them under My Learning.
 */
class ClassLibraryController extends Controller
{
    public function index(Request $request)
    {
        $classFilter = is_numeric($request->query('class_id')) ? (int) $request->query('class_id') : null;

        $resources = ClassLibraryResource::with(['class', 'addedBy'])
            ->when($classFilter, function ($query) use ($classFilter) {
                $query->where('class_id', $classFilter);
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.class-library.index', [
            'resources' => $resources,
            'classes' => Classes::orderBy('name')->get(),
            'classFilter' => $classFilter,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        ClassLibraryResource::create([
            'class_id' => $validated['class_id'],
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'url' => $validated['url'],
            'type' => $this->type($validated['type'] ?? null),
        ]);

        return back()->with('success', '"' . $validated['title'] . '" was added to the class library.');
    }

    public function destroy(ClassLibraryResource $resource)
    {
        $title = $resource->title;
        $resource->delete();

        return back()->with('success', '"' . $title . '" was removed from the class library.');
    }

    /** A pasted link is trimmed and must be a real http(s) address. */
    private function validated(Request $request): array
    {
        $request->merge(['url' => trim((string) $request->input('url'))]);

        return $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'url' => ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'type' => 'nullable|in:link,video,pdf,document',
        ], [
            'url.regex' => 'Paste the full link, starting with http:// or https:// (Google Drive links start with https://drive.google.com/).',
        ]);
    }

    private function type(?string $type): string
    {
        return in_array($type, array_keys(ClassLibraryResource::types()), true) ? $type : 'link';
    }
}
