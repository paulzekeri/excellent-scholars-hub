<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Assignment;
use App\Models\LearningMaterial;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;
        
        $data = [
            'myClasses' => Classes::where('tutor_id', $tutor->id)->count(),
            'upcomingSchedules' => ClassSchedule::where('tutor_id', $tutor->id)
                ->where('start_time', '>', now())
                ->with('class')
                ->orderBy('start_time')
                ->take(5)
                ->get(),
            'totalAssignments' => Assignment::where('tutor_id', $tutor->id)->count(),
            'totalMaterials' => LearningMaterial::where('tutor_id', $tutor->id)->count(),
        ];

        return view('tutor.dashboard', $data);
    }
}
