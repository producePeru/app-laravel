<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UgsecoDiagnosticoPregunta extends Model
{
    use HasFactory;

    protected $table = 'ugseco_diag_preguntas';

    protected $fillable = [
        'componente',
        'numero',
        'pregunta',
        'orden',
    ];

    public function opciones()
    {
        return $this->hasMany(UgsecoDiagnosticoOpcion::class, 'pregunta_id')->orderBy('orden');
    }
}
