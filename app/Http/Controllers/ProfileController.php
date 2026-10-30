<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Note 10 — a member edits their own name, email and profile photo, and can
 * submit a testimonial for the CEO to approve.
 */
class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(Request $request)
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'testimonial' => $user->testimonial,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $isLearner = $user->isLearner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                $isLearner ? 'nullable' : 'required',
                'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'An email address is required for your role.',
        ]);

        $attributes = [
            'name' => $validated['name'],
            'email' => ! empty($validated['email']) ? $validated['email'] : null,
        ];

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $attributes['photo_path'] = $request->file('photo')->store('profile-photos', 'public');
        } elseif ($request->boolean('remove_photo') && $user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $attributes['photo_path'] = null;
        }

        $user->update($attributes);

        return redirect()->route('profile.edit')->with('success', 'Your profile has been updated.');
    }

    public function storeTestimonial(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'publish_photo' => ['nullable', 'boolean'],
        ], [
            'body.required' => 'Write a few words for your testimonial.',
        ]);

        $publishPhoto = $request->boolean('publish_photo');

        if ($publishPhoto && ! $user->photo_path) {
            return back()->withErrors([
                'publish_photo' => 'Add a profile photo first, or leave the photo option switched off.',
            ])->withInput();
        }

        $testimonial = $user->testimonial ?: new Testimonial(['user_id' => $user->id]);

        $testimonial->fill([
            'body' => $validated['body'],
            'publish_photo' => $publishPhoto,
            // Any edit goes back to the CEO for approval.
            'status' => Testimonial::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'review_note' => null,
        ])->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Thank you — your testimonial was sent to the CEO for approval.');
    }
}
