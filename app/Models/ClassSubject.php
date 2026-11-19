<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One subject taught in one class, and the tutor who takes it.
 *
 * This is the row behind "Test Class WAEC → Mathematics → John Tutor". Because
 * it is a normal Eloquent model, a subject teacher can be shown, reassigned or
 * removed without touching the class itself.
 */
class ClassSubject extends Model
{
    use HasFactory;

    protected $table = 'class_subject';

    protected $fillable = ['class_id', 'subject_id', 'tutor_id'];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    /** Sessions scheduled for this subject of the class. */
    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'class_subject_id');
    }

    /** The teacher's name, or a clear label when nobody is assigned yet. */
    public function teacherName(): string
    {
        return optional(optional($this->tutor)->user)->name ?: 'Not assigned yet';
    }
}
