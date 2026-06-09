<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PpCapacitador extends Model
{
    use HasFactory;

    protected $table = "pp_capacitadores";

    protected $fillable = [
        'nombres_apellidos',
        'dni',
        'institucion',
        'correo'
    ];
}
