<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSession;

/**
 * "My assessments" for a tutor: today's call with a Join button, what is coming
 * up, and the history. A tutor only ever sees sessions assigned to them.
 */
class AssessmentController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;

        $assessments = $tutor
            ? AssessmentSession::with(['enquiry'])
                ->where('tutor_id', $tutor->id)
                ->orderBy('scheduled_at')
                ->get()
            : collect();

        return view('tutor.assessments.index', [
            'tutor' => $tutor,
            'today' => $assessments->filter(function ($assessment) {
                return $assessment->isToday() && $assessment->isScheduled();
            })->values(),
            'upcoming' => $assessments->filter(function ($assessment) {
                return $assessment->isUpcoming() && ! $assessment->isToday() && $assessment->isScheduled();
            })->values(),
            'history' => $assessments->filter(function ($assessment) {
                return ! $assessment->isUpcoming() || ! $assessment->isScheduled();
            })->sortByDesc('scheduled_at')->values(),
        ]);
    }
}
