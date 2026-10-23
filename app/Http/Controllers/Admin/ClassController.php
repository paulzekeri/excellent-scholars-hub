<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Learner;
use App\Models\Tutor;
use Illuminate\Http\Request;

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
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:classes',
            'tutor_id' => 'required|exists:tutors,id',
            'grade_level' => 'nullable|string',
            'subject' => 'nullable|string',
            'max_students' => 'integer|min:1',
            'description' => 'nullable|string',
        ]);

        Classes::create($request->all());

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
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:classes,code,' . $class->id,
            'tutor_id' => 'required|exists:tutors,id',
            'grade_level' => 'nullable|string',
            'subject' => 'nullable|string',
            'max_students' => 'integer|min:1',
            'description' => 'nullable|string',
        ]);

        $class->update($request->all());

        return redirect()->route('admin.classes.index')->with('success', 'Class updated successfully.');
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

        // Check max students
        if ($class->learners()->count() >= $class->max_students) {
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
