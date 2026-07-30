<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadCalendarEvento extends Model
{
    use HasFactory;

    protected $fillable = [
        'actividad_id',
        'horario_uid',
        'google_calendar_event_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
    ];

    public function actividad()
    {
        return $this->belongsTo(ActividadPnte::class, 'actividad_id');
    }
}
