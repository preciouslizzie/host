<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussionPost extends Model
{
    protected $fillable = ['group_id','user_id','message'];

    public function group()
    {
        return $this->belongsTo(DiscussionGroup::class, 'group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
