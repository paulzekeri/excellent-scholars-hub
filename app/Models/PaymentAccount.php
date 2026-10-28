<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A school bank account that fees are paid into.
 *
 * The CEO adds the accounts once; parents see the active ones on the payment
 * form and pick the one they transferred into. Disabling an account hides it
 * from parents without touching the payments already recorded against it, and
 * deleting one keeps `payments.paid_into` as the historical snapshot.
 */
class PaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name', 'account_name', 'account_number', 'instructions', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /** Accounts parents are allowed to pay into. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Active accounts first, then bank name - the order both screens use. */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_active')->orderBy('bank_name')->orderBy('id');
    }

    /** "GTBank · 0123456789" - the snapshot saved on each payment. */
    public function displayLabel(): string
    {
        return trim($this->bank_name . ' · ' . $this->account_number);
    }

    /** "GTBank · 0123456789 · Excellent Scholars Academy" for CEO lists. */
    public function fullLabel(): string
    {
        return $this->displayLabel() . ($this->account_name ? ' · ' . $this->account_name : '');
    }
}
