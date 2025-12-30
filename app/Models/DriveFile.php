<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'drive_file';

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
