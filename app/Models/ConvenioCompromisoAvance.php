<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConvenioCompromisoAvance extends Model
{
    use HasFactory;

    protected $table = 'convenios_compromiso_avances';

    protected $fillable = [
        'compromiso_id',
        'corte',
        'desde',
        'hasta',
        'realizado',
        'actividad',
        'user_id',
    ];

    protected $casts = [
        'corte' => 'integer',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function compromiso(): BelongsTo
    {
        return $this->belongsTo(ConvenioCompromiso::class, 'compromiso_id');
    }

    public function medios(): HasMany
    {
        return $this->hasMany(ConvenioCompromisoEvidencia::class, 'avance_id')->orderBy('id');
    }

    /**
     * Usuario que registró/actualizó la actividad del corte.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
