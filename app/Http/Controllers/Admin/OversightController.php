<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Assignment;
use App\Models\LearningMaterial;
use App\Models\Classes;
use App\Models\Submission;
use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OversightController extends Controller
{
    /**
     * View all attendance records across all classes.
     */
    public function attendance()
    {
        $classes = Classes::with('tutor.user')->get();

        $attendance = Attendance::with(['classSchedule.class', 'learner.user'])
            ->join('class_schedules', 'attendance.class_schedule_id', '=', 'class_schedules.id')
            ->select('attendance.*', 'class_schedules.class_id', 'class_schedules.start_time')
            ->orderBy('class_schedules.start_time', 'desc')
            ->paginate(50);

        return view('admin.oversight.attendance', compact('attendance', 'classes'));
    }

    /**
     * View all assignments and submissions across all classes.
     */
    public function assignments()
    {
        $classes = Classes::all();

        $assignments = Assignment::with(['class', 'tutor.user', 'submissions.learner.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.oversight.assignments', compact('assignments', 'classes'));
    }

    /**
     * View all learning materials across all classes.
     */
    public function materials()
    {
        $classes = Classes::all();

        $materials = LearningMaterial::with(['class', 'tutor.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.oversight.materials', compact('materials', 'classes'));
    }

    /**
     * Filter attendance by class.
     */
    public function attendanceByClass($classId)
    {
        $classes = Classes::with('tutor.user')->get();
        $selectedClass = Classes::findOrFail($classId);

        $attendance = Attendance::with(['classSchedule.class', 'learner.user'])
            ->join('class_schedules', 'attendance.class_schedule_id', '=', 'class_schedules.id')
            ->where('class_schedules.class_id', $classId)
            ->select('attendance.*', 'class_schedules.class_id', 'class_schedules.start_time')
            ->orderBy('class_schedules.start_time', 'desc')
            ->paginate(50);

        return view('admin.oversight.attendance', compact('attendance', 'classes', 'selectedClass'));
    }

    /**
     * Filter assignments by class.
     */
    public function assignmentsByClass($classId)
    {
        $classes = Classes::all();
        $selectedClass = Classes::findOrFail($classId);

        $assignments = Assignment::with(['class', 'tutor.user', 'submissions.learner.user'])
            ->where('class_id', $classId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.oversight.assignments', compact('assignments', 'classes', 'selectedClass'));
    }

    /**
     * Filter materials by class.
     */
    public function materialsByClass($classId)
    {
        $classes = Classes::all();
        $selectedClass = Classes::findOrFail($classId);

        $materials = LearningMaterial::with(['class', 'tutor.user'])
            ->where('class_id', $classId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.oversight.materials', compact('materials', 'classes', 'selectedClass'));
    }

    /**
     * Review every submission for a single assignment (admins and the CEO).
     */
    public function submissions(Assignment $assignment)
    {
        $assignment->load(['class', 'tutor.user']);

        $submissions = Submission::where('assignment_id', $assignment->id)
            ->with('learner.user')
            ->get();

        return view('admin.oversight.submissions', compact('assignment', 'submissions'));
    }

    /**
     * Grade a learner submission on behalf of the class tutor.
     */
    public function gradeSubmission(Request $request, Submission $submission)
    {
        $assignment = $submission->assignment;

        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:' . $assignment->total_points,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
            'graded_at' => now(),
        ]);

        return redirect()->route('admin.oversight.assignments.submissions', $assignment)
            ->with('success', 'Submission graded successfully.');
    }

    /**
     * Download a submitted assignment file.
     */
    public function downloadSubmission(Submission $submission)
    {
        if (!$submission->file_path || !Storage::disk('public')->exists($submission->file_path)) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($submission->file_path);
    }
}
