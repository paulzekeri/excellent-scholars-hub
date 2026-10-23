<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'learner_id', 'payment_type', 'amount', 'currency',
        'payment_method', 'reference', 'status', 'notes', 'payment_date',
    ];

    protected $casts = ['payment_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function learner()
    {
        return $this->belongsTo(Learner::class);
    }
}
