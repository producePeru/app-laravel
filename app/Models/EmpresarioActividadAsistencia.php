<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresarioActividadAsistencia extends Model
{
    use HasFactory;

    protected $table = 'empresario_actividad_asistencias';

    protected $fillable = [
        'empresario_actividad_id',
        'slug',
        'numero_dni',
        'fecha',
        'fecha_hora',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_hora' => 'datetime',
    ];

    public function empresarioActividad()
    {
        return $this->belongsTo(EmpresarioActividad::class, 'empresario_actividad_id');
    }
}
