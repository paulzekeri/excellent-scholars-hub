<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountProvisionedMail;
use App\Models\ActivityLog;
use App\Models\Classes;
use App\Models\Enquiry;
use App\Models\EnquiryNote;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Note 13 phase 4 — converting an "Awaiting enrolment" enquiry into real
 * accounts: the parent (or an existing one, matched by email), the learner, and
 * the class enrolment. Reuses note 12's provisioning (unique usernames, guardian
 * email required, temporary passwords, forced first-login change).
 */
class EnquiryConversionController extends Controller
{
    public function show(Enquiry $enquiry)
    {
        if ($reason = $this->blockReason($enquiry)) {
            return redirect()->route('admin.enquiries.show', $enquiry)->with('error', $reason);
        }

        $assessment = $enquiry->latestCompletedAssessment();
        $guardian = $enquiry->guardian_email
            ? User::where('email', $enquiry->guardian_email)->first()
            : null;

        return view('admin.enquiries.convert', [
            'enquiry' => $enquiry,
            'assessment' => $assessment,
            'classes' => Classes::where('is_active', true)->orderBy('name')->get(),
            'recommendedClassId' => optional($assessment)->recommended_class_id,
            'existingParent' => $guardian && $guardian->isParent() ? $guardian : null,
            'suggestedUsername' => User::suggestUsername($enquiry->child_name),
            'suggestedParentUsername' => User::suggestUsername($enquiry->guardian_name),
        ]);
    }

    public function store(Request $request, Enquiry $enquiry)
    {
        if ($reason = $this->blockReason($enquiry)) {
            return redirect()->route('admin.enquiries.show', $enquiry)->with('error', $reason);
        }

        if (! $enquiry->guardian_email) {
            return back()->withErrors([
                'guardian_email' => 'Add the guardian email before converting — it is where the logins are sent.',
            ])->withInput();
        }

        $guardian = User::where('email', $enquiry->guardian_email)->first();

        if ($guardian && ! $guardian->isParent()) {
            return back()->withErrors([
                'guardian_email' => 'That email belongs to a staff or learner account. Use the guardian\'s own email.',
            ])->withInput();
        }

        $parent = $guardian
            ? ($guardian->parent ?: ParentModel::create(['user_id' => $guardian->id]))
            : null;

        $validated = $request->validate([
            'child_username' => [
                'required', 'string', 'min:3', 'max:30', 'alpha_dash',
                'not_in:' . implode(',', User::RESERVED_USERNAMES),
                Rule::unique('users', 'username'),
            ],
            'child_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'parent_username' => [
                $parent ? 'nullable' : 'required',
                'string', 'min:3', 'max:30', 'alpha_dash',
                'not_in:' . implode(',', User::RESERVED_USERNAMES),
                Rule::unique('users', 'username'),
            ],
            'class_id' => ['required', 'exists:classes,id'],
        ], [
            'child_username.required' => 'Give the child a username — it is how they sign in.',
            'parent_username.required' => 'Give the guardian a username — it is how they sign in.',
        ]);

        $class = Classes::findOrFail($validated['class_id']);

        if (! $class->hasCapacity()) {
            return back()->withErrors([
                'class_id' => 'That class is full. Choose another class or raise its capacity first.',
            ])->withInput();
        }

        $childPassword = $this->temporaryPassword();
        $parentPassword = null;

        $result = DB::transaction(function () use ($enquiry, $validated, &$parent, &$parentPassword, $childPassword, $class) {
            $enquiry = Enquiry::whereKey($enquiry->id)->lockForUpdate()->firstOrFail();

            if ($enquiry->converted_learner_id) {
                return ['already' => true, 'learner' => $enquiry->convertedLearner];
            }

            $createdParent = false;

            if (! $parent) {
                $parentRole = Role::where('slug', 'parent')->firstOrFail();
                $parentPassword = $this->temporaryPassword();

                $parentUser = User::create([
                    'name' => $enquiry->guardian_name,
                    'username' => strtolower($validated['parent_username']),
                    'email' => $enquiry->guardian_email,
                    'password' => Hash::make($parentPassword),
                    'role_id' => $parentRole->id,
                    'must_change_password' => true,
                ]);

                $parent = ParentModel::create(['user_id' => $parentUser->id]);
                $createdParent = true;
            }

            $learnerRole = Role::where('slug', 'learner')->firstOrFail();

            $childUser = User::create([
                'name' => $enquiry->child_name,
                'username' => strtolower($validated['child_username']),
                'email' => ! empty($validated['child_email']) ? $validated['child_email'] : null,
                'password' => Hash::make($childPassword),
                'role_id' => $learnerRole->id,
                'must_change_password' => true,
            ]);

            $learner = Learner::create([
                'user_id' => $childUser->id,
                'parent_id' => $parent->id,
                'grade_id' => $enquiry->grade_id,
                'grade_level' => $enquiry->child_grade_level,
            ]);

            if (! $class->learners()->where('learner_id', $learner->id)->exists()) {
                $class->learners()->attach($learner->id);
            }

            $enquiry->update([
                'status' => Enquiry::STATUS_ENROLLED,
                'converted_learner_id' => $learner->id,
                'converted_at' => now(),
            ]);

            $enquiry->timeline()->create([
                'user_id' => auth()->id(),
                'type' => EnquiryNote::TYPE_STATUS,
                'body' => 'Converted to a learner (username ' . $childUser->username . ') and enrolled in ' . $class->name . '.',
            ]);

            return [
                'already' => false,
                'learner' => $learner,
                'childUser' => $childUser,
                'parent' => $parent,
                'createdParent' => $createdParent,
            ];
        });

        $enquiry->refresh();

        if (! empty($result['already'])) {
            return redirect()->route('admin.enquiries.show', $enquiry)
                ->with('error', 'This enquiry has already been converted.');
        }

        $this->sendCredentials($result['childUser'], $childPassword, $result['parent'], $result['createdParent'], $parentPassword);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'enquiry.converted',
            'description' => 'Enquiry ' . $enquiry->reference . ' converted and enrolled in ' . $class->name,
            'model_type' => Learner::class,
            'model_id' => $result['learner']->id,
        ]);

        return redirect()->route('admin.enquiries.show', $enquiry)
            ->with('success', 'Converted — ' . $result['learner']->user->name . ' is enrolled in ' . $class->name . '. The login details were emailed.');
    }

    /** Why this enquiry cannot be converted (null when it can). */
    private function blockReason(Enquiry $enquiry): ?string
    {
        if ($enquiry->converted_learner_id) {
            return 'This enquiry has already been converted to a learner.';
        }

        if ($enquiry->status !== Enquiry::STATUS_PLACED) {
            return 'The enquiry must be Awaiting enrolment before converting. Record the assessment result and choose a recommended class first.';
        }

        $assessment = $enquiry->latestCompletedAssessment();

        if (! $assessment || ! $assessment->recommended_class_id) {
            return 'The latest assessment has no recommended class, so there is nothing to enrol the child in.';
        }

        return null;
    }

    private function temporaryPassword(): string
    {
        return substr(str_shuffle('abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 10);
    }

    /**
     * Email the temporary credentials (note 12). New accounts must change them at
     * first sign-in; when the mailer cannot deliver, the values are flashed for
     * the administrator to hand over.
     */
    private function sendCredentials(User $childUser, string $childPassword, ParentModel $parent, bool $createdParent, ?string $parentPassword): void
    {
        $guardianEmail = optional($parent->user)->email;

        $accounts = [];

        if ($createdParent && $parentPassword && $parent->user) {
            $accounts[] = ['user' => $parent->user, 'password' => $parentPassword];
        }

        $accounts[] = ['user' => $childUser, 'password' => $childPassword];

        foreach ($accounts as $account) {
            $isParent = $account['user']->isParent();

            $recipients = $isParent
                ? [$account['user']->email]
                : array_values(array_unique(array_filter([$guardianEmail, $account['user']->email])));

            foreach ($recipients as $address) {
                try {
                    Mail::to($address)->send(new AccountProvisionedMail(
                        $account['user']->name,
                        $account['user']->username,
                        $account['password'],
                        $isParent ? [[
                            'name' => $childUser->name,
                            'username' => $childUser->username,
                            'relationship' => 'Child',
                        ]] : [],
                        $isParent ? null : $childUser->name
                    ));
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }

        if ($this->mailCannotDeliver()) {
            session()->flash('conversion_credentials', array_map(function ($account) {
                return [
                    'name' => $account['user']->name,
                    'username' => $account['user']->username,
                    'password' => $account['password'],
                ];
            }, $accounts));
        }
    }

    private function mailCannotDeliver(): bool
    {
        return in_array(config('mail.default'), ['log', 'array', null, ''], true);
    }
}
