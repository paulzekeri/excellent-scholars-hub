<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'tutor_id', 'title', 'description', 'file_path', 'images',
        'due_date', 'total_points', 'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'images' => 'array',
    ];

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

    /**
     * Pictures the tutor attached to the assignment (public-disk paths).
     *
     * @return array<int, string>
     */
    public function imagePaths(): array
    {
        return is_array($this->images) ? array_values(array_filter($this->images)) : [];
    }

    public function hasImages(): bool
    {
        return $this->imagePaths() !== [];
    }
}
