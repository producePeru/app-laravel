<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresarioEmprendimiento extends Model
{
    use HasFactory;

    protected $table = 'empresarios_emprendimiento';

    protected $fillable = [
        'empresario_id',
        'actividad_id',
        'redes_sociales',
        'pertenece_gremio',
        'nombre_gremio',
        'cap_prod_mensual',
        'porc_prod_planta',
        'porc_prod_maquila',
        'tiene_puntos_venta',
        'num_puntos_ventas',
        'desc_negocio',
        'pos',
        'yape_plim',
        'tiene_tiendas',
        'nombre_tienda',
        'tiene_delivery',
        'factura_electronica',
        'participado_produce',
        'nombre_servicio',
        'participado_feria',
        'nombre_feria',
        'formalizado_produce',
        'indecopi',
        'logros_empresa',
        'terminos_condiciones',
    ];

    protected $casts = [
        'redes_sociales' => 'array',
        'pertenece_gremio' => 'boolean',
        'tiene_puntos_venta' => 'boolean',
        'pos' => 'boolean',
        'yape_plim' => 'boolean',
        'tiene_tiendas' => 'boolean',
        'tiene_delivery' => 'boolean',
        'factura_electronica' => 'boolean',
        'participado_produce' => 'boolean',
        'participado_feria' => 'boolean',
        'formalizado_produce' => 'boolean',
        'indecopi' => 'boolean',
        'terminos_condiciones' => 'boolean',
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function empresario(): BelongsTo
    {
        return $this->belongsTo(Empresario::class, 'empresario_id');
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(ActividadPnte::class, 'actividad_id');
    }
}
