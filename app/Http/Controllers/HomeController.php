<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Testimonial;

class HomeController extends Controller
{
    /**
     * The landing page for guests; signed-in users are sent to their own
     * dashboard. (Was a closure in routes/web.php on Laravel 8, which made
     * `php artisan route:cache` impossible.)
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $user = auth()->user();

        if ($user) {
            if ($user->isAdministrator()) return redirect()->route('admin.dashboard');
            if ($user->isTutor()) return redirect()->route('tutor.dashboard');
            if ($user->isParent()) return redirect()->route('parent.dashboard');
            if ($user->isLearner()) return redirect()->route('learner.dashboard');
        }

        // Public marketing page: sliders, programmes, testimonials, about, register.
        return view('landing', [
            'programmes' => Classes::with('tutor.user')
                ->where('is_active', true)
                ->orderBy('name')
                ->take(6)
                ->get(),
            // Approved member testimonials (note 10); the view falls back to samples.
            'testimonials' => Testimonial::with('user.role')
                ->where('status', Testimonial::STATUS_APPROVED)
                ->whereNotNull('approved_at')
                ->latest('approved_at')
                ->take(6)
                ->get(),
        ]);
    }

    /**
     * The in-app user guide (also a closure on Laravel 8).
     */
    public function userGuide()
    {
        return view('help.user-guide');
    }
}
