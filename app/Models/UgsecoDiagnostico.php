<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UgsecoDiagnostico extends Model
{
    use HasFactory;

    protected $table = 'ugseco_diagnosticos';

    protected $fillable = [
        'empresario_id',
        'tipo_documento_id',
        'numero_documento',
        'ruc',
    ];

    public function empresario()
    {
        return $this->belongsTo(Empresario::class, 'empresario_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(Typedocument::class, 'tipo_documento_id');
    }

    public function respuestas()
    {
        return $this->hasMany(UgsecoDiagnosticoRespuesta::class, 'diagnostico_id');
    }
}
