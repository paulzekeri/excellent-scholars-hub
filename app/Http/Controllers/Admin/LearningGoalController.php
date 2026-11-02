<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\LearningGoal;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Administrators and the CEO review the goals tutors upload and decide which
 * individual parents, learners and tutors receive each one.
 */
class LearningGoalController extends Controller
{
    public function index(Request $request)
    {
        $tutorId = $request->query('tutor_id');
        $shared = $request->query('shared');

        $goals = LearningGoal::with('tutor.user', 'learner.user', 'schoolClass', 'sharedBy', 'recipients.role')
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
            ->when($shared === 'individual', function ($query) {
                $query->whereHas('recipients');
            })
            ->when($shared === 'unshared', function ($query) {
                $query->where('shared_with_parent', false)
                    ->where('shared_with_tutor', false)
                    ->where('shared_with_learner', false)
                    ->whereDoesntHave('recipients');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // The people the school can pick from, grouped by role.
        $people = User::with('role')
            ->whereHas('role', function ($query) {
                $query->whereIn('slug', ['parent', 'learner', 'tutor']);
            })
            ->orderBy('name')
            ->get()
            ->groupBy(function (User $user) {
                return optional($user->role)->slug ?: 'user';
            });

        return view('admin.learning-goals.index', [
            'goals' => $goals,
            'tutors' => Tutor::with('user')->get(),
            'classes' => Classes::orderBy('name')->get(),
            'parents' => $people->get('parent', collect()),
            'learners' => $people->get('learner', collect()),
            'tutorUsers' => $people->get('tutor', collect()),
            'tutorId' => $tutorId,
            'shared' => $shared,
            'counts' => [
                'total' => LearningGoal::count(),
                'parent' => LearningGoal::where('shared_with_parent', true)->count(),
                'tutor' => LearningGoal::where('shared_with_tutor', true)->count(),
                'learner' => LearningGoal::where('shared_with_learner', true)->count(),
                'individual' => LearningGoal::whereHas('recipients')->count(),
                'unshared' => LearningGoal::where('shared_with_parent', false)
                    ->where('shared_with_tutor', false)
                    ->where('shared_with_learner', false)
                    ->whereDoesntHave('recipients')
                    ->count(),
            ],
        ]);
    }

    /**
     * Share a goal with individual people (and/or everyone a role covers).
     *
     * The old quick switches still work: when no share_form marker is posted we
     * only flip the three role flags, exactly as before.
     */
    public function share(Request $request, LearningGoal $goal)
    {
        $roleIds = Role::whereIn('slug', ['parent', 'learner', 'tutor'])->pluck('id', 'slug');

        $validated = $request->validate([
            'shared_with_parent' => 'nullable|boolean',
            'shared_with_tutor' => 'nullable|boolean',
            'shared_with_learner' => 'nullable|boolean',
            'share_form' => 'nullable|boolean',
            'share_all_parent' => 'nullable|boolean',
            'share_all_learner' => 'nullable|boolean',
            'share_all_tutor' => 'nullable|boolean',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => ['integer', Rule::exists('users', 'id')->where('role_id', $roleIds['parent'] ?? 0)],
            'learner_ids' => 'nullable|array',
            'learner_ids.*' => ['integer', Rule::exists('users', 'id')->where('role_id', $roleIds['learner'] ?? 0)],
            'tutor_ids' => 'nullable|array',
            'tutor_ids.*' => ['integer', Rule::exists('users', 'id')->where('role_id', $roleIds['tutor'] ?? 0)],
        ], [
            'parent_ids.*.exists' => 'Choose parents from the list.',
            'learner_ids.*.exists' => 'Choose learners from the list.',
            'tutor_ids.*.exists' => 'Choose tutors from the list.',
        ]);

        $shortGoal = \Illuminate\Support\Str::limit($goal->goalText(), 60);

        // Legacy quick switches (kept for the existing screens and tests). They
        // meant "share with everyone of that role", so the "every …" flags mirror
        // them and the chips keep matching the switches.
        if (! $request->boolean('share_form')) {
            $flags = [
                'shared_with_parent' => (bool) ($validated['shared_with_parent'] ?? false),
                'shared_with_learner' => (bool) ($validated['shared_with_learner'] ?? false),
                'shared_with_tutor' => (bool) ($validated['shared_with_tutor'] ?? false),
            ];

            $flags['share_all_parent'] = $flags['shared_with_parent'];
            $flags['share_all_learner'] = $flags['shared_with_learner'];
            $flags['share_all_tutor'] = $flags['shared_with_tutor'];

            $isShared = in_array(true, $flags, true);

            $goal->update($flags + [
                'shared_by' => $isShared ? auth()->id() : null,
                'shared_at' => $isShared ? now() : null,
            ]);

            $this->syncImpliedRecipients($goal, $flags);

            return back()->with('success', 'Sharing updated for "' . $shortGoal . '".');
        }

        $groups = [
            'parent' => [
                'all' => $request->boolean('share_all_parent'),
                'ids' => array_values(array_unique($validated['parent_ids'] ?? [])),
            ],
            'learner' => [
                'all' => $request->boolean('share_all_learner'),
                'ids' => array_values(array_unique($validated['learner_ids'] ?? [])),
            ],
            'tutor' => [
                'all' => $request->boolean('share_all_tutor'),
                'ids' => array_values(array_unique($validated['tutor_ids'] ?? [])),
            ],
        ];

        // One pivot row per person, carrying the role so the lists can be grouped.
        $sync = [];
        foreach ($groups as $slug => $group) {
            foreach ($group['ids'] as $userId) {
                $sync[$userId] = ['role_slug' => $slug, 'shared_by' => auth()->id()];
            }
        }

        $goal->recipients()->sync($sync);

        $flags = [
            // "Shared with that role" drives the list filters and the legacy
            // visibility scopes, so it also goes on when somebody is chosen by
            // name. The explicit "every …" tick is recorded separately below.
            'shared_with_parent' => $groups['parent']['all'] || $groups['parent']['ids'] !== [],
            'shared_with_learner' => $groups['learner']['all'] || $groups['learner']['ids'] !== [],
            'shared_with_tutor' => $groups['tutor']['all'] || $groups['tutor']['ids'] !== [],
            'share_all_parent' => $groups['parent']['all'],
            'share_all_learner' => $groups['learner']['all'],
            'share_all_tutor' => $groups['tutor']['all'],
        ];

        $isShared = $sync !== [] || $groups['parent']['all'] || $groups['learner']['all'] || $groups['tutor']['all'];

        $goal->update($flags + [
            'shared_by' => $isShared ? auth()->id() : null,
            'shared_at' => $isShared ? now() : null,
        ]);

        if (! $isShared) {
            return back()->with('success', 'Sharing switched off for "' . $shortGoal . '".');
        }

        $people = count($sync);

        return back()->with('success', 'Sharing updated for "' . $shortGoal . '".'
            . ($people > 0
                ? ' ' . $people . ' ' . \Illuminate\Support\Str::plural('person', $people) . ' chosen by name.'
                : ' Available to everyone the goal is about.'));
    }

    /**
     * Attach/remove the people a role flag implies (the learner's parent, the
     * learner, the tutor who wrote it) so both sharing styles stay in step.
     *
     * @param  array<string, bool>  $flags
     */
    private function syncImpliedRecipients(LearningGoal $goal, array $flags): void
    {
        $implied = [
            'parent' => optional(optional($goal->learner)->parent)->user_id,
            'learner' => optional($goal->learner)->user_id,
            'tutor' => optional($goal->tutor)->user_id,
        ];

        foreach ($implied as $slug => $userId) {
            if (! $userId) {
                continue;
            }

            if (! empty($flags['shared_with_' . $slug])) {
                $goal->recipients()->syncWithoutDetaching([
                    $userId => ['role_slug' => $slug, 'shared_by' => auth()->id()],
                ]);
            } else {
                $goal->recipients()->detach($userId);
            }
        }
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
