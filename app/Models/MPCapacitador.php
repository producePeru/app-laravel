<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPCapacitador extends Model
{
    use HasFactory;

    protected $table = 'mp_capacitadores';

    protected $fillable = [
        'name',
        'ruc'
    ];
}
