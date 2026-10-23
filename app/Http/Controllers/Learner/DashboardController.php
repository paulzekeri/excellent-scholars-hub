<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Assignment;
use App\Models\LearningMaterial;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $learner = auth()->user()->learner;
        
        $classIds = $learner->classes->pluck('id');

        $data = [
            'myClasses' => $learner->classes()->with('tutor.user')->get(),
            'upcomingClasses' => ClassSchedule::whereIn('class_id', $classIds)
                ->where('start_time', '>', now())
                ->with('class', 'tutor.user')
                ->orderBy('start_time')
                ->take(5)
                ->get(),
            'upcomingCount' => ClassSchedule::whereIn('class_id', $classIds)
                ->where('start_time', '>', now())
                ->count(),
            'pendingAssignments' => Assignment::whereIn('class_id', $classIds)
                ->where('status', 'active')
                ->where('due_date', '>', now())
                ->count(),
            'materials' => LearningMaterial::whereIn('class_id', $classIds)
                ->latest()
                ->take(5)
                ->get(),
        ];


        return view('learner.dashboard', $data);
    }

    public function joinClass(ClassSchedule $schedule)
    {
        return redirect($schedule->meeting_link);
    }
}
