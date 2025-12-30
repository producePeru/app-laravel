<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruc',
        'razonSocial',
        // 'tipoContribuyente_id',
        'sectorEconomico_id',
        'rubro_id',
        'actividadComercial_id',
        'region_id',
        'provincia_id',
        'distrito_id',
        'direccion',
        'estado',
        'condicion'
    ];
}
