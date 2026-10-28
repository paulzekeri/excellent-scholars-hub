<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name', 'code', 'description', 'tutor_id', 'grade_level', 'subject', 'max_students', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_students' => 'integer',
    ];

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
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
}
