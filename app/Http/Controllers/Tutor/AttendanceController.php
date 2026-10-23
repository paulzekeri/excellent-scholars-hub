<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Attendance;
use App\Models\Learner;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(ClassSchedule $schedule)
    {
        $learners = $schedule->class->learners()->with('user')->get();
        $attendance = Attendance::where('class_schedule_id', $schedule->id)->get()->keyBy('learner_id');
        
        return view('tutor.attendance.index', compact('schedule', 'learners', 'attendance'));
    }

    public function store(Request $request, ClassSchedule $schedule)
    {
        $request->validate([
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:present,absent,late,excused',
        ]);

        foreach ($request->attendance as $learnerId => $data) {
            Attendance::updateOrCreate(
                [
                    'class_schedule_id' => $schedule->id,
                    'learner_id' => $learnerId,
                ],
                [
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'marked_at' => now(),
                ]
            );
        }

        return redirect()->route('tutor.schedules.index')->with('success', 'Attendance recorded successfully.');
    }
}
