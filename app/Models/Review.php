<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'attraction_id', 'destination', 'rating', 'body', 'status'];

    public function user()       { return $this->belongsTo(User::class); }
    public function attraction() { return $this->belongsTo(Attraction::class); }
}
