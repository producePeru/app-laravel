<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lengua extends Model
{
    use HasFactory;

    protected $table = 'lengua';

    protected $fillable = [
        'name'
    ];
}
