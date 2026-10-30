<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A motivation post written by the school (note 8). The most recently published
 * active post is shown on every dashboard; the built-in rotation is only a
 * fallback when the school has not posted anything.
 */
class Affirmation extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'author_id', 'is_active', 'published_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNotNull('published_at');
    }

    /** The post shown on the dashboards right now, if the school wrote one. */
    public static function current(): ?self
    {
        return static::active()->latest('published_at')->first();
    }
}
