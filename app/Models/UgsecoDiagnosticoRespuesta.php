<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UgsecoDiagnosticoRespuesta extends Model
{
    use HasFactory;

    protected $table = 'ugseco_diag_respuestas';

    protected $fillable = [
        'diagnostico_id',
        'pregunta_id',
        'opcion_id',
    ];

    public function diagnostico()
    {
        return $this->belongsTo(UgsecoDiagnostico::class, 'diagnostico_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(UgsecoDiagnosticoPregunta::class, 'pregunta_id');
    }

    public function opcion()
    {
        return $this->belongsTo(UgsecoDiagnosticoOpcion::class, 'opcion_id');
    }
}
