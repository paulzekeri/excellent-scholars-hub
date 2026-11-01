<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Learner;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function index()
    {
        $classes = Classes::with('tutor.user')->latest()->paginate(10);
        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        return view('admin.classes.create', compact('tutors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Optional: a blank code is generated from the class name below.
            'code' => 'nullable|string|max:50|unique:classes,code',
            'tutor_id' => 'required|exists:tutors,id',
            'grade_level' => 'nullable|string',
            'subject' => 'nullable|string',
            'max_students' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = $this->generateCode($validated['code'] ?? null, $validated['name']);

        Classes::create($validated);

        return redirect()->route('admin.classes.index')->with('success', 'Class created successfully.');
    }

    public function show(Classes $class)
    {
        $class->load('tutor.user', 'learners.user');
        $learners = Learner::with('user')->where('is_active', true)->get();
        return view('admin.classes.show', compact('class', 'learners'));
    }

    public function edit(Classes $class)
    {
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        return view('admin.classes.edit', compact('class', 'tutors'));
    }

    public function update(Request $request, Classes $class)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Optional: leaving it blank keeps the code the class already has.
            'code' => 'nullable|string|max:50|unique:classes,code,' . $class->id,
            'tutor_id' => 'required|exists:tutors,id',
            'grade_level' => 'nullable|string',
            'subject' => 'nullable|string',
            'max_students' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['code'])) {
            unset($validated['code']);
        } else {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        $class->update($validated);

        return redirect()->route('admin.classes.index')->with('success', 'Class updated successfully.');
    }

    /**
     * "Class Code should not be mandatory when Creating New Class" — a blank
     * code becomes a short, unique code built from the class name.
     */
    private function generateCode(?string $code, string $name): string
    {
        $code = trim((string) $code);

        if ($code !== '') {
            return strtoupper($code);
        }

        $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?: 'CLASS');
        $base = substr($base, 0, 6);

        do {
            $candidate = $base . '-' . strtoupper(Str::random(5));
        } while (Classes::where('code', $candidate)->exists());

        return $candidate;
    }

    public function destroy(Classes $class)
    {
        $class->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Class deleted successfully.');
    }

    public function enroll(Request $request, Classes $class)
    {
        $request->validate([
            'learner_id' => 'required|exists:learners,id',
        ]);

        // Check if already enrolled
        if ($class->learners()->where('learner_id', $request->learner_id)->exists()) {
            return back()->with('error', 'Learner is already enrolled in this class.');
        }

        // Check max students — a class without a maximum is unlimited.
        if ($class->max_students !== null && $class->learners()->count() >= $class->max_students) {
            return back()->with('error', 'Class has reached maximum capacity.');
        }

        $class->learners()->attach($request->learner_id);

        return back()->with('success', 'Learner enrolled successfully.');
    }

    public function unenroll(Classes $class, Learner $learner)
    {
        $class->learners()->detach($learner->id);
        return back()->with('success', 'Learner unenrolled successfully.');
    }
}
