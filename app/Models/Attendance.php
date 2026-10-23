<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'class_schedule_id', 'learner_id', 'status', 'notes', 'marked_at',
    ];

    protected $casts = ['marked_at' => 'datetime'];

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function learner()
    {
        return $this->belongsTo(Learner::class);
    }
}
