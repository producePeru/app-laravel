<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvenioContacto extends Model
{
    use HasFactory;

    protected $table = 'convenios_contactos';

    protected $fillable = [
        'convenio_id',
        'tipo',
        'nombre',
        'correo',
        'celular',
    ];

    public const TIPOS = [
        'repProduce',
        'repContraparte',
        'coordProduce',
        'coordContraparte',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }
}
