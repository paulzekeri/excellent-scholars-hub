<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassLibraryResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A tutor's view of the Class Library: the external links attached to the classes
 * they teach. They can add links to their own classes only, and remove the links
 * of those classes.
 */
class ClassLibraryController extends Controller
{
    public function index(Request $request)
    {
        $tutor = auth()->user()->tutor;
        $ownClassIds = Classes::where('tutor_id', $tutor->id)->pluck('id');

        // The add form and filter offer the active classes; links already on a
        // class that was switched off stay visible in the list.
        $classes = Classes::whereIn('id', $ownClassIds)->where('is_active', true)->orderBy('name')->get();

        $classFilter = is_numeric($request->query('class_id')) ? (int) $request->query('class_id') : null;
        $classFilter = in_array($classFilter, $ownClassIds->all(), true) ? $classFilter : null;

        $resources = ClassLibraryResource::with(['class', 'addedBy'])
            ->whereIn('class_id', $ownClassIds)
            ->when($classFilter, function ($query) use ($classFilter) {
                $query->where('class_id', $classFilter);
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('tutor.class-library.index', [
            'resources' => $resources,
            'classes' => $classes,
            'classFilter' => $classFilter,
        ]);
    }

    public function store(Request $request)
    {
        $tutor = auth()->user()->tutor;
        $validated = $this->validated($request, $tutor->id);

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
        $tutor = auth()->user()->tutor;

        // A tutor may only manage the library of a class they teach.
        abort_unless($resource->class && $resource->class->tutor_id === $tutor->id, 403);

        $title = $resource->title;
        $resource->delete();

        return back()->with('success', '"' . $title . '" was removed from the class library.');
    }

    /** A pasted link is trimmed and must be a real http(s) address. */
    private function validated(Request $request, int $tutorId): array
    {
        $request->merge(['url' => trim((string) $request->input('url'))]);

        return $request->validate([
            // Only the tutor's own classes can receive a link.
            'class_id' => ['required', Rule::exists('classes', 'id')->where('tutor_id', $tutorId)],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'url' => ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'type' => 'nullable|in:link,video,pdf,document',
        ], [
            'class_id.exists' => 'Choose one of your own classes.',
            'url.regex' => 'Paste the full link, starting with http:// or https:// (Google Drive links start with https://drive.google.com/).',
        ]);
    }

    private function type(?string $type): string
    {
        return in_array($type, array_keys(ClassLibraryResource::types()), true) ? $type : 'link';
    }
}
