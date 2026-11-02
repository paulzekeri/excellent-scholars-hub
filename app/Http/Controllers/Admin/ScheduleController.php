<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\ClassSchedule;
use App\Models\Classes;
use App\Models\LearningMaterial;
use App\Models\Tutor;
use App\Services\ClassScheduleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        // Filters: class, tutor, a single day of start times and status.
        $classFilter = is_numeric($request->query('class_id')) ? (int) $request->query('class_id') : null;
        $tutorFilter = is_numeric($request->query('tutor_id')) ? (int) $request->query('tutor_id') : null;
        $statusFilter = in_array($request->query('status'), ['scheduled', 'ongoing', 'completed', 'cancelled'], true)
            ? $request->query('status')
            : null;
        $dateFilter = $this->filterDate($request->query('date'));

        $schedules = ClassSchedule::with('class', 'tutor.user')
            ->when($classFilter, function ($query) use ($classFilter) {
                $query->where('class_id', $classFilter);
            })
            ->when($tutorFilter, function ($query) use ($tutorFilter) {
                $query->where('tutor_id', $tutorFilter);
            })
            ->when($dateFilter, function ($query) use ($dateFilter) {
                $query->whereDate('start_time', $dateFilter);
            })
            ->when($statusFilter, function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            // Upcoming and ongoing sessions first (soonest at the top), then past
            // sessions most-recent-first, so the live session is always on page one.
            ->orderByRaw('CASE WHEN start_time >= ? THEN 0 ELSE 1 END', [now()])
            ->orderByRaw('CASE WHEN start_time >= ? THEN start_time END ASC', [now()])
            ->orderByRaw('CASE WHEN start_time < ? THEN start_time END DESC', [now()])
            ->paginate(10)
            ->withQueryString();

        return view('admin.schedules.index', [
            'schedules' => $schedules,
            'classes' => Classes::orderBy('name')->get(),
            'tutors' => Tutor::with('user')->get(),
            'classFilter' => $classFilter,
            'tutorFilter' => $tutorFilter,
            'statusFilter' => $statusFilter,
            'dateFilter' => $dateFilter,
            'totalSessions' => ClassSchedule::count(),
        ]);
    }

    /** A valid Y-m-d date from the filter box, or null when blank/invalid. */
    private function filterDate($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Read-only session detail for administrators and the CEO: shows who is
     * enrolled, whether attendance was marked, and offers a single Join action.
     */
    public function show(ClassSchedule $schedule)
    {
        $schedule->load(['class.tutor.user', 'class.learners.user', 'tutor.user', 'attendance.learner.user']);

        $class = $schedule->class;
        $enrolledLearners = $class ? $class->learners : collect();
        $attendanceByLearner = $schedule->attendance->keyBy('learner_id');

        // Enrolled learners first, then any attendance record belonging to a
        // learner who has since been unenrolled, so the session log stays complete.
        $attendanceRows = $enrolledLearners->map(function ($learner) use ($attendanceByLearner) {
            return [
                'learner' => $learner,
                'record' => $attendanceByLearner->get($learner->id),
                'enrolled' => true,
            ];
        })->concat(
            $schedule->attendance
                ->reject(function ($record) use ($enrolledLearners) {
                    return $enrolledLearners->contains('id', $record->learner_id);
                })
                ->filter(function ($record) {
                    return $record->learner !== null;
                })
                ->map(function ($record) {
                    return [
                        'learner' => $record->learner,
                        'record' => $record,
                        'enrolled' => false,
                    ];
                })
        )->sortBy(function ($row) {
            return $row['learner']->user->name ?? '';
        })->values();

        return view('admin.schedules.show', [
            'schedule' => $schedule,
            'class' => $class,
            'attendanceRows' => $attendanceRows,
            'attendanceSummary' => [
                'present' => $schedule->attendance->where('status', 'present')->count(),
                'late' => $schedule->attendance->where('status', 'late')->count(),
                'absent' => $schedule->attendance->where('status', 'absent')->count(),
                'excused' => $schedule->attendance->where('status', 'excused')->count(),
            ],
            'assignmentCount' => $class ? Assignment::where('class_id', $class->id)->count() : 0,
            'materialCount' => $class ? LearningMaterial::where('class_id', $class->id)->count() : 0,
        ]);
    }

    public function create()
    {
        $classes = Classes::with('tutor.user')->where('is_active', true)->get();
        $tutors = Tutor::with('user')->where('is_active', true)->get();

        return view('admin.schedules.create', [
            'classes' => $classes,
            'tutors' => $tutors,
            'weekdays' => ClassScheduleService::weekdays(),
        ]);
    }

    public function store(Request $request)
    {
        $service = app(ClassScheduleService::class);

        $repeat = $request->input('repeat') === ClassScheduleService::REPEAT_WEEKLY
            ? ClassScheduleService::REPEAT_WEEKLY
            : ClassScheduleService::REPEAT_NONE;

        $validated = $request->validate(array_merge([
            'class_id' => 'required|exists:classes,id',
            'tutor_id' => 'required|exists:tutors,id',
            'title' => 'required|string|max:255',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|in:google_meet,zoom,jitsi',
            'description' => 'nullable|string',
            // "Set for Monday with repeat" — one form, a whole weekly series,
            // optionally on several days at different times.
            'repeat' => 'nullable|in:none,weekly',
        ], ClassScheduleService::repeatRules($repeat)));

        if ($errors = $service->seriesErrors($validated)) {
            throw ValidationException::withMessages($errors);
        }

        $sessions = $service->createSeries($validated);

        $message = $sessions->count() > 1
            ? $sessions->count() . ' sessions created — every ' . ClassScheduleService::slotSummary($sessions)
                . ' from ' . $sessions->first()->start_time->format('M d, Y') . '.'
            : 'Schedule created successfully.';

        return redirect()->route('admin.schedules.index')->with('success', $message);
    }

    public function edit(ClassSchedule $schedule)
    {
        $classes = Classes::where('is_active', true)->get();
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        return view('admin.schedules.edit', compact('schedule', 'classes', 'tutors'));
    }

    public function update(Request $request, ClassSchedule $schedule)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'tutor_id' => 'required|exists:tutors,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $schedule->update($request->all());

        return redirect()->route('admin.schedules.index')->with('success', 'Schedule updated successfully.');
    }

    /**
     * Note 5 — the school confirms a class was actually taken, so it counts on
     * the monthly tutor report (and therefore towards paying the tutor).
     */
    public function markCompleted(ClassSchedule $schedule)
    {
        if ($schedule->isCompleted()) {
            return back()->with('error', 'This class session is already marked as completed.');
        }

        if (! $schedule->hasEnded()) {
            return back()->with('error', 'This class session has not ended yet. You can only tick it after the scheduled end time.');
        }

        $schedule->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'schedule.completed',
            'description' => 'Marked "' . $schedule->title . '" as completed.',
            'model_type' => ClassSchedule::class,
            'model_id' => $schedule->id,
        ]);

        return back()->with('success', 'Class marked as completed. It now counts on the tutor report.');
    }

    /** Undo a tick — the session stops counting towards the tutor report. */
    public function reopen(ClassSchedule $schedule)
    {
        if (! $schedule->isCompleted()) {
            return back()->with('error', 'Only a completed session can be reopened.');
        }

        $schedule->update([
            'status' => 'scheduled',
            'completed_at' => null,
            'completed_by' => null,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'schedule.reopened',
            'description' => 'Reopened "' . $schedule->title . '" so it no longer counts as completed.',
            'model_type' => ClassSchedule::class,
            'model_id' => $schedule->id,
        ]);

        return back()->with('success', 'Session reopened — it no longer counts on the tutor report.');
    }

    public function destroy(ClassSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule deleted successfully.');
    }
}
