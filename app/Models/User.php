<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isVolunteer()
    {
        return $this->role === 'volunteer';
    }

    public function volunteerRoles()
    {
        return $this->belongsToMany(
            VolunteerRole::class,
            'volunteer_role_user',
            'user_id',
            'volunteer_role_id'
        );
    }

    public function volunteerApplications()
    {
        return $this->hasMany(VolunteerApplication::class, 'user_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'user_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'user_id');
    }

    public function availability()
    {
        return $this->hasMany(VolunteerAvailability::class, 'user_id');
    }

    public function discussionPosts()
    {
        return $this->hasMany(DiscussionPost::class, 'user_id');
    }

    public function discussionGroups()
    {
        return $this->belongsToMany(
            DiscussionGroup::class,
            'discussion_group_user',
            'user_id',
            'discussion_group_id'
        );
    }

    public function announcements()
    {
        return $this->belongsToMany(Announcement::class);
    }

    public function createdAnnouncements()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }
}
