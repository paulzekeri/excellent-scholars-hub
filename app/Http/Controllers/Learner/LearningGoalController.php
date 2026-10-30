<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\LearningGoal;
use Illuminate\Support\Facades\Storage;

/**
 * Note 7 — goals the school shares with the learner themselves (their own goals
 * and class-wide goals for classes they attend).
 */
class LearningGoalController extends Controller
{
    public function index()
    {
        $learner = auth()->user()->learner;
        abort_unless($learner, 403, 'Only learners have this page.');

        $goals = LearningGoal::visibleToLearner($learner)
            ->with('tutor.user', 'schoolClass', 'sharedBy')
            ->latest('shared_at')
            ->paginate(10);

        return view('learner.learning-goals.index', compact('learner', 'goals'));
    }

    public function download(LearningGoal $goal)
    {
        $learner = auth()->user()->learner;

        abort_unless($learner && $goal->isVisibleToLearner($learner), 403, 'This goal is not shared with you.');

        if (! $goal->file_path || ! Storage::disk('public')->exists($goal->file_path)) {
            return back()->with('error', 'No file was attached to this goal.');
        }

        return Storage::disk('public')->download(
            $goal->file_path,
            $goal->file_name ?: basename($goal->file_path)
        );
    }
}
