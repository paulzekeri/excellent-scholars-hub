<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Testimonial;
use Illuminate\Http\Request;

/**
 * Note 10 — administrators and the CEO review member testimonials. Only
 * approved ones appear on the landing page (and only then is the member's photo
 * published, if they allowed it). Reviewers may tidy the wording before
 * approving, because members often write the way they speak.
 */
class TestimonialController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->check() && auth()->user()->isAdministrator(), 403, 'Only administrators and the CEO can review testimonials.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $status = (string) $request->query('status', Testimonial::STATUS_PENDING);

        $query = Testimonial::with(['user.role', 'approvedBy'])->latest();

        if ($status !== 'all' && array_key_exists($status, Testimonial::STATUSES)) {
            $query->where('status', $status);
        }

        return view('admin.testimonials.index', [
            'testimonials' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'counts' => [
                'pending' => Testimonial::where('status', Testimonial::STATUS_PENDING)->count(),
                'approved' => Testimonial::where('status', Testimonial::STATUS_APPROVED)->count(),
                'rejected' => Testimonial::where('status', Testimonial::STATUS_REJECTED)->count(),
                'all' => Testimonial::count(),
            ],
        ]);
    }

    public function approve(Testimonial $testimonial)
    {
        $testimonial->update([
            'status' => Testimonial::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'review_note' => null,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'testimonial.approved',
            'description' => 'Approved the testimonial from ' . optional($testimonial->user)->name . '.',
            'model_type' => Testimonial::class,
            'model_id' => $testimonial->id,
        ]);

        return back()->with('success', 'Testimonial approved — it is now on the landing page.');
    }

    /**
     * Polish the wording without changing where the testimonial stands.
     *
     * Members often write the way they speak; a reviewer fixes the English so
     * the quote can be published as it stands.
     */
    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
            'publish_photo' => 'nullable|boolean',
        ], [
            'body.required' => 'The testimonial still needs some words.',
            'body.max' => 'Keep the testimonial under 1,000 characters.',
        ]);

        $testimonial->update([
            'body' => $validated['body'],
            'publish_photo' => $request->has('publish_photo')
                ? $request->boolean('publish_photo')
                : $testimonial->publish_photo,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'testimonial.edited',
            'description' => 'Edited the wording of the testimonial from ' . optional($testimonial->user)->name . '.',
            'model_type' => Testimonial::class,
            'model_id' => $testimonial->id,
        ]);

        return back()->with('success', 'Testimonial updated.');
    }

    public function reject(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $testimonial->update([
            'status' => Testimonial::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'review_note' => $validated['review_note'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'testimonial.rejected',
            'description' => 'Rejected the testimonial from ' . optional($testimonial->user)->name . '.',
            'model_type' => Testimonial::class,
            'model_id' => $testimonial->id,
        ]);

        return back()->with('success', 'Testimonial rejected. The member sees the reason on their profile.');
    }
}
