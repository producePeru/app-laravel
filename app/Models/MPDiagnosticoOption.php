<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPDiagnosticoOption extends Model
{
    use HasFactory;

    protected $table = 'mp_diag_preguntas_opciones';

    protected $fillable = [
        'name',
        'diag_pregunta_id'
    ];
}
