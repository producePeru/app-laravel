<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SedDescripcion extends Model
{
    use HasFactory;

    protected $table = "sed_descripcion";

    protected $fillable = [
        'slug_actividad_pnte',
        'descripcion',
        'mensaje_finalizacion',
        'mensaje_correo',
        'mensaje_recordatorio'
    ];
}
