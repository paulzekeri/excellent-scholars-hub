<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'tutor_id', 'title', 'description', 'start_time', 'end_time',
        'meeting_link', 'meeting_platform', 'status', 'recording_url',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }
}
