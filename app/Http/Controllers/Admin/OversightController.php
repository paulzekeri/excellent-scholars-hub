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
}
