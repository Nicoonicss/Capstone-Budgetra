<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'destination', 'start_date', 'end_date',
        'num_travelers', 'budget_limit', 'travel_type', 'notes',
        'cover_image', 'total_cost', 'summary_data', 'origin', 'origin_code', 'destination_code',
    ];

    protected function casts(): array
    {
        return [
            'start_date'    => 'date',
            'end_date'      => 'date',
            'budget_limit'  => 'decimal:2',
            'summary_data'  => 'array',
        ];
    }

    public function user()        { return $this->belongsTo(User::class); }
    public function budgets()     { return $this->hasMany(TripBudget::class); }
    public function expenses()    { return $this->hasMany(Expense::class); }
    public function itinerary()   { return $this->hasMany(Itinerary::class); }
    public function savingsGoals(){ return $this->hasMany(SavingsGoal::class); }
}
