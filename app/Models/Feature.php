<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }
}
