<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event2 extends Model
{
    use HasFactory;

    protected $table = 'events2';

    protected $fillable = [
        'tabla',
        'row_id',
        'visible',
        'resultados',
        'cancelado',
        'programado',
        'unidad',
        'estado'
    ];
}
