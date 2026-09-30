<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvenioCompromiso extends Model
{
    use HasFactory;

    protected $table = 'convenios_compromisos';

    protected $fillable = [
        'convenio_id',
        'tipo',
        'compromiso',
        'orden',
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
}
