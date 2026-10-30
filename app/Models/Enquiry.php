<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A prospective learner, captured before any user/learner/parent row exists
 * (note 13). Phase 1 covers capture, the pipeline list, the detail screen and
 * the timeline; booking an assessment arrives in phase 2.
 */
class Enquiry extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Where the enquiry came from
    |--------------------------------------------------------------------------
    */
    public const SOURCE_WHATSAPP = 'whatsapp';
    public const SOURCE_PHONE = 'phone';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_WALK_IN = 'walk_in';
    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_INSTAGRAM = 'instagram';
    public const SOURCE_OTHER = 'other';

    public const SOURCES = [
        self::SOURCE_WHATSAPP => 'WhatsApp',
        self::SOURCE_PHONE => 'Phone call',
        self::SOURCE_REFERRAL => 'Referral',
        self::SOURCE_WALK_IN => 'Walk-in',
        self::SOURCE_WEBSITE => 'Website',
        self::SOURCE_INSTAGRAM => 'Instagram',
        self::SOURCE_OTHER => 'Other',
    ];

    /*
    |--------------------------------------------------------------------------
    | Pipeline statuses
    |--------------------------------------------------------------------------
    | Phase 1 can only move new → contacted → declined/archived. The assessment
    | states are declared now so the stored values never change, but their
    | transitions are enabled with the booking/result screens (phase 2/3).
    */
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_ASSESSMENT_BOOKED = 'assessment_booked';
    public const STATUS_ASSESSED = 'assessed';
    public const STATUS_PLACED = 'placed';
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONTACTED => 'Contacted',
        self::STATUS_ASSESSMENT_BOOKED => 'Assessment booked',
        self::STATUS_ASSESSED => 'Assessed',
        self::STATUS_PLACED => 'Awaiting enrolment',
        self::STATUS_ENROLLED => 'Enrolled',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    /** Tailwind chip classes per status (literal so the CDN build keeps them). */
    public const STATUS_BADGES = [
        self::STATUS_NEW => 'bg-blue-100 text-blue-800',
        self::STATUS_CONTACTED => 'bg-amber-100 text-amber-800',
        self::STATUS_ASSESSMENT_BOOKED => 'bg-purple-100 text-purple-800',
        self::STATUS_ASSESSED => 'bg-teal-100 text-teal-800',
        self::STATUS_PLACED => 'bg-indigo-100 text-indigo-800',
        self::STATUS_ENROLLED => 'bg-green-100 text-green-800',
        self::STATUS_DECLINED => 'bg-red-100 text-red-800',
        self::STATUS_ARCHIVED => 'bg-gray-100 text-gray-700',
    ];

    /** Open statuses shown by the default "Open" tab. */
    public const OPEN_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_ASSESSMENT_BOOKED,
        self::STATUS_ASSESSED,
        self::STATUS_PLACED,
    ];

    /**
     * Allowed moves. Assessment/enrolment targets stay closed until the screens
     * that produce them exist, so the pipeline cannot jump a step.
     */
    public const STATUS_FLOW = [
        self::STATUS_NEW => [self::STATUS_CONTACTED, self::STATUS_DECLINED, self::STATUS_ARCHIVED],
        self::STATUS_CONTACTED => [self::STATUS_DECLINED, self::STATUS_ARCHIVED],
        self::STATUS_ASSESSMENT_BOOKED => [self::STATUS_ARCHIVED],
        self::STATUS_ASSESSED => [self::STATUS_ARCHIVED],
        self::STATUS_PLACED => [self::STATUS_ARCHIVED],
        self::STATUS_ENROLLED => [self::STATUS_ARCHIVED],
        self::STATUS_DECLINED => [self::STATUS_ARCHIVED],
        self::STATUS_ARCHIVED => [],
    ];

    protected $fillable = [
        'reference', 'child_name', 'child_dob', 'child_grade_level',
        'guardian_name', 'guardian_phone', 'guardian_email', 'preferred_at',
        'source', 'status', 'assigned_to', 'created_by',
        'converted_learner_id', 'converted_at', 'declined_reason', 'notes',
    ];

    protected $casts = [
        'child_dob' => 'date',
        'preferred_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    protected static function booted()
    {
        // "ENQ-2026-0001" — generated once the id exists, on every creation path.
        static::created(function (Enquiry $enquiry) {
            if (! $enquiry->reference) {
                $year = $enquiry->created_at ? $enquiry->created_at->format('Y') : now()->format('Y');

                $enquiry->forceFill([
                    'reference' => 'ENQ-' . $year . '-' . str_pad((string) $enquiry->id, 4, '0', STR_PAD_LEFT),
                ])->save();
            }
        });
    }

    /** The conversation history for this enquiry. */
    public function timeline()
    {
        return $this->hasMany(EnquiryNote::class)->latest();
    }

    /** Every assessment booked for this child. */
    public function assessments()
    {
        return $this->hasMany(AssessmentSession::class)->orderBy('scheduled_at');
    }

    /** The most recent assessment session (if any). */
    public function latestAssessment()
    {
        return $this->hasOne(AssessmentSession::class)->latestOfMany();
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedLearner()
    {
        return $this->belongsTo(Learner::class, 'converted_learner_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? self::STATUS_BADGES[self::STATUS_ARCHIVED];
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? ucfirst((string) $this->source);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function canMoveTo(string $status): bool
    {
        return in_array($status, self::STATUS_FLOW[$this->status] ?? [], true);
    }

    /**
     * The statuses this enquiry can move to now, as [value => label].
     *
     * @return array<string, string>
     */
    public function nextStatuses(): array
    {
        $next = [];

        foreach (self::STATUS_FLOW[$this->status] ?? [] as $status) {
            $next[$status] = self::STATUSES[$status];
        }

        return $next;
    }
}
