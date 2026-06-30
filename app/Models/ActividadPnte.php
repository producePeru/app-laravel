<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActividadPnte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'actividades_pnte';

    protected $dates = [
        'deleted_at',
    ];

    protected $fillable = [
        'unidad',
        'mes',
        'fechas',
        'cantidad_dias',
        'tipo_actividad_id',
        'nombre_actividad_id',
        'tema',
        'region',
        'provincia',
        'distrito',
        'lugar',
        'entidad_organizadora',
        'entidad_aliada',
        'representante_id',
        'requiere_pasaje',
        'monto_gasto',
        'mypes_beneficiadas',
        'modalidad_id',
        'total_participantes',
        'total_asesorias',
        'total_formalizaciones',
        'slug',
        'cancelado',
        'cancelado_por_id',
        'reprogramado',
        'reprogramado_por_id',
        'registrado_por_id',
        'actualizado_por_id',
        'horario',
        'activo',
        'link',

        'componente_id',
        'trainer_id'
    ];

    protected $casts = [
        'fechas' => 'array',
        'horario' => 'array',
        'requiere_pasaje' => 'boolean',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function tipoActividad(): BelongsTo
    {
        return $this->belongsTo(TipoActividad::class, 'tipo_actividad_id');
    }

    public function nombreActividad(): BelongsTo
    {
        return $this->belongsTo(NombreActividad::class, 'nombre_actividad_id');
    }

    public function regionRel(): BelongsTo
    {
        return $this->belongsTo(City::class, 'region');
    }

    public function provinciaRel(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'provincia');
    }

    public function distritoRel(): BelongsTo
    {
        return $this->belongsTo(District::class, 'distrito');
    }

    public function representante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'representante_id');
    }

    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(Modality::class, 'modalidad_id');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por_id');
    }

    public function reprogramadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reprogramado_por_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }

    public function empresariosActividad()
    {
        return $this->hasMany(EmpresarioActividad::class, 'slug', 'slug');
    }

    public function tainnerPp093(): BelongsTo
    {
        return $this->belongsTo(PpCapacitador::class, 'trainer_id');
    }

    public function sedDescripcion()
    {
        return $this->hasOne(SedDescripcion::class, 'slug_actividad_pnte', 'slug');
    }
}
