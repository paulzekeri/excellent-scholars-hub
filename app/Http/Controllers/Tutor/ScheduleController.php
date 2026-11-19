<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassSchedule;
use App\Models\Classes;
use App\Models\ClassSubject;
use App\Services\ClassScheduleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $tutor = auth()->user()->tutor;

        // The same problem as the admin list: a tutor teaching one class for
        // years would page through hundreds of sessions. Open on the current
        // week; "All schedules" keeps the full history for reference.
        $scope = $request->query('view') === 'all' ? 'all' : 'week';
        $weekStart = $scope === 'week' ? ClassScheduleService::weekStart($request->query('week')) : null;
        $weekEnd = $weekStart ? $weekStart->copy()->addDays(6) : null;

        $query = ClassSchedule::where('tutor_id', $tutor->id)
            ->with('class', 'classSubject.subject', 'classSubject.tutor.user')
            ->when($weekStart, function ($query) use ($weekStart) {
                $query->whereBetween('start_time', [
                    $weekStart->copy()->startOfDay(),
                    $weekStart->copy()->addDays(6)->endOfDay(),
                ]);
            });

        if ($scope === 'all') {
            $schedules = $query->orderBy('start_time')->paginate(25)->withQueryString();
        } else {
            $schedules = $query->orderBy('start_time')->get();
        }

        return view('tutor.schedules.index', [
            'schedules' => $schedules,
            'scope' => $scope,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'matchedSessions' => $scope === 'all' ? $schedules->total() : $schedules->count(),
        ]);
    }

    public function create()
    {
        $tutor = auth()->user()->tutor;

        // A tutor schedules only the class subjects they themselves teach. The
        // session's teacher is read from that row, so another subject's lessons
        // never end up under the wrong tutor.
        $classSubjects = ClassSubject::with(['class', 'subject'])
            ->where('tutor_id', $tutor->id)
            ->whereHas('class', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->sortBy(function (ClassSubject $row) {
                return mb_strtolower(($row->class->name ?? '') . '|' . ($row->subject->name ?? ''));
            })
            ->values();

        return view('tutor.schedules.create', [
            'classSubjects' => $classSubjects,
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
            // New flow: choose one of the tutor's own class subjects.
            'class_subject_id' => [
                'nullable',
                Rule::exists('class_subject', 'id')->where('tutor_id', $tutor->id),
            ],
            // Legacy flow (old bookmarks/API): class only, as before.
            'class_id' => [
                'required_without:class_subject_id',
                Rule::exists('classes', 'id')->whereIn('id', Classes::idsTaughtBy($tutor->id)),
            ],
            'title' => 'required|string|max:255',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|in:google_meet,zoom,jitsi',
            'description' => 'nullable|string',
            // A tutor can set the Monday lesson once and repeat it every week,
            // or run the same class on several days at different times.
            'repeat' => 'nullable|in:none,weekly',
        ], ClassScheduleService::repeatRules($repeat)));

        if (! empty($validated['class_subject_id'])) {
            $classSubject = ClassSubject::findOrFail($validated['class_subject_id']);

            $validated['class_id'] = $classSubject->class_id;
        } else {
            if (array_key_exists('class_subject_id', $validated)) {
                $validated['class_subject_id'] = null;
            }
        }

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
