<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresarioActividad extends Model
{
    use HasFactory;

    protected $table = 'empresario_actividad';

    protected $fillable = [
        'actividad_id',
        'slug',
        'empresario_id',
        'numero_dni',
        'fecha_asistencia',
        'personal_asesoria',
        'personal_formalizacion',
        'fecha_seleccionada',
        'horario_inicio',
        'horario_fin',
        'c_constancia',

        // Test de entrada
        'test_entrada',
        'fecha_te',

        // Test de salida
        'test_salida',
        'caso_practico',
        'ratings',
        'sugerencias',
        'fecha_ts',
    ];

    protected $casts = [
        'test_entrada' => 'array',
        'test_salida'  => 'array',
        'ratings'      => 'array',

        'fecha_te'     => 'datetime',
        'fecha_ts'     => 'datetime',
    ];

    public function empresario()
    {
        return $this->belongsTo(
            Empresario::class,
            'empresario_id',
            'id'
        );
    }

    public function actividadPnte()
    {
        return $this->belongsTo(
            ActividadPnte::class,
            'slug',
            'slug'
        );
    }
}






// public function empresario()
// {
//     return $this->belongsTo(
//         Empresario::class,
//         'numero_dni', // columna local
//         'numero_dni' // columna de empresarios
//     );
// }