<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Learner;
use App\Models\LearningGoal;
use App\Services\GoalWordingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LearningGoalController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;
        abort_unless($tutor, 403, 'Only tutors can manage learning goals.');

        $myGoals = LearningGoal::with('learner.user', 'schoolClass', 'recipients.role')
            ->where('tutor_id', $tutor->id)
            ->latest()
            ->paginate(10);

        $classIds = Classes::where('tutor_id', $tutor->id)->pluck('id');

        // Goals an administrator shared with this tutor: either "with tutors"
        // for one of their classes, or shared with this tutor by name.
        $sharedGoals = LearningGoal::with('learner.user', 'schoolClass', 'sharedBy', 'recipients.role')
            ->where(function ($query) use ($tutor, $classIds) {
                $query->where(function ($legacy) use ($tutor, $classIds) {
                    $legacy->where('shared_with_tutor', true)
                        ->where('tutor_id', '!=', $tutor->id)
                        ->where(function ($scope) use ($classIds) {
                            $scope->whereIn('class_id', $classIds)->orWhereNull('class_id');
                        });
                })->orWhereHas('recipients', function ($share) {
                    $share->where('user_id', auth()->id());
                });
            })
            ->latest('shared_at')
            ->get();

        return view('tutor.learning-goals.index', compact('myGoals', 'sharedGoals'));
    }

    public function create()
    {
        $tutor = auth()->user()->tutor;
        abort_unless($tutor, 403, 'Only tutors can manage learning goals.');

        return view('tutor.learning-goals.create', [
            'classes' => Classes::where('tutor_id', $tutor->id)->orderBy('name')->get(),
            'wordingAssistant' => (bool) config('services.languagetool.enabled', true),
        ]);
    }

    public function store(Request $request)
    {
        $tutor = auth()->user()->tutor;
        abort_unless($tutor, 403, 'Only tutors can manage learning goals.');

        $validated = $request->validate([
            // The goal itself is now a free text area, not a one-line title.
            'title' => 'required|string|max:5000',
            'description' => 'nullable|string|max:5000',
            'target' => 'nullable|string|max:255',
            'term' => 'nullable|string|max:50',
            'class_id' => [
                'nullable',
                Rule::exists('classes', 'id')->where(function ($query) use ($tutor) {
                    $query->where('tutor_id', $tutor->id);
                }),
            ],
            'learner_id' => 'nullable|exists:learners,id',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:8192',
        ]);

        if (! empty($validated['learner_id'])) {
            $learner = Learner::findOrFail($validated['learner_id']);
            $teachesLearner = $learner->classes()
                ->where('classes.tutor_id', $tutor->id)
                ->exists();

            abort_unless($teachesLearner, 403, 'That learner is not in one of your classes.');
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_path'] = $file->store('learning-goals', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
        }

        unset($validated['file']);

        LearningGoal::create(array_merge($validated, [
            'tutor_id' => $tutor->id,
            'status' => 'active',
        ]));

        return redirect()
            ->route('tutor.learning-goals.index')
            ->with('success', 'Learning goal saved. Administrators can now share it with individual parents, learners and tutors.');
    }

    /**
     * Free wording assistant (LanguageTool) used by the "Improve wording" button.
     */
    public function polish(Request $request, GoalWordingService $wording)
    {
        abort_unless(auth()->user()->tutor, 403, 'Only tutors can manage learning goals.');

        $validated = $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        return response()->json($wording->assist($validated['text']));
    }

    public function destroy(LearningGoal $goal)
    {
        $this->ensureOwnedGoal($goal);

        if ($goal->file_path) {
            Storage::disk('public')->delete($goal->file_path);
        }

        $goal->delete();

        return back()->with('success', 'Learning goal removed.');
    }

    public function download(LearningGoal $goal)
    {
        $this->ensureOwnedGoal($goal);

        if (! $goal->file_path || ! Storage::disk('public')->exists($goal->file_path)) {
            return back()->with('error', 'No file was attached to this goal.');
        }

        return Storage::disk('public')->download(
            $goal->file_path,
            $goal->file_name ?: basename($goal->file_path)
        );
    }

    /**
     * A tutor may download their own goals and any goal the school shared with
     * them by name.
     */
    private function ensureOwnedGoal(LearningGoal $goal): void
    {
        $tutor = auth()->user()->tutor;

        $owns = $tutor && (int) $goal->tutor_id === (int) $tutor->id;

        abort_unless(
            $owns || $goal->isSharedWithUser(auth()->id()),
            403,
            'You are not allowed to access this goal.'
        );
    }
}
