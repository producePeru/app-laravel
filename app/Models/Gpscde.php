<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gpscde extends Model
{
    use HasFactory;

    protected $table = 'gps_cdes';

    protected $fillable = [
        'name_cde',
        'lat_cde',
        'log_cde'
    ];
}
