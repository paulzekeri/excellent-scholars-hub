<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\LearningGoal;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Administrators and the CEO review the goals tutors upload and decide what is
 * shared with the parent and/or the tutor.
 */
class LearningGoalController extends Controller
{
    public function index(Request $request)
    {
        $tutorId = $request->query('tutor_id');
        $shared = $request->query('shared');

        $goals = LearningGoal::with('tutor.user', 'learner.user', 'schoolClass', 'sharedBy')
            ->when($tutorId, function ($query, $value) {
                $query->where('tutor_id', $value);
            })
            ->when($shared === 'parent', function ($query) {
                $query->where('shared_with_parent', true);
            })
            ->when($shared === 'tutor', function ($query) {
                $query->where('shared_with_tutor', true);
            })
            ->when($shared === 'learner', function ($query) {
                $query->where('shared_with_learner', true);
            })
            ->when($shared === 'unshared', function ($query) {
                $query->where('shared_with_parent', false)
                    ->where('shared_with_tutor', false)
                    ->where('shared_with_learner', false);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.learning-goals.index', [
            'goals' => $goals,
            'tutors' => Tutor::with('user')->get(),
            'classes' => Classes::orderBy('name')->get(),
            'tutorId' => $tutorId,
            'shared' => $shared,
            'counts' => [
                'total' => LearningGoal::count(),
                'parent' => LearningGoal::where('shared_with_parent', true)->count(),
                'tutor' => LearningGoal::where('shared_with_tutor', true)->count(),
                'learner' => LearningGoal::where('shared_with_learner', true)->count(),
                'unshared' => LearningGoal::where('shared_with_parent', false)
                    ->where('shared_with_tutor', false)
                    ->where('shared_with_learner', false)
                    ->count(),
            ],
        ]);
    }

    /**
     * Toggle the three sharing switches for a goal.
     */
    public function share(Request $request, LearningGoal $goal)
    {
        $validated = $request->validate([
            'shared_with_parent' => 'nullable|boolean',
            'shared_with_tutor' => 'nullable|boolean',
            'shared_with_learner' => 'nullable|boolean',
        ]);

        $shareWithParent = (bool) ($validated['shared_with_parent'] ?? false);
        $shareWithTutor = (bool) ($validated['shared_with_tutor'] ?? false);
        $shareWithLearner = (bool) ($validated['shared_with_learner'] ?? false);
        $isShared = $shareWithParent || $shareWithTutor || $shareWithLearner;

        $goal->update([
            'shared_with_parent' => $shareWithParent,
            'shared_with_tutor' => $shareWithTutor,
            'shared_with_learner' => $shareWithLearner,
            'shared_by' => $isShared ? auth()->id() : null,
            'shared_at' => $isShared ? now() : null,
        ]);

        return back()->with('success', 'Sharing updated for "' . $goal->title . '".');
    }

    public function download(LearningGoal $goal)
    {
        if (! $goal->file_path || ! Storage::disk('public')->exists($goal->file_path)) {
            return back()->with('error', 'No file was attached to this goal.');
        }

        return Storage::disk('public')->download(
            $goal->file_path,
            $goal->file_name ?: basename($goal->file_path)
        );
    }
}
