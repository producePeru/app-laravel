<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_id',
        'especialista_id',
        'dimension_id',
        'fecha',
        'horaInicio',
        'horaFin',
        'modalidad',
        'tema',
        'lugar',
        'participantes',
        'empresas',
        'estado',
        'coordinador',
        'observaciones',
    ];

    public function meta()
    {
        return $this->belongsTo(TrainingMeta::class, 'meta_id');
    }

    public function especialista()
    {
        return $this->belongsTo(TrainingSpecialist::class, 'especialista_id');
    }

    public function dimension()
    {
        return $this->belongsTo(TrainingDimension::class, 'dimension_id');
    }
}
