<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Assignment;
use App\Models\LearningMaterial;
use App\Models\LearningGoal;
use App\Services\ClassScheduleService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $learner = auth()->user()->learner;
        
        $classIds = $learner->classes->pluck('id');

        // The dashboard shows the current week's timetable. "View all" opens
        // the full session list on My Classes.
        $weekStart = ClassScheduleService::weekStart();
        $weekEndExclusive = $weekStart->copy()->addDays(7);

        $weekSessions = ClassSchedule::whereIn('class_id', $classIds)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '>=', $weekStart)
            ->where('start_time', '<', $weekEndExclusive)
            ->with('class', 'classSubject.subject', 'tutor.user')
            ->orderBy('start_time')
            ->get();

        $data = [
            'myClasses' => $learner->classes()->with(['tutor.user', 'subjectAssignments.subject', 'subjectAssignments.tutor.user'])->get(),
            // "The ongoing or nearest class should be on the panel": a session
            // that is live right now wins, otherwise the closest future one.
            'liveSession' => ClassSchedule::whereIn('class_id', $classIds)
                ->where('status', 'scheduled')
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->with('class', 'classSubject.subject', 'tutor.user')
                ->orderBy('start_time')
                ->first(),
            'nextSession' => ClassSchedule::whereIn('class_id', $classIds)
                ->where('status', 'scheduled')
                ->where('start_time', '>', now())
                ->where('start_time', '<', $weekEndExclusive)
                ->with('class', 'classSubject.subject', 'tutor.user')
                ->orderBy('start_time')
                ->first(),
            // Only this week, so the panel is a timetable rather than a growing
            // list of every future occurrence.
            'weekSessions' => $weekSessions,
            'weekSessionCount' => $weekSessions->count(),
            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->copy()->addDays(6),
            'pendingAssignments' => Assignment::whereIn('class_id', $classIds)
                ->where('status', 'active')
                ->where('due_date', '>', now())
                ->count(),
            'materials' => LearningMaterial::whereIn('class_id', $classIds)
                ->latest()
                ->take(5)
                ->get(),
            // Note 7 — goals the school shared with this learner.
            'sharedGoals' => LearningGoal::visibleToLearner($learner)
                ->with('schoolClass')
                ->latest('shared_at')
                ->take(3)
                ->get(),
            'sharedGoalsCount' => LearningGoal::visibleToLearner($learner)->count(),
        ];


        return view('learner.dashboard', $data);
    }

    public function joinClass(ClassSchedule $schedule)
    {
        $learner = auth()->user()->learner;

        // Visibility in the dashboard is already scoped to the learner's classes,
        // but this URL is guessable by id, so re-check enrolment here.
        $isEnrolled = $learner !== null
            && $learner->classes()->where('classes.id', $schedule->class_id)->exists();

        abort_unless($isEnrolled, 403, 'You are not enrolled in this class.');

        if ($schedule->status === 'cancelled') {
            return redirect()->route('learner.dashboard')->with('error', 'This session has been cancelled.');
        }

        if ($schedule->status === 'completed') {
            return redirect()->route('learner.dashboard')->with('error', 'This session has already ended.');
        }

        if (! $schedule->meeting_link) {
            return redirect()->route('learner.dashboard')->with('error', 'No meeting link is available for this session.');
        }

        return redirect()->away($schedule->meeting_link);
    }
}
