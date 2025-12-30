<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropagandaMedia extends Model
{
    use HasFactory;

    protected $table = 'propagandamedia';

    protected $fillable = [
        'name'
    ];
}
