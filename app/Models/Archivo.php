<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Archivo extends Model
{
    use HasFactory;

    protected $table = 'archivos';

    protected $fillable = [
        'nombre_original',
        'nombre_archivo',
        'extension',
        'mime_type',
        'ruta',
        'tamanio',
        'descripcion',
        'usuario_id',
    ];

    protected $casts = [
        'tamanio' => 'integer',
        'usuario_id' => 'integer',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function ferias(): HasMany
    {
        return $this->hasMany(ArchivoFeria::class, 'archivo_id');
    }

    public function empresarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Empresario::class,
            'archivos_ferias',
            'archivo_id',
            'empresario_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
