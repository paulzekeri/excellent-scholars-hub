<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Testimonial;
use Illuminate\Http\Request;

/**
 * Note 10 — the CEO reviews member testimonials. Only approved ones appear on
 * the public landing page (and only then is the member's photo published, if
 * they allowed it).
 */
class TestimonialController extends Controller
{
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
