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

    protected $casts = ['is_active' => 'boolean'];

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

}
