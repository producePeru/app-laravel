<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvenioGestion extends Model
{
    use HasFactory;

    protected $table = 'convenios_gestion';

    protected $fillable = [
        'convenio_id',
        'meta',
        'financiamiento',
        'plan_actividades',
        'plan_validado',
        'plazo_reportes',
        'para_resolucion',
        'responsable_produce',
        'responsable_contraparte',
        'responsable_contraparte_cargo',
        'responsable_contraparte_correo',
        'responsable_contraparte_celular',
        'avances',
        'observaciones',
    ];

    protected $casts = [
        'plan_validado' => 'boolean',
        'responsable_produce' => 'integer',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }

    public function responsableProduce(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_produce');
    }
}
