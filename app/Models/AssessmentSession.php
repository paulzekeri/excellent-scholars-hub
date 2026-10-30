<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One booked assessment video call for an enquiry (note 13, phase 2).
 *
 * The school creates the Meet/Zoom link, assigns the tutor, and the guardian
 * receives the link by email/WhatsApp. Any number of sessions can exist for the
 * same child because a prospect may be assessed more than once — and may never
 * register at all.
 */
class AssessmentSession extends Model
{
    use HasFactory;

    public const PLATFORM_GOOGLE_MEET = 'google_meet';
    public const PLATFORM_ZOOM = 'zoom';
    public const PLATFORM_PHONE = 'phone';
    public const PLATFORM_IN_PERSON = 'in_person';

    public const PLATFORMS = [
        self::PLATFORM_GOOGLE_MEET => 'Google Meet',
        self::PLATFORM_ZOOM => 'Zoom',
        self::PLATFORM_PHONE => 'Phone call',
        self::PLATFORM_IN_PERSON => 'In person',
    ];

    public const PLATFORM_BADGES = [
        self::PLATFORM_GOOGLE_MEET => 'bg-blue-100 text-blue-800',
        self::PLATFORM_ZOOM => 'bg-blue-100 text-blue-800',
        self::PLATFORM_PHONE => 'bg-gray-100 text-gray-700',
        self::PLATFORM_IN_PERSON => 'bg-amber-100 text-amber-800',
    ];

    /** Platforms that need a meeting link. */
    public const ONLINE_PLATFORMS = [self::PLATFORM_GOOGLE_MEET, self::PLATFORM_ZOOM];

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_NO_SHOW => 'No show',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const STATUS_BADGES = [
        self::STATUS_SCHEDULED => 'bg-blue-100 text-blue-800',
        self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
        self::STATUS_NO_SHOW => 'bg-orange-100 text-orange-800',
        self::STATUS_CANCELLED => 'bg-gray-100 text-gray-700',
    ];

    /* Outcomes the tutor records after the call (phase 3). */
    public const OUTCOME_READY = 'ready';
    public const OUTCOME_NEEDS_SUPPORT = 'needs_support';
    public const OUTCOME_NOT_READY = 'not_ready';

    public const OUTCOMES = [
        self::OUTCOME_READY => 'Ready for the recommended class',
        self::OUTCOME_NEEDS_SUPPORT => 'Needs additional support',
        self::OUTCOME_NOT_READY => 'Not ready yet',
    ];

    public const OUTCOME_BADGES = [
        self::OUTCOME_READY => 'bg-green-100 text-green-800',
        self::OUTCOME_NEEDS_SUPPORT => 'bg-amber-100 text-amber-800',
        self::OUTCOME_NOT_READY => 'bg-orange-100 text-orange-800',
    ];

    protected $fillable = [
        'enquiry_id', 'tutor_id', 'scheduled_at', 'duration_minutes', 'platform',
        'meeting_link', 'status', 'outcome', 'recommended_class_id', 'recommended_level',
        'strengths', 'focus_areas', 'notes', 'attachment_path', 'conducted_by',
        'completed_at', 'reminder_sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function recommendedClass()
    {
        return $this->belongsTo(Classes::class, 'recommended_class_id');
    }

    public function conductedBy()
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->platform] ?? ucfirst((string) $this->platform);
    }

    public function platformBadge(): string
    {
        return self::PLATFORM_BADGES[$this->platform] ?? self::PLATFORM_BADGES[self::PLATFORM_PHONE];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? self::STATUS_BADGES[self::STATUS_CANCELLED];
    }

    public function outcomeLabel(): string
    {
        return self::OUTCOMES[$this->outcome] ?? 'Not recorded';
    }

    public function outcomeBadge(): string
    {
        return self::OUTCOME_BADGES[$this->outcome] ?? self::STATUS_BADGES[self::STATUS_CANCELLED];
    }

    /** A result can be entered/edited until the call is cancelled or marked a no-show. */
    public function canRecordResult(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_COMPLETED], true);
    }

    public function isOnline(): bool
    {
        return in_array($this->platform, self::ONLINE_PLATFORMS, true);
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isToday(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isToday();
    }

    public function isUpcoming(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    /** "Mon 3 Nov 2026, 4:00 pm – 4:30 pm" */
    public function scheduleLabel(): string
    {
        if (! $this->scheduled_at) {
            return 'Not scheduled';
        }

        $end = $this->scheduled_at->copy()->addMinutes((int) $this->duration_minutes);

        return $this->scheduled_at->format('D j M Y, g:i a') . ' – ' . $end->format('g:i a');
    }

    /** A link the school/tutor can join from, when this is an online platform. */
    public function joinUrl(): ?string
    {
        return $this->isOnline() ? $this->meeting_link : null;
    }
}
