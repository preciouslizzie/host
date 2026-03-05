<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLink extends Model
{
    protected $fillable = [
        'title',
        'link',
        'role_id',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function role()
    {
        return $this->belongsTo(VolunteerRole::class, 'role_id');
    }
}
