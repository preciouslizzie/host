<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLink extends Model
{
    protected $fillable = [
        'title',
        'link',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
