<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassSchedule;
use App\Models\Classes;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;
        $schedules = ClassSchedule::where('tutor_id', $tutor->id)
            ->with('class')
            ->orderBy('start_time')
            ->paginate(10);
        return view('tutor.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $tutor = auth()->user()->tutor;
        $classes = Classes::where('tutor_id', $tutor->id)->where('is_active', true)->get();
        return view('tutor.schedules.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $tutor = auth()->user()->tutor;
        
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        ClassSchedule::create(array_merge($request->all(), ['tutor_id' => $tutor->id]));

        return redirect()->route('tutor.schedules.index')->with('success', 'Schedule created successfully.');
    }

    public function markCompleted(ClassSchedule $schedule)
    {
        if ($schedule->isCompleted()) {
            return redirect()->back()->with('error', 'This class session is already marked as completed.');
        }

        // Ensure the schedule's end time has already passed
        if (! $schedule->hasEnded()) {
            return redirect()->back()->with('error', 'This class session has not ended yet. You can only mark it as completed after the scheduled end time.');
        }

        $schedule->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'schedule.completed',
            'description' => 'Tutor marked "' . $schedule->title . '" as completed.',
            'model_type' => ClassSchedule::class,
            'model_id' => $schedule->id,
        ]);

        return redirect()->back()->with('success', 'Class marked as completed.');
    }
}
