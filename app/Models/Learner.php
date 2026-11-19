<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Learner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date_of_birth', 'phone', 'address', 'grade_level', 'grade_id', 'parent_id', 'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The grade catalogue entry behind `grade_level`. */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_learner', 'learner_id', 'class_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
