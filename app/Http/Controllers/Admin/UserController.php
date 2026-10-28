<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\Learner;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'learner.parent.user', 'parent.children'])->latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->assignableRoles();
        $parents = $this->parentOptions();
        return view('admin.users.create', compact('roles', 'parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'parent_id' => 'nullable|exists:parents,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $this->ensureRoleAssignable($role);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
        ]);

        if ($role->slug === 'tutor') {
            Tutor::create(['user_id' => $user->id]);
        } elseif ($role->slug === 'learner') {
            // A learner may be created before their parent, so parent_id is optional.
            Learner::create([
                'user_id' => $user->id,
                'parent_id' => $request->input('parent_id') ?: null,
            ]);
        } elseif ($role->slug === 'parent') {
            ParentModel::create(['user_id' => $user->id]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
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

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'parent_id' => 'nullable|exists:parents,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $this->ensureRoleAssignable($role);

        $user->update($request->only('name', 'email', 'role_id', 'phone', 'is_active'));

        // Keep the learner profile (and its parent link) in sync with the role.
        if ($role->slug === 'learner') {
            $learner = $user->learner ?: Learner::create(['user_id' => $user->id]);
            $learner->update(['parent_id' => $request->input('parent_id') ?: null]);
        }

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
     * Send a one-time reset link to a user who forgot their password.
     *
     * When the installation has no working mailer (UAT uses the `log` driver),
     * the link is shown to the administrator instead so nobody is locked out.
     * The link is single-use and expires after `auth.passwords.users.expire`.
     */
    public function sendPasswordReset(User $user)
    {
        $this->ensureUserManageable($user);

        if (! $user->is_active) {
            return back()->with('error', 'This account is inactive. Activate it before resetting the password.');
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
