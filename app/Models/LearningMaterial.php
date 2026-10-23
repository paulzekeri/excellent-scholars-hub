<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'tutor_id', 'title', 'description',
        'file_path', 'file_type', 'file_size', 'status',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class);
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }
}
