<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'transaction_id',
        'amount',
        'status',
        'payment_method',
        'paid_at',
        'due_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
