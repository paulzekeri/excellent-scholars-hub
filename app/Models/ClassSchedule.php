<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'tutor_id', 'title', 'description', 'start_time', 'end_time',
        'meeting_link', 'meeting_platform', 'status', 'completed_at', 'completed_by', 'recording_url',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'completed_at' => 'datetime',
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

    /** Who ticked this session as completed (administrator, CEO or the tutor). */
    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /** A session can only be ticked once its scheduled end time has passed. */
    public function hasEnded(): bool
    {
        return $this->end_time !== null && now()->greaterThanOrEqualTo($this->end_time);
    }

    /** Sessions that count towards a tutor's monthly teaching report. */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
