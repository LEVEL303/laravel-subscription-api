<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'gateway_id',
        'status',
        'locked_price',
        'started_at',
        'ends_at',
        'trial_ends_at',
        'auto_renew',
    ];
}
