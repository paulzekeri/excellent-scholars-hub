<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassSchedule;
use App\Models\Classes;
use App\Services\ClassScheduleService;
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

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|in:google_meet,zoom,jitsi',
            'description' => 'nullable|string',
            // A tutor can set the Monday lesson once and repeat it every week.
            'repeat' => 'nullable|in:none,weekly',
            'repeat_until' => 'nullable|required_if:repeat,weekly|date|after:start_time',
        ]);

        $validated['tutor_id'] = $tutor->id;

        $sessions = app(ClassScheduleService::class)->createSeries($validated);
        $first = $sessions->first();

        $message = $sessions->count() > 1
            ? $sessions->count() . ' sessions created — every ' . $first->start_time->format('l')
                . ' at ' . $first->start_time->format('g:i a') . ' from ' . $first->start_time->format('M d, Y') . '.'
            : 'Schedule created successfully.';

        return redirect()->route('tutor.schedules.index')->with('success', $message);
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
