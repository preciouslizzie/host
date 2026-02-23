<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Audio extends Model
{
    use HasFactory;

    protected $table = 'audios'; 
    
    protected $fillable = [
        'title',
        'audio_url',
        'file_path',
        'etag',
    ];
}
