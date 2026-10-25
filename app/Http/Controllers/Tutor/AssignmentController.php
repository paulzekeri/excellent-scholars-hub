<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classes;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;
        $assignments = Assignment::where('tutor_id', $tutor->id)
            ->with('class')
            ->latest()
            ->paginate(10);
        return view('tutor.assignments.index', compact('assignments'));
    }

    public function create()
    {
        $tutor = auth()->user()->tutor;
        $classes = Classes::where('tutor_id', $tutor->id)->where('is_active', true)->get();
        return view('tutor.assignments.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $tutor = auth()->user()->tutor;
        
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'total_points' => 'integer|min:1',
        ]);

        Assignment::create(array_merge($request->all(), ['tutor_id' => $tutor->id]));

        return redirect()->route('tutor.assignments.index')->with('success', 'Assignment created successfully.');
    }

    public function submissions(Assignment $assignment)
    {
        $this->ensureOwnedAssignment($assignment);

        $submissions = Submission::where('assignment_id', $assignment->id)
            ->with('learner.user')
            ->get();
        return view('tutor.assignments.submissions', compact('assignment', 'submissions'));
    }

    public function downloadFile(Submission $submission)
    {
        $this->ensureOwnedAssignment($submission->assignment);

        if (!$submission->file_path || !Storage::disk('public')->exists($submission->file_path)) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($submission->file_path);
    }

    public function grade(Request $request, Submission $submission)
    {
        $assignment = $submission->assignment;
        $this->ensureOwnedAssignment($assignment);

        $request->validate([
            'score' => 'required|integer|min:0|max:' . $assignment->total_points,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'score' => $request->score,
            'feedback' => $request->feedback,
            'status' => 'graded',
            'graded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Submission graded successfully.');
    }

    /**
     * Tutors may only review and grade assignments for their own classes.
     */
    private function ensureOwnedAssignment(Assignment $assignment)
    {
        $tutor = auth()->user()->tutor;

        abort_unless($tutor && (int) $assignment->tutor_id === (int) $tutor->id, 403, 'You are not allowed to access this assignment.');
    }
}
