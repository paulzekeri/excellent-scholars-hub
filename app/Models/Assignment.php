<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'tutor_id', 'title', 'description', 'file_path',
        'due_date', 'total_points', 'status',
    ];

    protected $casts = ['due_date' => 'datetime'];

    public function class()
    {
        return $this->belongsTo(Classes::class);
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
