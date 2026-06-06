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
    ];

    public function empresario()
    {
        return $this->belongsTo(
            Empresario::class,
            'empresario_id', // ✅ FK exacta
            'id'             // ✅ PK de empresarios
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