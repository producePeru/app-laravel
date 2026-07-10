<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresario extends Model
{
    use HasFactory;

    protected $table = 'empresarios';

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'sector_economico_id',
        'rubro_id',
        'actividad_comercial_id',
        'region_id',
        'provincia_id',
        'distrito_id',
        'direccion',

        'pais_id',
        'tipo_documento_id',
        'numero_dni',
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'genero_id',
        'discapacidad',
        'celular',
        'correo_electronico',
        'academicdegree_id',
        'role_company_id',
        'cargo_empresa_id',
        'fecha_nacimiento',
        'edad',

        // otros
        'actividad_comercial_nombre',
        'tipo_empresa_id',
        'f_inicio_act',
        'venta_anual',
        'medio_entero',

        'coop_ruc',
        'coop_razon_social',
        'coop_rol'
    ];

    // ─── RELACIONES ───────────────────────────────────────────────

    public function sectorEconomico(): BelongsTo
    {
        return $this->belongsTo(EconomicSector::class, 'sector_economico_id');
    }

    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'rubro_id');
    }

    public function actividadComercial(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'actividad_comercial_id');
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'pais_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(City::class, 'region_id');
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'provincia_id');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(District::class, 'distrito_id');
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(Typedocument::class, 'tipo_documento_id');
    }

    public function genero(): BelongsTo
    {
        return $this->belongsTo(Gender::class, 'genero_id');
    }

    public function cargoEmpresa(): BelongsTo
    {
        return $this->belongsTo(RoleCompany::class, 'cargo_empresa_id');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(EmpresarioActividad::class, 'empresario_id');
    }

    public function gradoAcademico(): BelongsTo
    {
        return $this->belongsTo(AcademicDegree::class, 'academicdegree_id');
    }
}
