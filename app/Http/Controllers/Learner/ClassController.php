<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\LearningMaterial;
use App\Services\ClassScheduleService;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:learner');
    }

    public function index(Request $request)
    {
        $learner = auth()->user()->learner;

        // The page opens on the current week, like the admin and tutor schedule
        // screens. "All sessions" keeps the full history one click away.
        $scope = $request->query('view') === 'all' ? 'all' : 'week';
        $weekStart = $scope === 'week' ? ClassScheduleService::weekStart($request->query('week')) : null;
        $weekEnd = $weekStart ? $weekStart->copy()->addDays(6) : null;
        $weekEndExclusive = $weekStart ? $weekStart->copy()->addDays(7) : null;

        $classes = $learner->classes()
            ->with([
                'subjectAssignments.subject',
                'subjectAssignments.tutor.user',
                'schedules' => function ($query) use ($weekStart, $weekEndExclusive) {
                    $query->where('status', '!=', 'cancelled')->orderBy('start_time');

                    if ($weekStart) {
                        $query->where('start_time', '>=', $weekStart)
                            ->where('start_time', '<', $weekEndExclusive);
                    }
                },
                'schedules.classSubject.subject',
            ])
            ->get();

        return view('learner.classes', compact('classes', 'scope', 'weekStart', 'weekEnd'));
    }

    public function materials()
    {
        $learner = auth()->user()->learner;
        $classIds = $learner->classes()->pluck('classes.id');
        $materials = LearningMaterial::whereIn('class_id', $classIds)->latest()->paginate(12);
        return view('learner.materials', compact('materials'));
    }
}
