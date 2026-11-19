<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name', 'code', 'description', 'tutor_id', 'grade_level', 'grade_id', 'subject', 'max_students', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_students' => 'integer',
    ];

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    /** The grade catalogue entry behind `grade_level`. */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function learners()
    {
        return $this->belongsToMany(Learner::class, 'class_learner', 'class_id', 'learner_id');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }

    public function materials()
    {
        return $this->hasMany(LearningMaterial::class, 'class_id');
    }

    public function learningGoals()
    {
        return $this->hasMany(LearningGoal::class, 'class_id');
    }

    /** External Google Drive / video / PDF links shown under Class Library. */
    public function libraryResources()
    {
        return $this->hasMany(ClassLibraryResource::class, 'class_id');
    }

    /**
     * The subjects this class teaches, one row each, with the tutor who takes
     * that subject. See ClassSubject.
     */
    public function subjectAssignments()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    /** The subject catalogue entries attached to this class. */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
            ->withPivot('tutor_id')
            ->withTimestamps();
    }

    /**
     * "Mathematics, Physics, English" — the readable list used on lists and
     * cards. Falls back to the original single `subject` column for classes
     * that have not been given subject rows yet.
     */
    public function subjectSummary(string $fallback = 'General'): string
    {
        if ($this->relationLoaded('subjectAssignments')) {
            $names = $this->subjectAssignments
                ->map(function (ClassSubject $row) {
                    return optional($row->subject)->name;
                })
                ->filter()
                ->unique()
                ->values();
        } else {
            $names = $this->subjects()->orderBy('subjects.name')->pluck('subjects.name')->unique()->values();
        }

        if ($names->isNotEmpty()) {
            return $names->implode(', ');
        }

        return $this->subject ?: $fallback;
    }

    /**
     * Subject/teacher pairs for this class, in the same order as
     * subjectSummary(). A null teacher means "not assigned yet".
     *
     * @return array<int, array{subject: string, teacher: ?string}>
     */
    public function subjectTeacherRows(): array
    {
        $rows = $this->relationLoaded('subjectAssignments')
            ? $this->subjectAssignments
            : $this->subjectAssignments()->with(['subject', 'tutor.user'])->orderBy('id')->get();

        return $rows
            ->filter(function (ClassSubject $row) {
                return $row->subject !== null;
            })
            ->map(function (ClassSubject $row) {
                return [
                    'subject' => $row->subject->name,
                    'teacher' => optional(optional($row->tutor)->user)->name,
                ];
            })
            ->values()
            ->all();
    }

    /** Every tutor who teaches this class: the lead tutor plus subject tutors. */
    public function teacherIds(): array
    {
        $ids = $this->subjectAssignments()->whereNotNull('tutor_id')->pluck('tutor_id')->all();

        if ($this->tutor_id) {
            array_unshift($ids, $this->tutor_id);
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * True when the tutor leads the class or takes at least one of its
     * subjects. Used instead of comparing `tutor_id` directly, so a Physics
     * teacher can open a class whose lead tutor is the Maths teacher.
     */
    public function isTaughtBy($tutorId): bool
    {
        $tutorId = (int) $tutorId;

        if (! $tutorId) {
            return false;
        }

        if ((int) $this->tutor_id === $tutorId) {
            return true;
        }

        return $this->subjectAssignments()->where('tutor_id', $tutorId)->exists();
    }

    /** Classes the tutor leads or takes a subject in. */
    public function scopeTaughtBy($query, $tutorId)
    {
        $tutorId = (int) $tutorId;

        return $query->where(function ($query) use ($tutorId) {
            $query->where('tutor_id', $tutorId)
                ->orWhereHas('subjectAssignments', function ($subjects) use ($tutorId) {
                    $subjects->where('tutor_id', $tutorId);
                });
        });
    }

    /** @return array<int, int> */
    public static function idsTaughtBy($tutorId): array
    {
        return static::query()
            ->taughtBy($tutorId)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
    }

    /**
     * A class without a maximum is unlimited, so enrolment is always allowed.
     */
    public function hasCapacity(): bool
    {
        if ($this->max_students === null) {
            return true;
        }

        return $this->learners()->count() < $this->max_students;
    }

    /** "18 / 30" or "18 enrolled · no limit". */
    public function capacityLabel(): string
    {
        $enrolled = $this->learners()->count();

        return $this->max_students === null
            ? $enrolled . ' enrolled · no limit'
            : $enrolled . ' / ' . $this->max_students;
    }

    /**
     * Replace the class's subject rows with the given list.
     *
     * A row may point at a catalogue subject (`subject_id`), carry a new subject
     * name (`subject`), or be a plain string name. Blank rows are ignored and
     * duplicates collapse, so a mistyped double row is harmless. The old
     * `subject` column is kept equal to the first row so existing screens and
     * reports that still read it continue to work.
     *
     * @param  array<int, array{subject_id?: mixed, subject?: string, name?: string, tutor_id?: mixed}>|array<int, string>  $rows
     */
    public function syncSubjectAssignments(array $rows): void
    {
        $normalised = collect($rows)
            ->map(function ($row) {
                if (is_string($row)) {
                    $row = ['subject' => $row];
                }

                if (! is_array($row)) {
                    return null;
                }

                $subjectId = $row['subject_id'] ?? null;

                if ($subjectId === '' || $subjectId === '__new__') {
                    $subjectId = null;
                } elseif ($subjectId !== null) {
                    $subjectId = (int) $subjectId;
                }

                $name = trim((string) ($row['subject'] ?? $row['name'] ?? ''));

                if ($subjectId === null && $name === '') {
                    return null;
                }

                $tutorId = $row['tutor_id'] ?? null;

                return [
                    'subject_id' => $subjectId,
                    'subject' => $name,
                    'tutor_id' => ($tutorId === '' || $tutorId === null) ? null : (int) $tutorId,
                ];
            })
            ->filter()
            ->unique(function ($row) {
                return $row['subject_id'] !== null
                    ? 'id:' . $row['subject_id']
                    : 'name:' . mb_strtolower($row['subject']);
            })
            ->values();

        $keptSubjectIds = [];
        $firstSubjectName = null;

        foreach ($normalised as $row) {
            if ($row['subject_id'] !== null) {
                $subject = Subject::find($row['subject_id']);

                // The subject was deleted between opening the form and saving.
                if (! $subject) {
                    continue;
                }
            } else {
                $subject = Subject::firstOrCreate(['name' => $row['subject']]);
            }

            $this->subjectAssignments()->updateOrCreate(
                ['subject_id' => $subject->id],
                ['tutor_id' => $row['tutor_id']]
            );

            $keptSubjectIds[] = $subject->id;

            if ($firstSubjectName === null) {
                $firstSubjectName = $subject->name;
            }
        }

        // whereNotIn with an empty list removes every row, which is correct when
        // the class has no subjects left.
        $this->subjectAssignments()->whereNotIn('subject_id', $keptSubjectIds)->delete();

        $this->forceFill(['subject' => $firstSubjectName])->save();

        $this->unsetRelation('subjectAssignments');
        $this->unsetRelation('subjects');
    }
}
