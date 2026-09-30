<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvenioAdenda extends Model
{
    use HasFactory;

    protected $table = 'convenios_adendas';

    protected $fillable = [
        'convenio_id',
        'numero',
        'anios',
        'desde',
        'hasta',
    ];

    protected $casts = [
        'numero' => 'integer',
        'anios' => 'integer',
        'desde' => 'date',
        'hasta' => 'date',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }
}
