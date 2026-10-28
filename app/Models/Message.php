<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /** Audiences an administrator can broadcast to. */
    public const AUDIENCE_PARENTS = 'parents';
    public const AUDIENCE_TUTORS = 'tutors';
    public const AUDIENCE_LEARNERS = 'learners';
    public const AUDIENCE_EVERYONE = 'everyone';
    public const AUDIENCE_CLASS = 'class';

    public const AUDIENCES = [
        self::AUDIENCE_PARENTS => 'Parents / Guardians',
        self::AUDIENCE_TUTORS => 'Tutors',
        self::AUDIENCE_LEARNERS => 'Students',
        self::AUDIENCE_EVERYONE => 'Everyone',
        self::AUDIENCE_CLASS => 'A single class',
    ];

    protected $fillable = [
        'sender_id', 'receiver_id', 'body', 'audience', 'class_id', 'batch',
        'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /** Human label for the broadcast category, if any. */
    public function audienceLabel(): ?string
    {
        if (! $this->audience) {
            return null;
        }

        $label = self::AUDIENCES[$this->audience] ?? ucfirst($this->audience);

        if ($this->audience === self::AUDIENCE_CLASS && $this->schoolClass) {
            $label .= ': ' . $this->schoolClass->name;
        }

        return $label;
    }

    /**
     * Limit the query to the conversation between two users.
     */
    public function scopeBetween($query, $firstUserId, $secondUserId)
    {
        return $query->where(function ($query) use ($firstUserId, $secondUserId) {
            $query->where(function ($query) use ($firstUserId, $secondUserId) {
                $query->where('sender_id', $firstUserId)
                    ->where('receiver_id', $secondUserId);
            })->orWhere(function ($query) use ($firstUserId, $secondUserId) {
                $query->where('sender_id', $secondUserId)
                    ->where('receiver_id', $firstUserId);
            });
        });
    }
}
