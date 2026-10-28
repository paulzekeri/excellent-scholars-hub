<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Learner;
use App\Models\LearningGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LearningGoalController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;
        abort_unless($tutor, 403, 'Only tutors can manage learning goals.');

        $myGoals = LearningGoal::with('learner.user', 'schoolClass')
            ->where('tutor_id', $tutor->id)
            ->latest()
            ->paginate(10);

        $classIds = Classes::where('tutor_id', $tutor->id)->pluck('id');

        // Goals an administrator chose to share with the tutor.
        $sharedGoals = LearningGoal::with('learner.user', 'schoolClass', 'sharedBy')
            ->where('shared_with_tutor', true)
            ->where('tutor_id', '!=', $tutor->id)
            ->where(function ($query) use ($classIds) {
                $query->whereIn('class_id', $classIds)->orWhereNull('class_id');
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
            'learners' => Learner::with('user')
                ->whereHas('classes', function ($query) use ($tutor) {
                    $query->where('classes.tutor_id', $tutor->id);
                })
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tutor = auth()->user()->tutor;
        abort_unless($tutor, 403, 'Only tutors can manage learning goals.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
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

        // A goal has to be about somebody: a learner, a class, or both.
        if (empty($validated['class_id']) && empty($validated['learner_id'])) {
            return back()->withInput()->withErrors([
                'learner_id' => 'Choose the learner or the class this goal is for.',
            ]);
        }

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
            ->with('success', 'Learning goal saved. Administrators can now share it with the parent.');
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

    private function ensureOwnedGoal(LearningGoal $goal): void
    {
        $tutor = auth()->user()->tutor;

        abort_unless($tutor && (int) $goal->tutor_id === (int) $tutor->id, 403, 'You are not allowed to access this goal.');
    }
}
