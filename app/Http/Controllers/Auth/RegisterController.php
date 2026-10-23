<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Models\Role;
use App\Models\Learner;
use App\Models\Tutor;
use App\Models\ParentModel;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        // Exclude 'parent' from registration dropdown - only learners & tutors can self-register
        $roles = Role::whereIn('slug', ['learner', 'tutor'])->get();
        return view('auth.register', compact('roles'));
    }

    protected function validator(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        // If registering as a learner, parent details are required
        $role = \App\Models\Role::find($data['role_id'] ?? 0);
        if ($role && $role->slug === 'learner') {
            $rules['parent_name'] = ['required', 'string', 'max:255'];
            $rules['parent_email'] = ['required', 'string', 'email', 'max:255'];
        }

        return Validator::make($data, $rules);
    }

    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'phone' => $data['phone'] ?? null,
        ]);

        $role = Role::find($data['role_id']);

        if ($role->slug === 'tutor') {
            Tutor::create(['user_id' => $user->id]);
        } elseif ($role->slug === 'learner') {
            // Create learner record
            $learner = Learner::create([
                'user_id' => $user->id,
            ]);

            // Check if learner provided parent details
            if (!empty($data['parent_name']) && !empty($data['parent_email'])) {
                $parentEmail = $data['parent_email'];
                $parentName = $data['parent_name'];
                $defaultPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);

                // Check if parent user already exists by email
                $parentUser = User::where('email', $parentEmail)->first();

                if (!$parentUser) {
                    // Get parent role
                    $parentRole = Role::where('slug', 'parent')->first();

                    // Create parent user account
                    $parentUser = User::create([
                        'name' => $parentName,
                        'email' => $parentEmail,
                        'password' => Hash::make($defaultPassword),
                        'role_id' => $parentRole->id,
                    ]);

                    // Create parent model record
                    $parent = ParentModel::create([
                        'user_id' => $parentUser->id,
                    ]);

                    // Link learner to parent
                    $learner->update(['parent_id' => $parent->id]);

                    // Send email to parent with login credentials
                    try {
                        Mail::to($parentEmail)->send(new \App\Mail\ParentWelcomeMail(
                            $parentName,
                            $parentEmail,
                            $defaultPassword,
                            $user->name
                        ));
                    } catch (\Exception $e) {
                        // Log the error but don't break registration
                        Log::error('Failed to send parent welcome email: ' . $e->getMessage());
                    }
                } else {
                    // Parent user already exists - link them
                    $parent = ParentModel::where('user_id', $parentUser->id)->first();
                    if ($parent) {
                        $learner->update(['parent_id' => $parent->id]);
                    }
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
