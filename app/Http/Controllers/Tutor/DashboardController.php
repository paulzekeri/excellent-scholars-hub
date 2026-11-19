<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Assignment;
use App\Models\AssessmentSession;
use App\Models\LearningMaterial;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;
        
        $data = [
            // Includes classes where this tutor takes a subject, even when
            // another tutor is the class's lead tutor.
            'myClasses' => Classes::taughtBy($tutor->id)->count(),
            'upcomingSchedules' => ClassSchedule::where('tutor_id', $tutor->id)
                ->where('start_time', '>', now())
                ->with('class')
                ->orderBy('start_time')
                ->take(5)
                ->get(),
            'totalAssignments' => Assignment::where('tutor_id', $tutor->id)->count(),
            'totalMaterials' => LearningMaterial::where('tutor_id', $tutor->id)->count(),
            'todayAssessments' => AssessmentSession::with('enquiry')
                ->where('tutor_id', $tutor->id)
                ->whereDate('scheduled_at', today())
                ->where('status', AssessmentSession::STATUS_SCHEDULED)
                ->orderBy('scheduled_at')
                ->get(),
        ];

        return view('tutor.dashboard', $data);
    }
}
