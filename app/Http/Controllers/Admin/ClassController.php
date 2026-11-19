<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\Learner;
use App\Models\Subject;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function index()
    {
        // Subject rows are eager loaded so the list shows every subject without
        // a query per class.
        $classes = Classes::with([
                'tutor.user',
                'subjectAssignments.subject',
                'subjectAssignments.tutor.user',
            ])
            ->withCount('learners')
            ->latest()
            ->paginate(10);

        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        $subjectRows = $this->subjectRowsForForm(null);
        $subjects = Subject::orderBy('name')->get();
        $grades = Grade::ordered()->get();

        return view('admin.classes.create', compact('tutors', 'subjectRows', 'subjects', 'grades'));
    }

    public function store(Request $request)
    {
        $this->normaliseSubjectSelection($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Optional: a blank code is generated from the class name below.
            'code' => 'nullable|string|max:50|unique:classes,code',
            'tutor_id' => 'required|exists:tutors,id',
            'grade_level' => 'nullable|string',
            'grade_id' => 'nullable|integer|exists:grades,id',
            // Legacy single-subject field, still accepted from older forms.
            'subject' => 'nullable|string|max:255',
            // A class can teach several subjects, each with its own teacher.
            'subjects' => 'nullable|array',
            'subjects.*.subject_id' => 'nullable|integer|exists:subjects,id',
            'subjects.*.subject' => 'nullable|string|max:255',
            'subjects.*.tutor_id' => 'nullable|exists:tutors,id',
            'max_students' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $validated['code'] = $this->generateCode($validated['code'] ?? null, $validated['name']);
        $this->applyGradeLabel($validated);

        $subjectRows = $this->subjectRowsFromRequest($request, (int) $validated['tutor_id']);
        unset($validated['subjects']);

        DB::transaction(function () use ($validated, $subjectRows) {
            $class = Classes::create($validated);
            $class->syncSubjectAssignments($subjectRows);
        });

        return redirect()->route('admin.classes.index')->with('success', 'Class created successfully.');
    }

    public function show(Classes $class)
    {
        $class->load(
            'tutor.user',
            'learners.user',
            'subjectAssignments.subject',
            'subjectAssignments.tutor.user'
        );

        $learners = Learner::with('user')->where('is_active', true)->get();
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        $subjects = Subject::orderBy('name')->get();

        return view('admin.classes.show', compact('class', 'learners', 'tutors', 'subjects'));
    }

    public function edit(Classes $class)
    {
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        $subjectRows = $this->subjectRowsForForm($class);
        $subjects = Subject::orderBy('name')->get();
        $grades = Grade::ordered()->get();

        return view('admin.classes.edit', compact('class', 'tutors', 'subjectRows', 'subjects', 'grades'));
    }

    public function update(Request $request, Classes $class)
    {
        $this->normaliseSubjectSelection($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Optional: leaving it blank keeps the code the class already has.
            'code' => 'nullable|string|max:50|unique:classes,code,' . $class->id,
            'tutor_id' => 'required|exists:tutors,id',
            'grade_level' => 'nullable|string',
            'grade_id' => 'nullable|integer|exists:grades,id',
            'subject' => 'nullable|string|max:255',
            'subjects' => 'nullable|array',
            'subjects.*.subject_id' => 'nullable|integer|exists:subjects,id',
            'subjects.*.subject' => 'nullable|string|max:255',
            'subjects.*.tutor_id' => 'nullable|exists:tutors,id',
            'max_students' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['code'])) {
            unset($validated['code']);
        } else {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        // A request that says nothing about subjects (an API client renaming a
        // class, for example) leaves the existing subject rows untouched.
        $hasSubjectInput = $request->has('subjects') || $request->filled('subject');

        $this->applyGradeLabel($validated);

        $subjectRows = $this->subjectRowsFromRequest($request, (int) $validated['tutor_id']);
        unset($validated['subjects']);

        DB::transaction(function () use ($class, $validated, $subjectRows, $hasSubjectInput) {
            $class->update($validated);

            if ($hasSubjectInput) {
                $class->syncSubjectAssignments($subjectRows);
            }
        });

        return redirect()->route('admin.classes.index')->with('success', 'Class updated successfully.');
    }

    /**
     * Add one subject (and its teacher) to an existing class from the class
     * page. Adding a subject the class already teaches simply reassigns the
     * teacher, so the same form works for "change Physics teacher".
     */
    public function storeSubject(Request $request, Classes $class)
    {
        if ($request->input('subject_id') === '__new__') {
            $request->merge(['subject_id' => null]);
        }

        $validated = $request->validate([
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'subject' => 'nullable|string|max:255',
            'tutor_id' => 'nullable|exists:tutors,id',
        ]);

        $subject = ! empty($validated['subject_id'])
            ? Subject::find($validated['subject_id'])
            : null;

        if (! $subject) {
            $name = trim((string) ($validated['subject'] ?? ''));

            if ($name === '') {
                return back()
                    ->withErrors(['subject' => 'Choose a subject from the list or type a new subject name.'])
                    ->withInput();
            }

            $subject = Subject::firstOrCreate(['name' => $name]);
        }

        $class->subjectAssignments()->updateOrCreate(
            ['subject_id' => $subject->id],
            ['tutor_id' => $validated['tutor_id'] ?? null]
        );

        $this->syncLegacySubjectColumn($class);

        return back()->with('success', $subject->name . ' was added to ' . $class->name . '.');
    }

    /** Remove a subject from a class (its teacher is not affected). */
    public function destroySubject(Classes $class, ClassSubject $classSubject)
    {
        abort_unless((int) $classSubject->class_id === (int) $class->id, 404);

        // Scheduled lessons belong to a subject. Removing the subject first
        // would leave those sessions without one, so they are cleared first.
        if ($classSubject->schedules()->exists()) {
            return back()->with(
                'error',
                optional($classSubject->subject)->name . ' still has scheduled sessions. Delete or move those sessions first, then remove the subject.'
            );
        }

        $name = optional($classSubject->subject)->name ?: 'The subject';

        $classSubject->delete();
        $this->syncLegacySubjectColumn($class);

        return back()->with('success', $name . ' was removed from ' . $class->name . '.');
    }

    /**
     * Keep the original single `subject` column equal to the first subject row,
     * so older screens, exports and reports that still read it stay correct.
     */
    private function syncLegacySubjectColumn(Classes $class): void
    {
        $first = $class->subjectAssignments()->with('subject')->orderBy('id')->first();

        $class->forceFill([
            'subject' => $first && $first->subject ? $first->subject->name : null,
        ])->save();

        $class->unsetRelation('subjectAssignments');
    }

    /**
     * Keep the legacy `grade_level` column equal to the chosen grade's label.
     *
     * @param  array<string, mixed>  $validated
     */
    private function applyGradeLabel(array &$validated): void
    {
        if (! empty($validated['grade_id'])) {
            $grade = Grade::find($validated['grade_id']);
            $validated['grade_level'] = $grade ? $grade->label() : null;
        }
    }

    /**
     * Turn the special "+ Add a new subject…" option into "no subject id", so
     * validation accepts the request and the typed name is used instead.
     */
    private function normaliseSubjectSelection(Request $request): void
    {
        $rows = $request->input('subjects');

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (is_array($row) && ($row['subject_id'] ?? null) === '__new__') {
                $rows[$index]['subject_id'] = null;
            }
        }

        $request->merge(['subjects' => array_values($rows)]);
    }

    /**
     * Read the subject rows from the class form.
     *
     * Each row either points at a catalogue subject (`subject_id`) or carries a
     * new subject name typed after choosing "+ Add a new subject…". A form that
     * still posts only the old single `subject` field (or an API client written
     * before this feature) is converted into one row led by the class tutor.
     *
     * @return array<int, array{subject_id: ?int, subject: ?string, tutor_id: ?int}>
     */
    private function subjectRowsFromRequest(Request $request, int $leadTutorId): array
    {
        $rows = $request->input('subjects', []);

        if (! is_array($rows)) {
            $rows = [];
        }

        $rows = array_values(array_filter(array_map(function ($row) {
            if (! is_array($row)) {
                return null;
            }

            $subjectId = $row['subject_id'] ?? null;

            if ($subjectId === '' || $subjectId === '__new__') {
                $subjectId = null;
            } elseif ($subjectId !== null) {
                $subjectId = (int) $subjectId;
            }

            $name = trim((string) ($row['subject'] ?? ''));

            // A chosen subject wins; otherwise the typed name is used.
            if ($subjectId === null && $name === '') {
                return null;
            }

            $tutorId = $row['tutor_id'] ?? null;

            return [
                'subject_id' => $subjectId,
                'subject' => $subjectId === null ? $name : null,
                'tutor_id' => ($tutorId === '' || $tutorId === null) ? null : (int) $tutorId,
            ];
        }, $rows)));

        if ($rows === []) {
            $legacy = trim((string) $request->input('subject', ''));

            if ($legacy !== '') {
                $rows[] = [
                    'subject_id' => null,
                    'subject' => $legacy,
                    'tutor_id' => $leadTutorId ?: null,
                ];
            }
        }

        return $rows;
    }

    /**
     * The rows the class form opens with: old input after a validation error,
     * the class's saved subjects, or the legacy column, or one blank row.
     *
     * @return array<int, array{subject_id: mixed, subject: string, tutor_id: mixed}>
     */
    private function subjectRowsForForm(?Classes $class): array
    {
        $old = old('subjects');

        if (is_array($old) && $old !== []) {
            return array_values(array_map(function ($row) {
                return [
                    'subject_id' => is_array($row) ? ($row['subject_id'] ?? '') : '',
                    'subject' => is_array($row) ? ($row['subject'] ?? '') : '',
                    'tutor_id' => is_array($row) ? ($row['tutor_id'] ?? '') : '',
                ];
            }, $old));
        }

        if ($class) {
            $rows = $class->subjectAssignments()
                ->with('subject')
                ->orderBy('id')
                ->get()
                ->map(function (ClassSubject $row) {
                    return [
                        'subject_id' => $row->subject_id,
                        'subject' => optional($row->subject)->name ?? '',
                        'tutor_id' => $row->tutor_id,
                    ];
                })
                ->filter(function ($row) {
                    return $row['subject_id'] || $row['subject'] !== '';
                })
                ->values()
                ->all();

            if ($rows === [] && trim((string) $class->subject) !== '') {
                $rows[] = [
                    'subject_id' => '',
                    'subject' => $class->subject,
                    'tutor_id' => $class->tutor_id,
                ];
            }

            if ($rows !== []) {
                return $rows;
            }
        }

        return [['subject_id' => '', 'subject' => '', 'tutor_id' => '']];
    }

    /**
     * "Class Code should not be mandatory when Creating New Class" — a blank
     * code becomes a short, unique code built from the class name.
     */
    private function generateCode(?string $code, string $name): string
    {
        $code = trim((string) $code);

        if ($code !== '') {
            return strtoupper($code);
        }

        $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?: 'CLASS');
        $base = substr($base, 0, 6);

        do {
            $candidate = $base . '-' . strtoupper(Str::random(5));
        } while (Classes::where('code', $candidate)->exists());

        return $candidate;
    }

    public function destroy(Classes $class)
    {
        $class->delete();
        return redirect()->route('admin.classes.index')->with('success', 'Class deleted successfully.');
    }

    public function enroll(Request $request, Classes $class)
    {
        $request->validate([
            'learner_id' => 'required|exists:learners,id',
        ]);

        // Check if already enrolled
        if ($class->learners()->where('learner_id', $request->learner_id)->exists()) {
            return back()->with('error', 'Learner is already enrolled in this class.');
        }

        // Check max students — a class without a maximum is unlimited.
        if ($class->max_students !== null && $class->learners()->count() >= $class->max_students) {
            return back()->with('error', 'Class has reached maximum capacity.');
        }

        $class->learners()->attach($request->learner_id);

        return back()->with('success', 'Learner enrolled successfully.');
    }

    public function unenroll(Classes $class, Learner $learner)
    {
        $class->learners()->detach($learner->id);
        return back()->with('success', 'Learner unenrolled successfully.');
    }
}
