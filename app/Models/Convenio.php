<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Convenio extends Model
{
    use HasFactory;

    protected $table = 'convenios';

    protected $fillable = [
        'institucion',
        'nombre_convenio',
        'objeto',
        'fecha_emision',
        'inicio_vigencia',
        'tipo_renovacion',
        'plazo_renovacion_anios',
        'vencimiento',
        'estado',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'inicio_vigencia' => 'date',
        'vencimiento' => 'date',
        'plazo_renovacion_anios' => 'integer',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function contactos(): HasMany
    {
        return $this->hasMany(ConvenioContacto::class, 'convenio_id');
    }

    public function compromisos(): HasMany
    {
        return $this->hasMany(ConvenioCompromiso::class, 'convenio_id');
    }

    public function adendas(): HasMany
    {
        return $this->hasMany(ConvenioAdenda::class, 'convenio_id');
    }

    public function gestion(): HasOne
    {
        return $this->hasOne(ConvenioGestion::class, 'convenio_id');
    }
}
