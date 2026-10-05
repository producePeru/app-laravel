<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConvenioCompromiso extends Model
{
    use HasFactory;

    protected $table = 'convenios_compromisos';

    protected $fillable = [
        'convenio_id',
        'tipo',
        'compromiso',
        'orden',
        'realizado',
        'actividad',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public const TIPOS = [
        'produce',
        'contraparte',
        'partes',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }

    public function medios(): HasMany
    {
        return $this->hasMany(ConvenioCompromisoEvidencia::class, 'compromiso_id')->orderBy('id');
    }

    public function avances(): HasMany
    {
        return $this->hasMany(ConvenioCompromisoAvance::class, 'compromiso_id')->orderBy('corte');
    }
}
