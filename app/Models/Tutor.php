<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'phone', 'qualification', 'bio', 'specialization', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Classes where this tutor is the lead tutor/class coordinator. */
    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    /** Subject rows this tutor teaches (a tutor can hold several). */
    public function subjectAssignments()
    {
        return $this->hasMany(ClassSubject::class, 'tutor_id');
    }

    /** Classes this tutor takes at least one subject in. */
    public function taughtClasses()
    {
        return $this->belongsToMany(Classes::class, 'class_subject', 'tutor_id', 'class_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function materials()
    {
        return $this->hasMany(LearningMaterial::class);
    }
}
