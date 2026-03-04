<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'message',
        'created_by',
        'role_id',
        'priority',
        'target_roles',
    ];

    protected $casts = [
        'target_roles' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
{
    return $this->hasMany(AnnouncementRead::class);
}
}
