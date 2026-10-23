<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\ClassSchedule;
use App\Models\Attendance;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $parent = auth()->user()->parent;
        $children = Learner::where('parent_id', $parent->id)->with('user', 'classes')->get();
        
        $data = [
            'children' => $children,
            'totalChildren' => $children->count(),
        ];

        return view('parent.dashboard', $data);
    }

    public function childProgress(Learner $learner)
    {
        $classIds = $learner->classes->pluck('id');

        $attendance = Attendance::where('learner_id', $learner->id)
            ->with('classSchedule.class')
            ->latest()
            ->take(20)
            ->get();

        $upcomingClasses = ClassSchedule::whereIn('class_id', $classIds)
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->take(5)
            ->get();

        // Get all assignments for classes the learner is enrolled in
        $assignments = Assignment::whereIn('class_id', $classIds)
            ->with('class', 'tutor.user')
            ->with(['submissions' => function ($q) use ($learner) {
                $q->where('learner_id', $learner->id);
            }])
            ->orderBy('due_date', 'desc')
            ->get();

        return view('parent.child-progress', compact(
            'learner', 'attendance', 'upcomingClasses', 'assignments'
        ));
    }
}


