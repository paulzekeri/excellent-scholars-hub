<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A learning goal a tutor writes for the school and that an administrator / the
 * CEO can share with individual parents, learners and tutors (or with everyone
 * the goal is about through the legacy shared_with_* switches).
 */
class LearningGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id', 'learner_id', 'class_id', 'title', 'description', 'term',
        'target', 'file_path', 'file_name', 'status',
        'shared_with_parent', 'shared_with_tutor', 'shared_with_learner', 'shared_by', 'shared_at',
        'share_all_parent', 'share_all_learner', 'share_all_tutor',
    ];

    protected $casts = [
        'shared_with_parent' => 'boolean',
        'shared_with_tutor' => 'boolean',
        'shared_with_learner' => 'boolean',
        'share_all_parent' => 'boolean',
        'share_all_learner' => 'boolean',
        'share_all_tutor' => 'boolean',
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

    /**
     * The individual people this goal was shared with.
     */
    public function recipients()
    {
        return $this->belongsToMany(User::class, 'learning_goal_shares')
            ->withPivot('role_slug', 'shared_by')
            ->withTimestamps();
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
     * True only when the school ticked "every parent / learner / tutor the goal
     * is about" for that role. Picking people by name does not set this: those
     * names live in the shares table (see `recipients()`).
     */
    public function sharesWithAll(string $roleSlug): bool
    {
        return (bool) $this->getAttribute('share_all_' . $roleSlug);
    }

    /**
     * Goals shared with this learner: their own goals, class-wide goals for a
     * class they are enrolled in, and anything shared with them by name.
     */
    public function scopeVisibleToLearner($query, Learner $learner)
    {
        $classIds = $learner->classes()->pluck('classes.id');

        return $query->where(function ($query) use ($learner, $classIds) {
            $query->where(function ($legacy) use ($learner, $classIds) {
                $legacy->where('shared_with_learner', true)
                    ->where(function ($scope) use ($learner, $classIds) {
                        $scope->where('learner_id', $learner->id)
                            ->orWhere(function ($inner) use ($classIds) {
                                $inner->whereNull('learner_id')->whereIn('class_id', $classIds);
                            });
                    });
            })->orWhereHas('recipients', function ($share) use ($learner) {
                $share->where('user_id', $learner->user_id);
            });
        });
    }

    /**
     * A goal is visible to a learner when it is shared with learners and it is
     * about that learner (or one of their classes), or when it was shared with
     * this learner by name.
     */
    public function isVisibleToLearner(Learner $learner): bool
    {
        if ($this->isSharedWithUser($learner->user_id)) {
            return true;
        }

        if (! $this->shared_with_learner) {
            return false;
        }

        if ($this->learner_id) {
            return (int) $this->learner_id === (int) $learner->id;
        }

        return $this->class_id
            && $learner->classes()->whereKey($this->class_id)->exists();
    }

    /** Was this goal shared with one individual person? */
    public function isSharedWithUser(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        if ($this->relationLoaded('recipients')) {
            return $this->recipients->contains('id', $userId);
        }

        return $this->recipients()->where('user_id', $userId)->exists();
    }

    /**
     * The long goal text. Older goals only have a title, so it doubles as the
     * body: this keeps every existing goal readable.
     */
    public function goalText(): string
    {
        return trim((string) ($this->title ?: $this->description));
    }

    /** A short version for tables: the first characters, then an ellipsis. */
    public function goalExcerpt(int $length = 160): string
    {
        return \Illuminate\Support\Str::limit($this->goalText(), $length);
    }

    /** The people this goal was shared with, grouped by their role. */
    public function recipientsByRole(): array
    {
        $recipients = $this->relationLoaded('recipients')
            ? $this->recipients
            : $this->recipients()->get();

        return $recipients->groupBy(function (User $user) {
            return optional($user->pivot)->role_slug ?: 'user';
        })->all();
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
        if ($this->shared_with_parent || $this->shared_with_tutor || $this->shared_with_learner) {
            return true;
        }

        return $this->recipients()->exists();
    }
}
