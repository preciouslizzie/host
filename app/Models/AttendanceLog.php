<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'user_id',
        'hours_worked',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
