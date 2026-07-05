<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'full_name', 'email', 'password', 'phone', 'country',
        'currency_code', 'currency_symbol', 'role', 'profile_photo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
        ];
    }

    public function trips()       { return $this->hasMany(Trip::class); }
    public function expenses()    { return $this->hasMany(Expense::class); }
    public function savingsGoals(){ return $this->hasMany(SavingsGoal::class); }
    public function notifications(){ return $this->hasMany(Notification::class); }
    public function reviews()     { return $this->hasMany(Review::class); }
    public function ocrLogs()     { return $this->hasMany(OcrLog::class); }
}
