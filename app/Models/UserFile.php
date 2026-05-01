<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFile extends Model
{
    //
    protected $fillable = [
        'user_id',
        'photo',
        'banner_path',
        'video',
        'language',
        'status',
    ];
}
