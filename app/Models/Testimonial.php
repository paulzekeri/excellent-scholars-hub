<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A member's testimonial (note 10). It stays pending until the CEO approves it;
 * only approved testimonials appear on the landing page, and the profile photo
 * is published only when the member ticked that option.
 */
class Testimonial extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Waiting for the CEO',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    public const STATUS_BADGES = [
        self::STATUS_PENDING => 'bg-amber-100 text-amber-800',
        self::STATUS_APPROVED => 'bg-green-100 text-green-800',
        self::STATUS_REJECTED => 'bg-orange-100 text-orange-800',
    ];

    protected $fillable = [
        'user_id', 'body', 'publish_photo', 'status',
        'approved_by', 'approved_at', 'review_note',
    ];

    protected $casts = [
        'publish_photo' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? self::STATUS_BADGES[self::STATUS_PENDING];
    }

    /** "Parent", "Tutor", "Learner", "Administrator" or "CEO". */
    public function roleLabel(): string
    {
        $slug = optional(optional($this->user)->role)->slug;

        $labels = [
            'parent' => 'Parent',
            'tutor' => 'Tutor',
            'learner' => 'Learner',
            'admin' => 'Administrator',
            'ceo' => 'CEO',
        ];

        return $labels[$slug] ?? 'Member';
    }

    /** The profile photo URL, but only when the member allowed it. */
    public function photoUrl(): ?string
    {
        if (! $this->publish_photo) {
            return null;
        }

        return optional($this->user)->photoUrl();
    }
}
