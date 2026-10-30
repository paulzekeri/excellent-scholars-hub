<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One entry in an enquiry's conversation history — a typed note, a logged call
 * or WhatsApp message, or an automatic status change (note 13).
 */
class EnquiryNote extends Model
{
    use HasFactory;

    public const TYPE_NOTE = 'note';
    public const TYPE_CALL = 'call';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_STATUS = 'status';

    /** Types a person can add by hand (status entries are automatic). */
    public const MANUAL_TYPES = [
        self::TYPE_NOTE => 'Note',
        self::TYPE_CALL => 'Phone call',
        self::TYPE_WHATSAPP => 'WhatsApp message',
    ];

    public const TYPES = self::MANUAL_TYPES + [
        self::TYPE_STATUS => 'Status change',
    ];

    public const TYPE_BADGES = [
        self::TYPE_NOTE => 'bg-gray-100 text-gray-700',
        self::TYPE_CALL => 'bg-blue-100 text-blue-800',
        self::TYPE_WHATSAPP => 'bg-green-100 text-green-800',
        self::TYPE_STATUS => 'bg-amber-100 text-amber-800',
    ];

    protected $fillable = ['enquiry_id', 'user_id', 'type', 'body'];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function typeBadge(): string
    {
        return self::TYPE_BADGES[$this->type] ?? self::TYPE_BADGES[self::TYPE_NOTE];
    }
}
