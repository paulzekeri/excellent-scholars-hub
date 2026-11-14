<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Learner;
use App\Models\ParentModel;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Roles that may create an account themselves. Only a learner may sign up
     * here: tutors are staff and are added by an administrator or the CEO from
     * Users > Add user. Parents are provisioned by the school (or automatically
     * from a learner's guardian details).
     */
    public const SELF_REGISTER_ROLES = ['learner'];

    public function showRegistrationForm()
    {
        // The public form is learner-only, so the sorted lookup simply keeps the
        // single selectable role in one place.
        $roles = Role::whereIn('slug', self::SELF_REGISTER_ROLES)
            ->get()
            ->sortBy(function (Role $role) {
                return array_search($role->slug, self::SELF_REGISTER_ROLES, true);
            })
            ->values();

        return view('auth.register', compact('roles'));
    }

    protected function validator(array $data)
    {
        $role = \App\Models\Role::find($data['role_id'] ?? 0);
        $isLearner = $role && $role->slug === 'learner';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // Note 12: a child usually has no mailbox, so the username is the login.
            'username' => [
                $isLearner ? 'required' : 'nullable',
                'string', 'min:3', 'max:30', 'alpha_dash',
                'not_in:' . implode(',', User::RESERVED_USERNAMES),
                'unique:users,username',
            ],
            'email' => [
                $isLearner ? 'nullable' : 'required',
                'string', 'email', 'max:255', 'unique:users',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // The role travels in a hidden field and must be a learner, so a
            // crafted role_id cannot create a tutor or any other staff account.
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->whereIn('slug', self::SELF_REGISTER_ROLES);
                }),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        // A child must always have a guardian — that address is the reset
        // channel and the record the username is tied to.
        if ($isLearner) {
            $rules['parent_name'] = ['required', 'string', 'max:255'];
            $rules['parent_email'] = ['required', 'string', 'email', 'max:255'];
        }

        $validator = Validator::make($data, $rules, [
            'username.required' => 'Choose a username — it is what your child will sign in with.',
            'parent_email.required' => 'The parent/guardian email is required. It is where login details and password resets are sent.',
        ]);

        if ($isLearner && ! empty($data['parent_email'])) {
            $validator->after(function ($validator) use ($data) {
                $guardian = User::where('email', $data['parent_email'])->first();

                if ($guardian && ! $guardian->isParent()) {
                    $validator->errors()->add(
                        'parent_email',
                        'That email belongs to another account. Please use the parent/guardian\'s own email.'
                    );
                }

                if (! empty($data['email']) && strcasecmp($data['email'], $data['parent_email']) === 0) {
                    $validator->errors()->add(
                        'email',
                        'Your own email must be different from your parent/guardian\'s email.'
                    );
                }
            });
        }

        return $validator;
    }

    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'username' => ! empty($data['username']) ? strtolower($data['username']) : null,
            'email' => ! empty($data['email']) ? $data['email'] : null,
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'phone' => $data['phone'] ?? null,
        ]);

        $role = Role::find($data['role_id']);

        if ($role->slug === 'learner') {
            // Create learner record
            $learner = Learner::create([
                'user_id' => $user->id,
            ]);

            // Check if learner provided parent details
            if (!empty($data['parent_name']) && !empty($data['parent_email'])) {
                $parentEmail = $data['parent_email'];
                $parentName = $data['parent_name'];

                // Check if parent user already exists by email
                $parentUser = User::where('email', $parentEmail)->first();

                if (!$parentUser) {
                    // The guardian account is created with a temporary password and
                    // must be changed at first sign-in (note 12).
                    $defaultPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789'), 0, 10);
                    $parentRole = Role::where('slug', 'parent')->firstOrFail();

                    $parentUser = User::create([
                        'name' => $parentName,
                        'username' => User::suggestUsername($parentName),
                        'email' => $parentEmail,
                        'password' => Hash::make($defaultPassword),
                        'role_id' => $parentRole->id,
                        'must_change_password' => true,
                    ]);

                    $parent = ParentModel::create([
                        'user_id' => $parentUser->id,
                    ]);

                    // Link learner to parent
                    $learner->update(['parent_id' => $parent->id]);

                    // Send the guardian their credentials plus the child's username.
                    try {
                        Mail::to($parentEmail)->send(new \App\Mail\AccountProvisionedMail(
                            $parentName,
                            $parentUser->username,
                            $defaultPassword,
                            [[
                                'name' => $user->name,
                                'username' => $user->username ?: (string) $user->email,
                                'relationship' => 'Child',
                            ]]
                        ));
                    } catch (\Throwable $e) {
                        // Log the error but don't break registration
                        Log::error('Failed to send guardian welcome email: ' . $e->getMessage());
                    }
                } else {
                    // Parent user already exists - link them (and create a missing profile row)
                    $parent = ParentModel::where('user_id', $parentUser->id)->first()
                        ?: ParentModel::create(['user_id' => $parentUser->id]);

                    $learner->update(['parent_id' => $parent->id]);
                }
            }
        } elseif ($role->slug === 'parent') {
            ParentModel::create(['user_id' => $user->id]);
        }

        return $user;
    }

    protected function registered(Request $request, $user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isTutor()) {
            return redirect()->route('tutor.dashboard');
        } elseif ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        } elseif ($user->isLearner()) {
            return redirect()->route('learner.dashboard');
        }
        return redirect($this->redirectTo);
    }
}
