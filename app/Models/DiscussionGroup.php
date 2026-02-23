<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussionGroup extends Model
{
    protected $fillable = ['name'];

    public function posts()
    {
        return $this->hasMany(DiscussionPost::class, 'group_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'discussion_group_user', 'group_id', 'user_id');
    }
}
