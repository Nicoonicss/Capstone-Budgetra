<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attraction extends Model
{
    protected $fillable = [
        'name', 'destination', 'category', 'image', 'rating', 'description',
    ];

    protected function casts(): array
    {
        return ['rating' => 'decimal:1'];
    }
}
