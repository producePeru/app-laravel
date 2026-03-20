<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SedAsistente extends Model
{
    use HasFactory;

    protected $table = 'sed_asistencias';

    protected $fillable = [
        'sed_id',
        'mype_id',
        'attendance',
        'typeAsistente'
    ];

    public function postulante()
    {
        return $this->belongsTo(UgsePostulante::class, 'mype_id');
    }
}
