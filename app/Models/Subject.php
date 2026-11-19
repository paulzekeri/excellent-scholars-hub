<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A subject in the school's catalogue, e.g. Mathematics or Biology.
 *
 * A subject is shared by any number of classes; the class_subject pivot holds
 * the class/subject/teacher combination (see ClassSubject).
 */
class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** One row per class that teaches this subject. */
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_subject', 'subject_id', 'class_id')
            ->withPivot('tutor_id')
            ->withTimestamps();
    }

    /** Teachers who take this subject, across every class. */
    public function teachers()
    {
        return $this->belongsToMany(Tutor::class, 'class_subject', 'subject_id', 'tutor_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
