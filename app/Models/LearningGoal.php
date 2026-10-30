<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A learning goal a tutor sets for a learner (or a whole class) and that an
 * administrator / the CEO can share with the parent and/or the tutor.
 */
class LearningGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id', 'learner_id', 'class_id', 'title', 'description', 'term',
        'target', 'file_path', 'file_name', 'status',
        'shared_with_parent', 'shared_with_tutor', 'shared_with_learner', 'shared_by', 'shared_at',
    ];

    protected $casts = [
        'shared_with_parent' => 'boolean',
        'shared_with_tutor' => 'boolean',
        'shared_with_learner' => 'boolean',
        'shared_at' => 'datetime',
    ];

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function learner()
    {
        return $this->belongsTo(Learner::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /** Goals the parent is allowed to see. */
    public function scopeSharedWithParent($query)
    {
        return $query->where('shared_with_parent', true);
    }

    /** Goals the tutor (and admins) can see on the goals screens. */
    public function scopeSharedWithTutor($query)
    {
        return $query->where('shared_with_tutor', true);
    }

    /** Goals the learner is allowed to see on their own dashboard. */
    public function scopeSharedWithLearner($query)
    {
        return $query->where('shared_with_learner', true);
    }

    /**
     * Goals shared with this learner: their own goals plus class-wide goals for
     * a class they are enrolled in.
     */
    public function scopeVisibleToLearner($query, Learner $learner)
    {
        $classIds = $learner->classes()->pluck('classes.id');

        return $query->where('shared_with_learner', true)
            ->where(function ($query) use ($learner, $classIds) {
                $query->where('learner_id', $learner->id)
                    ->orWhere(function ($query) use ($classIds) {
                        $query->whereNull('learner_id')->whereIn('class_id', $classIds);
                    });
            });
    }

    /**
     * A goal is visible to a learner when it is shared with learners and it is
     * about that learner (or about one of their classes, for class-wide goals).
     */
    public function isVisibleToLearner(Learner $learner): bool
    {
        if (! $this->shared_with_learner) {
            return false;
        }

        if ($this->learner_id) {
            return (int) $this->learner_id === (int) $learner->id;
        }

        return $this->class_id
            && $learner->classes()->whereKey($this->class_id)->exists();
    }

    /** "Ada Obi · First Term · JSS 2 Maths" */
    public function contextLabel(): string
    {
        $parts = array_filter([
            $this->learner->user->name ?? null,
            $this->schoolClass->name ?? null,
            $this->term ?: null,
        ]);

        return $parts ? implode(' · ', $parts) : 'General goal';
    }

    public function isShared(): bool
    {
        return $this->shared_with_parent || $this->shared_with_tutor || $this->shared_with_learner;
    }
}
