<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:learner');
    }

    public function index()
    {
        $learner = auth()->user()->learner;
        $classIds = $learner->classes()->pluck('classes.id');
        $assignments = Assignment::whereIn('class_id', $classIds)
            ->with(['submissions' => function($q) use ($learner) {
                $q->where('learner_id', $learner->id);
            }])
            ->latest()
            ->paginate(12);
        return view('learner.assignments', compact('assignments'));
    }

    public function submit(Assignment $assignment)
    {
        $learner = auth()->user()->learner;
        // Ensure learner is enrolled
        if (!$learner->classes()->where('classes.id', $assignment->class_id)->exists()) {
            abort(403);
        }
        return view('learner.submit-assignment', compact('assignment'));
    }

    public function store(Request $request, Assignment $assignment)
    {
        $learner = auth()->user()->learner;
        
        if (!$learner->classes()->where('classes.id', $assignment->class_id)->exists()) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|max:102400',
            'notes' => 'nullable|string|max:1000',
        ]);

        $path = $request->file('file')->store('submissions', 'public');

        Submission::create([
            'assignment_id' => $assignment->id,
            'learner_id' => $learner->id,
            'file_path' => $path,
            'content' => $request->notes,
            'status' => 'submitted',
        ]);

        return redirect()->route('learner.assignments')->with('success', 'Assignment submitted successfully!');
    }
}
