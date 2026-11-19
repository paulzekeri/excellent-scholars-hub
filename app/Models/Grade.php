<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A grade / class level in the school's catalogue, e.g. "KG1 - Pre Primary".
 *
 * The name is free text: the office types the full label they want families to
 * see. The legacy `grade_level` / `child_grade_level` columns are kept equal to
 * it so older screens and reports continue to work.
 */
class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** The label shown to families (the name itself). */
    public function label(): string
    {
        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Sort order first, then name — shared by every grade dropdown. */
    public function scopeOrdered($query)
    {
        return $query->orderByRaw('COALESCE(sort_order, 999999)')->orderBy('name');
    }

    public function learners()
    {
        return $this->hasMany(Learner::class);
    }

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }
}
