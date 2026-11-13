<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Mail\AccountProvisionedMail;
use App\Services\LearnerPasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    /**
     * Every user, with a role filter (client note: "filter by roles to be
     * added"), plus a quick name/username/email search and status filter.
     */
    public function index(Request $request)
    {
        $role = $request->query('role');
        $search = trim((string) $request->query('q'));
        $status = $request->query('status');

        $users = User::with(['role', 'learner.parent.user', 'parent.children'])
            ->when($role && $role !== 'all', function ($query) use ($role) {
                $query->whereHas('role', function ($roleQuery) use ($role) {
                    $roleQuery->where('slug', $role);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('username', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'roleFilter' => $role,
            'search' => $search,
            'statusFilter' => $status,
            'roleCounts' => User::selectRaw('role_id, COUNT(*) as total')
                ->groupBy('role_id')
                ->pluck('total', 'role_id'),
        ]);
    }

    public function create()
    {
        $roles = $this->assignableRoles();
        $parents = $this->parentOptions();
        return view('admin.users.create', compact('roles', 'parents'));
    }

    /**
     * Create an account on behalf of the school.
     *
     * Parents and learners are provisioned with a temporary password that is
     * emailed to the guardian (note 12); they choose their own password at first
     * sign-in. A learner must always have a parent/guardian, because that
     * address is the reset channel for a child without a mailbox.
     */
    public function store(Request $request)
    {
        $role = Role::findOrFail((int) $request->input('role_id'));
        $this->ensureRoleAssignable($role);

        $isLearner = $role->slug === 'learner';
        $isParent = $role->slug === 'parent';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                ($isLearner || $isParent) ? 'required' : 'nullable',
                'string', 'min:3', 'max:30', 'alpha_dash',
                'not_in:' . implode(',', User::RESERVED_USERNAMES),
                Rule::unique('users', 'username'),
            ],
            'email' => [
                $isLearner ? 'nullable' : 'required',
                'email', 'max:255', Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'parent_id' => [$isLearner ? 'required' : 'nullable', 'exists:parents,id'],
        ], [
            'username.required' => 'A username is required for parents and learners — it is how they sign in.',
            'parent_id.required' => 'Choose the parent/guardian. Every child must have a reachable guardian.',
        ]);

        $user = DB::transaction(function () use ($validated, $role, $isLearner, $isParent) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => ! empty($validated['username']) ? strtolower($validated['username']) : null,
                'email' => ($isLearner && empty($validated['email'])) ? null : ($validated['email'] ?? null),
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'phone' => $validated['phone'] ?? null,
                // The school set this password, so it must be replaced.
                'must_change_password' => $isLearner || $isParent,
            ]);

            if ($role->slug === 'tutor') {
                Tutor::create(['user_id' => $user->id]);
            } elseif ($isLearner) {
                Learner::create([
                    'user_id' => $user->id,
                    'parent_id' => $validated['parent_id'],
                ]);
            } elseif ($isParent) {
                ParentModel::create(['user_id' => $user->id]);
            }

            return $user;
        });

        $this->sendProvisioningMail($user->fresh(['learner.parent.user', 'parent.children.user']), $validated['password']);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created. The login details were emailed to ' . ($user->deliveryEmail() ?: 'the account holder') . '.');
    }

    public function edit(User $user)
    {
        $this->ensureUserManageable($user);

        $roles = $this->assignableRoles();
        $parents = $this->parentOptions();
        $user->load(['learner.parent.user', 'parent.children.user']);

        return view('admin.users.edit', compact('user', 'roles', 'parents'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureUserManageable($user);

        $role = Role::findOrFail((int) $request->input('role_id'));
        $this->ensureRoleAssignable($role);

        $isLearner = $role->slug === 'learner';
        $isParent = $role->slug === 'parent';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                ($isLearner || $isParent) ? 'required' : 'nullable',
                'string', 'min:3', 'max:30', 'alpha_dash',
                'not_in:' . implode(',', User::RESERVED_USERNAMES),
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                $isLearner ? 'nullable' : 'required',
                'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'parent_id' => [$isLearner ? 'required' : 'nullable', 'exists:parents,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'username.required' => 'A username is required for parents and learners — it is how they sign in.',
            'parent_id.required' => 'Choose the parent/guardian. Every child must have a reachable guardian.',
        ]);

        DB::transaction(function () use ($request, $user, $role, $isLearner, $validated) {
            $user->update([
                'name' => $validated['name'],
                'username' => ! empty($validated['username']) ? strtolower($validated['username']) : null,
                'email' => ($isLearner && empty($validated['email'])) ? null : ($validated['email'] ?? null),
                'role_id' => $role->id,
                'phone' => $validated['phone'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            // Keep the profile rows in sync with the role.
            if ($isLearner) {
                $learner = $user->learner ?: Learner::create(['user_id' => $user->id]);
                $learner->update(['parent_id' => $validated['parent_id']]);
            }

            if ($role->slug === 'tutor' && ! $user->tutor) {
                Tutor::create(['user_id' => $user->id]);
            }

            if ($role->slug === 'parent' && ! $user->parent) {
                ParentModel::create(['user_id' => $user->id]);
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->ensureUserManageable($user);

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Create a child account directly under a parent (note 12).
     *
     * The school supplies a username and a temporary password; the guardian
     * receives the details and the child chooses their own password at first
     * sign-in.
     */
    public function addChild(Request $request, User $user)
    {
        $this->ensureUserManageable($user);

        $parent = $user->parent;

        if (! $parent) {
            return back()->with('error', 'That account is not a parent account.');
        }

        if (! $user->is_active) {
            return back()->with('error', 'Activate the guardian account before adding a child to it.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'min:3', 'max:30', 'alpha_dash',
                'not_in:' . implode(',', User::RESERVED_USERNAMES),
                Rule::unique('users', 'username'),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $learnerRole = Role::where('slug', 'learner')->firstOrFail();

        $child = DB::transaction(function () use ($validated, $parent, $learnerRole) {
            $child = User::create([
                'name' => $validated['name'],
                'username' => strtolower($validated['username']),
                'email' => $validated['email'] ?: null,
                'password' => Hash::make($validated['password']),
                'role_id' => $learnerRole->id,
                'must_change_password' => true,
            ]);

            Learner::create([
                'user_id' => $child->id,
                'parent_id' => $parent->id,
            ]);

            return $child;
        });

        $this->sendProvisioningMail(
            $child->fresh(['learner.parent.user']),
            $validated['password']
        );

        return redirect()->route('admin.users.edit', $user)
            ->with('success', $child->name . '\'s account is ready. The login details were sent to the guardian.');
    }

    /**
     * Email the temporary credentials.
     *
     * A child's details always go to the guardian (the school never hands a
     * password straight to a child) and also to the child's own address when one
     * is on file. A guardian's own account mail lists their linked children's
     * usernames so the family has every login in one place.
     */
    private function sendProvisioningMail(User $user, string $plainPassword): void
    {
        $recipients = [];
        $linked = [];
        $guardianOf = null;

        if ($user->isLearner()) {
            $guardian = optional(optional($user->learner)->parent)->user;
            $guardianOf = $user->name;

            if ($guardian && $guardian->email) {
                $recipients[] = $guardian->email;
            }

            if ($user->email) {
                $recipients[] = $user->email;
            }
        } else {
            if ($user->email) {
                $recipients[] = $user->email;
            }

            foreach (optional($user->parent)->children ?? collect() as $child) {
                if ($child->user) {
                    $linked[] = [
                        'name' => $child->user->name,
                        'username' => $child->user->username ?: '—',
                        'relationship' => 'Child',
                    ];
                }
            }
        }

        $recipients = array_values(array_unique(array_filter($recipients)));

        foreach ($recipients as $address) {
            try {
                Mail::to($address)->send(new AccountProvisionedMail(
                    $user->name,
                    $user->username ?: (string) $user->email,
                    $plainPassword,
                    $linked,
                    $guardianOf
                ));
            } catch (Throwable $e) {
                // One undeliverable address should not stop the other recipient.
                report($e);
                continue;
            }
        }

        if (! $this->mailCanDeliver()) {
            session()->flash('provisioned_credentials', [
                'name' => $user->name,
                'username' => $user->username ?: $user->email,
                'password' => $plainPassword,
                'sent_to' => $recipients[0] ?? null,
            ]);
        }
    }

    /**
     * Send a one-time reset link to a user who forgot their password.
     *
     * When the installation has no working mailer (the live server uses the `log`
     * driver), the link is shown to the administrator instead so nobody is locked out.
     * The link is single-use and expires after `auth.passwords.users.expire`.
     */
    public function sendPasswordReset(User $user)
    {
        $this->ensureUserManageable($user);

        if (! $user->is_active) {
            return back()->with('error', 'This account is inactive. Activate it before resetting the password.');
        }

        if (! $user->email) {
            return $this->sendGuardianResetLink($user);
        }

        $expires = (int) config('auth.passwords.users.expire', 60);

        if (! $this->mailCanDeliver()) {
            return $this->shareResetLink(
                $user,
                'Email delivery is not configured on this installation, so share this one-time link privately (it expires in ' . $expires . ' minutes).'
            );
        }

        try {
            $status = Password::broker()->sendResetLink(['email' => $user->email]);
        } catch (Throwable $e) {
            report($e);

            return $this->shareResetLink(
                $user,
                'The reset email could not be sent. Share this one-time link privately instead (it expires in ' . $expires . ' minutes).'
            );
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Password reset link sent to ' . $user->email . '.');
        }

        return back()->with('error', __($status));
    }

    /**
     * Reset a child who has no mailbox: the one-time link goes to the guardian,
     * or is flashed for the administrator when mail cannot deliver (as on the live server).
     */
    private function sendGuardianResetLink(User $user)
    {
        $service = app(LearnerPasswordResetService::class);
        $guardianEmail = $service->guardianEmail($user);

        if (! $guardianEmail) {
            return back()->with('error', 'This child has no email and no guardian email on file. Add a guardian email before resetting the password.');
        }

        if ($this->mailCanDeliver()) {
            return $service->send($user)
                ? back()->with('success', 'A one-time reset link was emailed to the guardian (' . $guardianEmail . ').')
                : back()->with('error', 'The reset email could not be sent. Ask the guardian to use "Forgot your password" with the child\'s username.');
        }

        $plain = $service->createToken($user);
        $expires = (int) config('auth.passwords.users.expire', 60);

        return back()
            ->with('success', 'Email delivery is not configured, so share this one-time link with the guardian (' . $guardianEmail . '). It expires in ' . $expires . ' minutes.')
            ->with('password_reset_link', $service->resetUrl($user, $plain))
            ->with('password_reset_user', $user->name . ' (guardian: ' . $guardianEmail . ')');
    }

    /** The `log` and `array` mailers accept mail but never deliver it. */
    private function mailCanDeliver(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array', null, ''], true);
    }

    /** Create a token and flash the reset URL for the administrator to pass on. */
    private function shareResetLink(User $user, string $message)
    {
        $token = Password::broker()->createToken($user);

        return back()
            ->with('success', $message)
            ->with('password_reset_link', route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]))
            ->with('password_reset_user', $user->name);
    }

    /**
     * Parent profiles that learners can be linked to (one parent, many learners).
     */
    private function parentOptions()
    {
        return ParentModel::with('user')
            ->get()
            ->filter(function ($parent) {
                return $parent->user !== null;
            })
            ->sortBy(function ($parent) {
                return strtolower($parent->user->name);
            })
            ->values();
    }

    /**
     * Regular administrators cannot see or assign the CEO role.
     */
    private function assignableRoles()
    {
        $roles = Role::query();

        if (!auth()->user()->isCeo()) {
            $roles->where('slug', '!=', 'ceo');
        }

        return $roles->get();
    }

    private function ensureRoleAssignable(Role $role)
    {
        if ($role->slug === 'ceo' && !auth()->user()->isCeo()) {
            abort(403, 'Only the CEO can assign the CEO role.');
        }
    }

    private function ensureUserManageable(User $user)
    {
        if ($user->isCeo() && !auth()->user()->isCeo()) {
            abort(403, 'Only the CEO can manage a CEO account.');
        }
    }
}
