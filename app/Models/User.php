<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'must_change_password', 'password_changed_at',
        'role_id', 'phone', 'photo_path', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    /**
     * Usernames that would be confusing, or could impersonate the school.
     * Shared by the validators and the backfill command.
     */
    public const RESERVED_USERNAMES = [
        'admin', 'administrator', 'ceo', 'owner', 'support', 'help', 'info',
        'school', 'esh', 'root', 'system', 'login', 'logout', 'api', 'staff',
        'tutor', 'teacher', 'learner', 'student', 'parent',
    ];

    /**
     * Where account details and password resets should be delivered: the user's
     * own address when they have one, otherwise their guardian's.
     *
     * This is the single implementation of the client's "own email first,
     * parent/guardian otherwise" rule.
     */
    public function deliveryEmail(): ?string
    {
        if ($this->email) {
            return $this->email;
        }

        return optional(optional(optional($this->learner)->parent)->user)->email;
    }

    /** True when the school set a temporary password for this account. */
    public function needsPasswordChange(): bool
    {
        return (bool) $this->must_change_password;
    }

    /**
     * Build a free username from a display name ("Tolu Okafor" → "tolu_okafor",
     * then tolu_okafor_2, …), skipping reserved words.
     */
    public static function suggestUsername(string $name): string
    {
        // Some imported accounts store an email address in the name field.
        $source = Str::contains($name, '@') ? Str::before($name, '@') : $name;

        $base = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($source)), '_');
        $base = substr($base ?: 'user', 0, 25);

        $candidate = $base;
        $suffix = 1;

        while (static::where('username', $candidate)->exists()
            || in_array($candidate, self::RESERVED_USERNAMES, true)) {
            $suffix++;
            $candidate = $base . '_' . $suffix;
        }

        return $candidate;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function tutor()
    {
        return $this->hasOne(Tutor::class);
    }

    public function learner()
    {
        return $this->hasOne(Learner::class);
    }

    public function parent()
    {
        return $this->hasOne(ParentModel::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /** The member's landing-page testimonial (one per user). */
    public function testimonial()
    {
        return $this->hasOne(Testimonial::class);
    }

    /** Public URL of the uploaded profile photo, if there is one. */
    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo_path);
    }

    /** One or two letters for an avatar when no photo exists. */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $letters = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $letters .= mb_substr($word, 0, 1);
        }

        return mb_strtoupper($letters ?: 'U');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function hasRole($slug)
    {
        return $this->role && $this->role->slug === $slug;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isCeo()
    {
        return $this->hasRole('ceo');
    }

    /**
     * The CEO works inside the admin area, so both roles share admin access.
     */
    public function isAdministrator()
    {
        return $this->isAdmin() || $this->isCeo();
    }

    /**
     * Only the CEO may view or record payments.
     */
    public function canManagePayments()
    {
        return $this->isCeo();
    }

    public function isTutor()
    {
        return $this->hasRole('tutor');
    }

    public function isLearner()
    {
        return $this->hasRole('learner');
    }

    public function isParent()
    {
        return $this->hasRole('parent');
    }

    public function unreadMessagesCount()
    {
        return $this->receivedMessages()->where('is_read', false)->count();
    }
}
