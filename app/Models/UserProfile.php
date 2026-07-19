<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = ['user_id', 'home_city', 'daily_budget', 'interests', 'sub_interests'];

    protected $casts = [
        'interests'     => 'array',
        'sub_interests' => 'array',
        'daily_budget'  => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
