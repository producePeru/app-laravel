<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPDiagnostico extends Model
{
    use HasFactory;

    protected $table = 'mp_diag_preguntas';

    protected $fillable = [
        'label',
        'type',
        'model',
        'required',
        'status'
    ];

    public function options()
    {
        return $this->hasMany(MPDiagnosticoOption::class, 'diag_pregunta_id');
    }
}
