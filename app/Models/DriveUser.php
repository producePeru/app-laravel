<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriveUser extends Model
{
    use HasFactory;

    protected $table = 'drive_users';

    protected $fillable = ['drive_id', 'user_ids'];

    protected $casts = [
        'user_ids' => 'array'
    ];
}
