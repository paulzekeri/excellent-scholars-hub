<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Enquiry;
use App\Models\EnquiryNote;
use App\Models\Grade;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Note 13 (phase 1) — admissions before a learner exists.
 *
 * Capture a WhatsApp/phone enquiry in seconds, keep the pipeline honest with
 * status transitions, and record every call and message on a timeline so the
 * school stops searching chat history.
 */
class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'open');
        $search = trim((string) $request->query('search'));
        $source = (string) $request->query('source');

        $query = Enquiry::with(['assignedTo', 'createdBy'])->latest();

        if ($status === 'open') {
            $query->whereIn('status', Enquiry::OPEN_STATUSES);
        } elseif ($status !== 'all' && array_key_exists($status, Enquiry::STATUSES)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('child_name', 'like', '%' . $search . '%')
                    ->orWhere('guardian_name', 'like', '%' . $search . '%')
                    ->orWhere('guardian_phone', 'like', '%' . $search . '%')
                    ->orWhere('guardian_email', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%');
            });
        }

        if ($source !== '' && array_key_exists($source, Enquiry::SOURCES)) {
            $query->where('source', $source);
        }

        return view('admin.enquiries.index', [
            'enquiries' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'search' => $search,
            'source' => $source,
            'counts' => [
                'all' => Enquiry::count(),
                'open' => Enquiry::whereIn('status', Enquiry::OPEN_STATUSES)->count(),
                'new' => Enquiry::where('status', Enquiry::STATUS_NEW)->count(),
                'contacted' => Enquiry::where('status', Enquiry::STATUS_CONTACTED)->count(),
                'declined' => Enquiry::where('status', Enquiry::STATUS_DECLINED)->count(),
                'archived' => Enquiry::where('status', Enquiry::STATUS_ARCHIVED)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.enquiries.create', [
            'enquiry' => new Enquiry(['source' => Enquiry::SOURCE_WHATSAPP, 'status' => Enquiry::STATUS_NEW]),
            'owners' => $this->owners(),
            'grades' => Grade::active()->ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEnquiry($request);

        $enquiry = Enquiry::create($validated + [
            'status' => Enquiry::STATUS_NEW,
            'created_by' => auth()->id(),
            'assigned_to' => $validated['assigned_to'] ?? auth()->id(),
        ]);

        $enquiry->timeline()->create([
            'user_id' => auth()->id(),
            'type' => EnquiryNote::TYPE_STATUS,
            'body' => 'Enquiry captured from ' . $enquiry->sourceLabel() . '.',
        ]);

        return redirect()->route('admin.enquiries.show', $enquiry)
            ->with('success', 'Enquiry ' . $enquiry->reference . ' captured.');
    }

    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['timeline.user', 'assignedTo', 'createdBy', 'assessments.tutor.user', 'convertedLearner.user']);

        return view('admin.enquiries.show', [
            'enquiry' => $enquiry,
            'nextStatuses' => $enquiry->nextStatuses(),
            'tutors' => Tutor::with('user')->get()
                ->sortBy(function ($tutor) {
                    return strtolower((string) optional($tutor->user)->name);
                })
                ->values(),
            'canBookAssessment' => $enquiry->status === Enquiry::STATUS_CONTACTED,
        ]);
    }

    public function edit(Enquiry $enquiry)
    {
        return view('admin.enquiries.edit', [
            'enquiry' => $enquiry,
            'owners' => $this->owners(),
            'grades' => Grade::active()->ordered()->get(),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $validated = $this->validateEnquiry($request);

        $enquiry->update($validated);

        return redirect()->route('admin.enquiries.show', $enquiry)
            ->with('success', 'Enquiry details updated.');
    }

    /**
     * Move the enquiry along the pipeline. Only transitions declared in
     * Enquiry::STATUS_FLOW are accepted, and declining needs a reason.
     */
    public function updateStatus(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Enquiry::STATUSES))],
            'declined_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $enquiry->canMoveTo($validated['status'])) {
            return back()->with('error', 'An enquiry cannot move from ' . $enquiry->statusLabel() . ' to that status.');
        }

        // The assessment result is the evidence for these two states.
        if ($validated['status'] === Enquiry::STATUS_ASSESSED) {
            $latest = $enquiry->latestCompletedAssessment();

            if (! $latest || ! $latest->outcome) {
                return back()->with('error', 'Record the assessment result before marking it Assessed.');
            }
        }

        if ($validated['status'] === Enquiry::STATUS_PLACED) {
            $latest = $enquiry->latestCompletedAssessment();

            if (! $latest || ! $latest->recommended_class_id) {
                return back()->with('error', 'Choose a recommended class on the assessment result before placing the child.');
            }
        }

        if ($validated['status'] === Enquiry::STATUS_DECLINED && empty($validated['declined_reason'])) {
            return back()->withErrors(['declined_reason' => 'Give a reason when declining an enquiry.'])->withInput();
        }

        $previous = $enquiry->statusLabel();

        $enquiry->update([
            'status' => $validated['status'],
            'declined_reason' => $validated['status'] === Enquiry::STATUS_DECLINED
                ? $validated['declined_reason']
                : null,
        ]);

        $body = 'Status changed from ' . $previous . ' to ' . $enquiry->statusLabel();

        if ($validated['status'] === Enquiry::STATUS_DECLINED) {
            $body .= ': ' . $validated['declined_reason'];
        }

        $enquiry->timeline()->create([
            'user_id' => auth()->id(),
            'type' => EnquiryNote::TYPE_STATUS,
            'body' => $body,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'enquiry.status',
            'description' => 'Enquiry ' . $enquiry->reference . ' moved to ' . $enquiry->statusLabel(),
            'model_type' => Enquiry::class,
            'model_id' => $enquiry->id,
        ]);

        return back()->with('success', 'Enquiry marked as ' . $enquiry->statusLabel() . '.');
    }

    public function storeNote(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(EnquiryNote::MANUAL_TYPES))],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $enquiry->timeline()->create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Timeline updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEnquiry(Request $request): array
    {
        $validated = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'child_dob' => ['nullable', 'date', 'before:today'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            // Legacy text field, still accepted from older forms.
            'child_grade_level' => ['nullable', 'string', 'max:100'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['required', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'source' => ['required', Rule::in(array_keys(Enquiry::SOURCES))],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // The selected grade is the source of truth; keep the text column in
        // step so the enquiry list and conversion see the same label.
        if (! empty($validated['grade_id'])) {
            $grade = Grade::find($validated['grade_id']);
            $validated['child_grade_level'] = $grade ? $grade->label() : null;
        }

        return $validated;
    }

    /** Administrators and the CEO can own an enquiry. */
    private function owners()
    {
        return User::visible()->whereHas('role', function ($query) {
            $query->whereIn('slug', ['admin', 'ceo']);
        })->orderBy('name')->get();
    }
}
