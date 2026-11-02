<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassSchedule;
use App\Models\Classes;
use App\Services\ClassScheduleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

        return view('tutor.schedules.create', [
            'classes' => $classes,
            'weekdays' => ClassScheduleService::weekdays(),
        ]);
    }

    public function store(Request $request)
    {
        $tutor = auth()->user()->tutor;
        $service = app(ClassScheduleService::class);

        $repeat = $request->input('repeat') === ClassScheduleService::REPEAT_WEEKLY
            ? ClassScheduleService::REPEAT_WEEKLY
            : ClassScheduleService::REPEAT_NONE;

        $validated = $request->validate(array_merge([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|in:google_meet,zoom,jitsi',
            'description' => 'nullable|string',
            // A tutor can set the Monday lesson once and repeat it every week,
            // or run the same class on several days at different times.
            'repeat' => 'nullable|in:none,weekly',
        ], ClassScheduleService::repeatRules($repeat)));

        if ($errors = $service->seriesErrors($validated)) {
            throw ValidationException::withMessages($errors);
        }

        $validated['tutor_id'] = $tutor->id;

        $sessions = $service->createSeries($validated);

        $message = $sessions->count() > 1
            ? $sessions->count() . ' sessions created — every ' . ClassScheduleService::slotSummary($sessions)
                . ' from ' . $sessions->first()->start_time->format('M d, Y') . '.'
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
