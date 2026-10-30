<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /** Types an administrator can pick when composing a notification. */
    public const TYPE_INFO = 'info';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_ALERT = 'alert';

    /** System generated type used when someone sends a direct message. */
    public const TYPE_MESSAGE = 'message';

    /**
     * Composable types in display order.
     */
    public const COMPOSABLE_TYPES = [
        self::TYPE_INFO,
        self::TYPE_REMINDER,
        self::TYPE_ALERT,
    ];

    /**
     * Label, guidance and presentation for every notification type.
     *
     * The badge/accent/icon_class values are literal Tailwind and Font Awesome
     * classes so they end up in the rendered markup the Tailwind CDN scans.
     */
    public const TYPE_META = [
        self::TYPE_INFO => [
            'label' => 'Information',
            'description' => 'General news the recipient should know. No action required.',
            'icon' => 'fa-info-circle',
            'badge' => 'bg-blue-100 text-blue-800',
            'accent' => 'border-blue-500',
            'icon_class' => 'text-blue-600',
        ],
        self::TYPE_REMINDER => [
            'label' => 'Reminder',
            'description' => 'A nudge about something still to be done, usually with a deadline.',
            'icon' => 'fa-clock',
            'badge' => 'bg-sun-light text-sun-ink',
            'accent' => 'border-sun',
            'icon_class' => 'text-sun-ink',
        ],
        self::TYPE_ALERT => [
            'label' => 'Alert',
            'description' => 'Urgent or exceptional news that needs immediate attention.',
            'icon' => 'fa-exclamation-triangle',
            'badge' => 'bg-orange-100 text-orange-800',
            'accent' => 'border-orange-500',
            'icon_class' => 'text-orange-600',
        ],
        self::TYPE_MESSAGE => [
            'label' => 'Message',
            'description' => 'Created automatically when someone sends a direct message.',
            'icon' => 'fa-comment',
            'badge' => 'bg-gray-100 text-gray-800',
            'accent' => 'border-gray-400',
            'icon_class' => 'text-gray-500',
        ],
    ];

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'channel',
        'is_read', 'read_at', 'status', 'sent_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Presentation metadata for a type, falling back to Information for
     * unknown values so old or hand-written rows still render.
     */
    public static function metaFor(?string $type): array
    {
        return self::TYPE_META[$type] ?? self::TYPE_META[self::TYPE_INFO];
    }

    /**
     * Presentation metadata for this notification's type.
     */
    public function typeMeta(): array
    {
        return self::metaFor($this->type);
    }

    /**
     * Human readable label for this notification's type.
     */
    public function typeLabel(): string
    {
        return $this->typeMeta()['label'];
    }
}
