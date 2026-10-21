<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UgsecoDiagnosticoOpcion extends Model
{
    use HasFactory;

    protected $table = 'ugseco_diag_opciones';

    protected $fillable = [
        'pregunta_id',
        'letra',
        'texto',
        'orden',
    ];

    public function pregunta()
    {
        return $this->belongsTo(UgsecoDiagnosticoPregunta::class, 'pregunta_id');
    }

    public function respuestas()
    {
        return $this->hasMany(UgsecoDiagnosticoRespuesta::class, 'opcion_id');
    }
}
