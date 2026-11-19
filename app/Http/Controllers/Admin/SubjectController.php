<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The school's subject catalogue (Biology, Physics, Chemistry, Mathematics, …).
 *
 * These names fill the subject dropdown on the class form. A subject can also
 * be created straight from the class form ("Add a new subject…"), so the office
 * is never blocked when a new subject comes up; this page is where they tidy the
 * list afterwards (rename, switch off, delete).
 */
class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with(['classSubjects.tutor.user'])
            ->withCount('classSubjects')
            ->orderBy('name')
            ->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subjects,name',
        ], [
            'name.unique' => 'That subject is already in the list.',
            'name.required' => 'Type the subject name, e.g. Biology.',
        ]);

        $subject = Subject::create([
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return back()->with('success', $subject->name . ' was added to the subject list.');
    }

    public function update(Request $request, Subject $subject)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'name')->ignore($subject->id),
            ],
            // Leave the box unticked/0 to hide the subject from new class forms.
            'is_active' => 'nullable|boolean',
        ], [
            'name.unique' => 'Another subject already uses that name.',
        ]);

        // The class rows store the subject id, so a rename shows up on every
        // class that teaches it immediately.
        $subject->update([
            'name' => $validated['name'],
            // Leave the current status alone when the request says nothing.
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : $subject->is_active,
        ]);

        return back()->with('success', $subject->name . ' was updated.');
    }

    public function destroy(Subject $subject)
    {
        // Never silently detach a subject from a class: the office is told which
        // classes still teach it and can remove it from those classes first.
        if ($subject->classSubjects()->exists()) {
            return back()->with(
                'error',
                $subject->name . ' is still taught in one or more classes. Remove it from those classes first.'
            );
        }

        $name = $subject->name;
        $subject->delete();

        return back()->with('success', $name . ' was removed from the subject list.');
    }
}
