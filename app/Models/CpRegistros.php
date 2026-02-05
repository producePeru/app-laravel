<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class CpRegistros extends Model
{
    use HasFactory;

    use SoftDeletes;

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

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function economicsectors()
    {
        return $this->belongsTo(CpSectorPriorizado::class, 'economicsector_id', 'id');
    }

    public function comercialactivity()
    {
        return $this->belongsTo(ComercialActivities::class, 'comercialactivity_id');
    }

    public function component()
    {
        return $this->belongsTo(CpComponente::class, 'component_id', 'id');
    }

    public function theme()
    {
        return $this->belongsTo(CpTema::class, 'theme_id', 'id');
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class, 'modality_id');
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cde()
    {
        return $this->belongsTo(Cde::class, 'cde_id');
    }
}
