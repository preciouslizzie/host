<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerRole extends Model
{
    protected $fillable = [
        'name',
        'availability_required',
        'availability'
    ];

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'volunteer_role_user',
            'volunteer_role_id',
            'user_id'
        );
    }

    public function applications()
    {
        return $this->hasMany(VolunteerApplication::class, 'role_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'role_id');
    }
}
