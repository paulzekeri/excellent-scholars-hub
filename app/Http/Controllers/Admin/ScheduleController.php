<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\ClassSchedule;
use App\Models\Classes;
use App\Models\ClassSubject;
use App\Models\LearningMaterial;
use App\Models\Tutor;
use App\Services\ClassScheduleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        // Filters: class, subject, tutor, a single day of start times and status.
        $classFilter = is_numeric($request->query('class_id')) ? (int) $request->query('class_id') : null;
        $subjectFilter = is_numeric($request->query('class_subject_id')) ? (int) $request->query('class_subject_id') : null;
        $tutorFilter = is_numeric($request->query('tutor_id')) ? (int) $request->query('tutor_id') : null;
        $statusFilter = in_array($request->query('status'), ['scheduled', 'ongoing', 'completed', 'cancelled'], true)
            ? $request->query('status')
            : null;
        $dateFilter = $this->filterDate($request->query('date'));

        // A weekly series can run for years, which used to fill this screen with
        // hundreds of rows and dozens of pages. The list now opens on one week
        // ("week"). Picking a day narrows it to that day ("day"), and the full
        // paginated history stays one click away under "All schedules" ("all").
        $scope = $dateFilter ? 'day' : ($request->query('view') === 'all' ? 'all' : 'week');

        $weekStart = $scope === 'week' ? ClassScheduleService::weekStart($request->query('week')) : null;
        $weekEnd = $weekStart ? $weekStart->copy()->addDays(6) : null;

        $query = ClassSchedule::with('class', 'classSubject.subject', 'classSubject.tutor.user', 'tutor.user')
            ->when($classFilter, function ($query) use ($classFilter) {
                $query->where('class_id', $classFilter);
            })
            ->when($subjectFilter, function ($query) use ($subjectFilter) {
                $query->where('class_subject_id', $subjectFilter);
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
            ->when($weekStart, function ($query) use ($weekStart) {
                $query->whereBetween('start_time', [
                    $weekStart->copy()->startOfDay(),
                    $weekStart->copy()->addDays(6)->endOfDay(),
                ]);
            });

        if ($scope === 'all') {
            // Upcoming and ongoing sessions first (soonest at the top), then past
            // sessions most-recent-first, so the live session is always on page one.
            $schedules = $query
                ->orderByRaw('CASE WHEN start_time >= ? THEN 0 ELSE 1 END', [now()])
                ->orderByRaw('CASE WHEN start_time >= ? THEN start_time END ASC', [now()])
                ->orderByRaw('CASE WHEN start_time < ? THEN start_time END DESC', [now()])
                ->paginate(25)
                ->withQueryString();
        } else {
            // A week or a single day fits on one screen, so it is shown in full
            // and grouped by day (no pagination).
            $schedules = $query->orderBy('start_time')->get();
        }

        $activeFilters = array_filter([
            'class_id' => $classFilter,
            'class_subject_id' => $subjectFilter,
            'tutor_id' => $tutorFilter,
            'status' => $statusFilter,
        ]);

        return view('admin.schedules.index', [
            'schedules' => $schedules,
            'scope' => $scope,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'filterParams' => $activeFilters,
            'isFiltered' => $activeFilters !== [],
            'matchedSessions' => $scope === 'all' ? $schedules->total() : $schedules->count(),
            'classes' => Classes::orderBy('name')->get(),
            'classSubjects' => $this->classSubjectOptions(),
            'tutors' => Tutor::with('user')->get(),
            'classFilter' => $classFilter,
            'subjectFilter' => $subjectFilter,
            'tutorFilter' => $tutorFilter,
            'statusFilter' => $statusFilter,
            'dateFilter' => $dateFilter,
            'totalSessions' => ClassSchedule::count(),
        ]);
    }

    /**
     * Every "class → subject → teacher" choice, sorted by class then subject.
     * Used by the schedule form and the subject filter.
     *
     * @return \Illuminate\Support\Collection<int, ClassSubject>
     */
    private function classSubjectOptions()
    {
        return ClassSubject::with(['class', 'subject', 'tutor.user'])
            ->get()
            ->sortBy(function (ClassSubject $row) {
                return mb_strtolower(($row->class->name ?? '') . '|' . ($row->subject->name ?? ''));
            })
            ->values();
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
        $schedule->load([
            'class.tutor.user',
            'class.learners.user',
            'classSubject.subject',
            'classSubject.tutor.user',
            'tutor.user',
            'attendance.learner.user',
        ]);

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
        $allSubjects = $this->classSubjectOptions();

        // Only subjects with a teacher can be scheduled: the session's tutor is
        // read from that teacher, so there is nobody to link it to otherwise.
        $classSubjects = $allSubjects
            ->filter(function (ClassSubject $row) {
                return $row->tutor_id && $row->class && $row->class->is_active;
            })
            ->values();

        $subjectsMissingTeacher = $allSubjects
            ->filter(function (ClassSubject $row) {
                return ! $row->tutor_id && $row->class && $row->class->is_active;
            })
            ->values();

        return view('admin.schedules.create', [
            'classSubjects' => $classSubjects,
            'subjectsMissingTeacher' => $subjectsMissingTeacher,
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
            // New flow: one session = one subject of one class. The class and
            // the tutor are read from that subject row, not trusted from the form.
            'class_subject_id' => 'nullable|exists:class_subject,id',
            // Legacy flow (old bookmarks/API): no subject posted, so the class
            // and tutor are still required exactly as before.
            'class_id' => 'required_without:class_subject_id|exists:classes,id',
            'tutor_id' => 'required_without:class_subject_id|exists:tutors,id',
            'title' => 'required|string|max:255',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|in:google_meet,zoom,jitsi',
            'description' => 'nullable|string',
            // "Set for Monday with repeat" — one form, a whole weekly series,
            // optionally on several days at different times.
            'repeat' => 'nullable|in:none,weekly',
        ], ClassScheduleService::repeatRules($repeat)));

        $this->applySubjectSelection($validated);

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

    /**
     * When a subject row is chosen, its class and teacher win. This is what makes
     * "only the tutor for that subject is linked to the lesson" true even if a
     * hand-crafted request tries to post a different tutor.
     *
     * @param  array<string, mixed>  $validated
     */
    private function applySubjectSelection(array &$validated): void
    {
        if (empty($validated['class_subject_id'])) {
            // Legacy session: keep class_id/tutor_id exactly as posted. When the
            // edit form explicitly chose "no subject", clear any old link.
            if (array_key_exists('class_subject_id', $validated)) {
                $validated['class_subject_id'] = null;
            }

            return;
        }

        $classSubject = ClassSubject::with('subject')->findOrFail($validated['class_subject_id']);

        if (! $classSubject->tutor_id) {
            throw ValidationException::withMessages([
                'class_subject_id' => 'Assign a teacher to '
                    . (optional($classSubject->subject)->name ?? 'this subject')
                    . ' on the class page before scheduling it.',
            ]);
        }

        $validated['class_id'] = $classSubject->class_id;
        $validated['tutor_id'] = $classSubject->tutor_id;
    }

    public function edit(ClassSchedule $schedule)
    {
        return view('admin.schedules.edit', [
            'schedule' => $schedule,
            'classSubjects' => $this->classSubjectOptions(),
        ]);
    }

    public function update(Request $request, ClassSchedule $schedule)
    {
        $validated = $request->validate([
            'class_subject_id' => 'nullable|exists:class_subject,id',
            'class_id' => 'required_without:class_subject_id|exists:classes,id',
            'tutor_id' => 'required_without:class_subject_id|exists:tutors,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $this->applySubjectSelection($validated);

        $schedule->update($validated);

        // Land back on the week the session belongs to, so a past session that
        // was edited from the week view does not drop the admin into this week.
        return redirect()
            ->route('admin.schedules.index', ['week' => $schedule->start_time->toDateString()])
            ->with('success', 'Schedule updated successfully.');
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
        $week = $schedule->start_time->toDateString();
        $schedule->delete();

        return redirect()
            ->route('admin.schedules.index', ['week' => $week])
            ->with('success', 'Schedule deleted successfully.');
    }
}
