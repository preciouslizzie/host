<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementRead extends Model
{
    protected $fillable = [
    'announcement_id',
    'volunteer_id',
    'read_at',
];
}
