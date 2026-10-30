<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Affirmation;
use Illuminate\Http\Request;

/**
 * Note 8 — the school writes the weekly affirmation. The latest active post is
 * shown on every dashboard; older ones remain in the history.
 */
class AffirmationController extends Controller
{
    public function index()
    {
        return view('admin.affirmations.index', [
            'current' => Affirmation::current(),
            'affirmations' => Affirmation::with('author')->latest('published_at')->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ], [
            'body.required' => 'Write the affirmation the learners should see.',
        ]);

        $affirmation = Affirmation::create([
            'body' => $validated['body'],
            'author_id' => auth()->id(),
            'is_active' => true,
            'published_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'affirmation.published',
            'description' => 'Published a new affirmation for the dashboards.',
            'model_type' => Affirmation::class,
            'model_id' => $affirmation->id,
        ]);

        return redirect()->route('admin.affirmations.index')
            ->with('success', 'Affirmation published — it is now on every dashboard.');
    }

    public function toggle(Affirmation $affirmation)
    {
        $activating = ! $affirmation->is_active;

        $affirmation->update([
            'is_active' => $activating,
            'published_at' => $activating ? now() : $affirmation->published_at,
        ]);

        return back()->with('success', $activating
            ? 'Affirmation switched back on.'
            : 'Affirmation switched off; the previous one (or the built-in rotation) shows instead.');
    }

    public function destroy(Affirmation $affirmation)
    {
        $affirmation->delete();

        return back()->with('success', 'Affirmation deleted.');
    }
}
