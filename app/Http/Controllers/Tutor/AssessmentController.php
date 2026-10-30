<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentResultRequest;
use App\Models\AssessmentSession;
use App\Models\Classes;
use App\Services\AssessmentResultService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * "My assessments" for a tutor: today's call with a Join button, what is coming
 * up, the history, and the result form for the calls they ran (note 13, phase 3).
 *
 * A tutor only ever sees sessions assigned to them.
 */
class AssessmentController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;

        $assessments = $tutor
            ? AssessmentSession::with(['enquiry'])
                ->where('tutor_id', $tutor->id)
                ->orderBy('scheduled_at')
                ->get()
            : collect();

        return view('tutor.assessments.index', [
            'tutor' => $tutor,
            'today' => $assessments->filter(function ($assessment) {
                return $assessment->isToday() && $assessment->isScheduled();
            })->values(),
            'upcoming' => $assessments->filter(function ($assessment) {
                return $assessment->isUpcoming() && ! $assessment->isToday() && $assessment->isScheduled();
            })->values(),
            'history' => $assessments->filter(function ($assessment) {
                return ! $assessment->isUpcoming() || ! $assessment->isScheduled();
            })->sortByDesc('scheduled_at')->values(),
        ]);
    }

    public function show(AssessmentSession $assessment)
    {
        $this->authorizeOwn($assessment);

        $assessment->load(['enquiry', 'recommendedClass', 'conductedBy']);

        return view('tutor.assessments.show', [
            'assessment' => $assessment,
            'classes' => Classes::orderBy('name')->get(),
        ]);
    }

    public function recordResult(AssessmentResultRequest $request, AssessmentSession $assessment, AssessmentResultService $results)
    {
        $this->authorizeOwn($assessment);

        $validated = $request->validated();

        try {
            $results->record($assessment, $validated, $request->file('report'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tutor.assessments.show', $assessment)
            ->with('success', $validated['result'] === AssessmentSession::STATUS_COMPLETED
                ? 'Assessment result saved.'
                : 'Marked as a no-show — the school will rebook it.');
    }

    public function downloadReport(AssessmentSession $assessment)
    {
        $this->authorizeOwn($assessment);

        $path = $assessment->attachment_path;

        abort_unless($path && Storage::disk('public')->exists($path), 404, 'No report was uploaded for this assessment.');

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';
        $name = 'assessment-' . (optional($assessment->enquiry)->reference ?: $assessment->id) . '.' . $extension;

        return Storage::disk('public')->download($path, $name);
    }

    /** A tutor may only open or record their own assessments. */
    private function authorizeOwn(AssessmentSession $assessment): void
    {
        $tutor = auth()->user()->tutor;

        abort_unless($tutor && (int) $assessment->tutor_id === (int) $tutor->id, 403);
    }
}
