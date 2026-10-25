<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'phone', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

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
