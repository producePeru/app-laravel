<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpRegistros extends Model
{
    use HasFactory;

    protected $table = 'cp_registros';

    protected $fillable = [
        'city_id',
        'province_id',
        'district_id',

        'economicsector_id',
        'comercialactivity_id',

        'component_id',
        'theme_id',
        'modality_id',

        'ruc',
        'razonSocial',

        'periodo',
        'cantidad',
        'ubicacion',

        'people_id',
        'ruc_obs',
        'user_id',
        'cde_id'
    ];

    public function economicsectors()
    {
        return $this->belongsTo(CpSectorPriorizado::class, 'economicsector_id', 'id');
    }
}
