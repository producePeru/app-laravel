<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoFeria extends Model
{
    use HasFactory;

    protected $table = 'archivos_ferias';

    protected $fillable = [
        'archivo_id',
        'empresario_id',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'archivo_id');
    }

    public function empresario(): BelongsTo
    {
        return $this->belongsTo(Empresario::class, 'empresario_id');
    }
}
