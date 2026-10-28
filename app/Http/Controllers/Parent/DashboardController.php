<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\LearningMaterial;
use App\Models\LearningGoal;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Sections available on the child details screen.
     */
    private const TABS = [
        'overview' => 'Overview',
        'schedule' => 'Class Schedule',
        'pending' => 'Pending Assignments',
        'completed' => 'Completed Assignments',
        'materials' => 'Materials Shared',
        'attendance' => 'Attendance',
        'goals' => 'Learning Goals',
    ];

    public function index()
    {
        $parent = auth()->user()->parent;

        $children = $parent
            ? Learner::where('parent_id', $parent->id)
                ->with(['user', 'classes:id,name,code'])
                ->orderBy('id')
                ->get()
            : collect();

        $childStats = $children->mapWithKeys(function (Learner $child) {
            return [$child->id => $this->statsFor($child)];
        });

        return view('parent.dashboard', [
            'children' => $children,
            'childStats' => $childStats,
            'totalChildren' => $children->count(),
            'totalMissedClasses' => $childStats->sum('missedClasses'),
        ]);
    }

    public function childProgress(Request $request, Learner $learner)
    {
        $this->authorizeChild($learner);

        $tab = $request->query('tab', 'overview');
        if (! is_string($tab) || ! array_key_exists($tab, self::TABS)) {
            $tab = 'overview';
        }

        $classIds = $learner->classes()->pluck('classes.id');
        $stats = $this->statsFor($learner, $classIds);

        $data = [
            'learner' => $learner->load(['user', 'classes.tutor.user']),
            'tabs' => self::TABS,
            'tab' => $tab,
            'stats' => $stats,
            'missedClasses' => $this->missedClasses($learner),
            'unrecordedSessions' => ClassSchedule::whereIn('class_id', $classIds)
                ->where('end_time', '<', now())
                ->where('status', '!=', 'cancelled')
                ->whereDoesntHave('attendance', function ($query) use ($learner) {
                    $query->where('learner_id', $learner->id);
                })
                ->with('class')
                ->orderByDesc('start_time')
                ->get(),
        ];

        switch ($tab) {
            case 'schedule':
                $data['upcomingClasses'] = $this->upcomingSessions($classIds)
                    ->paginate(15)
                    ->withQueryString();
                break;

            case 'pending':
                $data['pendingAssignments'] = $this->pendingAssignments($learner, $classIds)
                    ->paginate(10)
                    ->withQueryString();
                break;

            case 'completed':
                $data['completedSubmissions'] = $this->completedSubmissions($learner, $classIds)
                    ->paginate(10)
                    ->withQueryString();
                break;

            case 'materials':
                $data['materials'] = LearningMaterial::whereIn('class_id', $classIds)
                    ->where('status', 'published')
                    ->with(['class', 'tutor.user'])
                    ->latest()
                    ->paginate(10)
                    ->withQueryString();
                break;

            case 'attendance':
                $data['attendance'] = $this->attendanceHistory($learner)
                    ->paginate(20)
                    ->withQueryString();
                break;

            case 'goals':
                $data['goals'] = $this->sharedGoals($learner, $classIds)
                    ->paginate(10)
                    ->withQueryString();
                break;

            default:
                $data['upcomingClasses'] = $this->upcomingSessions($classIds)->take(5)->get();
                $data['pendingAssignments'] = $this->pendingAssignments($learner, $classIds)->take(5)->get();
                $data['recentAttendance'] = $this->attendanceHistory($learner)->take(5)->get();
                break;
        }

        return view('parent.child-progress', $data);
    }

    /**
     * A parent may only open children linked to their own account.
     */
    private function authorizeChild(Learner $learner)
    {
        $parent = auth()->user()->parent;

        abort_unless($parent && (int) $learner->parent_id === (int) $parent->id, 403);
    }

    /**
     * Headline numbers shown on the dashboard cards and child tabs.
     */
    private function statsFor(Learner $learner, ?Collection $classIds = null): array
    {
        $classIds = $classIds ?? $learner->classes()->pluck('classes.id');

        $attendanceCounts = Attendance::where('learner_id', $learner->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recorded = (int) $attendanceCounts->sum();
        $attended = (int) ($attendanceCounts['present'] ?? 0) + (int) ($attendanceCounts['late'] ?? 0);

        return [
            'upcomingClasses' => $this->upcomingSessions($classIds)->count(),
            'pendingAssignments' => $this->pendingAssignments($learner, $classIds)->count(),
            'completedAssignments' => $this->completedSubmissions($learner, $classIds)->count(),
            'materials' => LearningMaterial::whereIn('class_id', $classIds)
                ->where('status', 'published')
                ->count(),
            'missedClasses' => (int) ($attendanceCounts['absent'] ?? 0),
            'attendanceRecorded' => $recorded,
            'attendanceAttended' => $attended,
            'attendanceRate' => $recorded > 0 ? (int) round($attended / $recorded * 100) : null,
            'enrolledClasses' => $classIds->count(),
            'goals' => $this->sharedGoals($learner, $classIds)->count(),
            // Verified vs pending fees for the fee summary line on the dashboard.
            'fees' => Payment::totalsForLearner($learner->id),
        ];
    }

    /**
     * Goals the school chose to share with the parent: those written for this
     * learner plus class goals for any class the learner belongs to.
     */
    private function sharedGoals(Learner $learner, ?Collection $classIds = null)
    {
        $classIds = $classIds ?? $learner->classes()->pluck('classes.id');

        return LearningGoal::with(['tutor.user', 'schoolClass'])
            ->where('shared_with_parent', true)
            ->where(function ($query) use ($learner, $classIds) {
                $query->where('learner_id', $learner->id)
                    ->orWhere(function ($inner) use ($classIds) {
                        $inner->whereNull('learner_id')->whereIn('class_id', $classIds);
                    });
            })
            ->latest('shared_at')
            ->latest();
    }

    /**
     * Scheduled sessions that have not started yet.
     */
    private function upcomingSessions(Collection $classIds)
    {
        return ClassSchedule::whereIn('class_id', $classIds)
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->with(['class.tutor.user'])
            ->orderBy('start_time');
    }

    /**
     * Assignments for the child's classes that they have not submitted.
     */
    private function pendingAssignments(Learner $learner, Collection $classIds)
    {
        return Assignment::whereIn('class_id', $classIds)
            ->whereDoesntHave('submissions', function ($query) use ($learner) {
                $query->where('learner_id', $learner->id);
            })
            ->with(['class', 'tutor.user'])
            ->orderBy('due_date');
    }

    /**
     * Submissions the child has made for assignments in their classes.
     */
    private function completedSubmissions(Learner $learner, Collection $classIds)
    {
        return Submission::where('learner_id', $learner->id)
            ->whereHas('assignment', function ($query) use ($classIds) {
                $query->whereIn('class_id', $classIds);
            })
            ->with(['assignment.class', 'assignment.tutor.user'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');
    }

    /**
     * Attendance records for the child, newest session first.
     */
    private function attendanceHistory(Learner $learner)
    {
        return Attendance::where('learner_id', $learner->id)
            ->join('class_schedules', 'attendance.class_schedule_id', '=', 'class_schedules.id')
            ->select('attendance.*', 'class_schedules.start_time')
            ->with('classSchedule.class')
            ->orderByDesc('class_schedules.start_time');
    }

    /**
     * Sessions the child was explicitly marked absent for.
     */
    private function missedClasses(Learner $learner)
    {
        return $this->attendanceHistory($learner)
            ->where('attendance.status', 'absent')
            ->get();
    }
}
