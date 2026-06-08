<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NombreActividad extends Model
{
    use HasFactory;

    protected $table = 'nombre_actividad';

    protected $fillable = ['tipo_actividad_id', 'name'];
}
