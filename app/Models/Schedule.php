<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\VolunteerRole;
use App\Models\AttendanceLog;

class Schedule extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'date',
        'scheduled_date',
        'start_time',
        'end_time',
        'location'
    ];

    // public function volunteer()
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    // }
     
    public function user() 
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(VolunteerRole::class, 'role_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
