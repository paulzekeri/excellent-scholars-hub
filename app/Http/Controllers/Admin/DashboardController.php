<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\Tutor;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [
            'totalUsers' => User::count(),
            'totalTutors' => Tutor::count(),
            'totalLearners' => Learner::count(),
            'totalClasses' => Classes::count(),
            'totalSchedules' => ClassSchedule::count(),
            'totalPayments' => Payment::count(),
            'recentUsers' => User::with('role')->latest()->take(5)->get(),
            'upcomingClasses' => ClassSchedule::with('class', 'tutor.user')
                ->where('start_time', '>', now())
                ->orderBy('start_time')
                ->take(5)
                ->get(),
        ];

        return view('admin.dashboard', $data);
    }
}
