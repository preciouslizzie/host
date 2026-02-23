<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerAvailability extends Model
{
    protected $table = 'volunteer_availability';

    protected $fillable = [
        'user_id',
        'day',
        'start_time',
        'end_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
