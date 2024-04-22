<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formalization20 extends Model
{
    use HasFactory;

    protected $table = 'formalization20s';

    protected $guarded = ['id'];

    // protected $hidden = [
    //     'city_id',
    //     'province_id',
    //     'district_id',
    //     'regime_id',
    //     'notary_id',
    //     'modality_id',
    //     'comercialactivity_id',
    //     'economicsector_id',
    //     // 'user_id',
    //     'mype_id',
    //     // 'people_id'
    // ];

    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }

    public function province()
    {
        return $this->belongsTo('App\Models\Province');
    }

    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }

    public function modality()
    {
        return $this->belongsTo('App\Models\Modality');
    }

    public function comercialactivity()
    {
        return $this->belongsTo('App\Models\ComercialActivities');
    }

    public function regime()
    {
        return $this->belongsTo('App\Models\Regime');
    }

    public function notary()
    {
        return $this->belongsTo('App\Models\Notary');
    }
    public function economicsector()
    {
        return $this->belongsTo('App\Models\EconomicSector');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function userupdater()
    {
        return $this->belongsTo('App\Models\User', 'userupdated_id');
    }

    public function mype()
    {
        return $this->belongsTo('App\Models\Mype');
    }

    public function people()
    {
        return $this->belongsTo('App\Models\People');
    }

    public function scopeWithFormalizationAndRelations($query)
    {
        return $query->with([
            'city',
            'province',
            'district',
            'modality',
            'comercialactivity',
            'regime',
            'notary:id,name,price',
            'economicsector',
            'user.profile',
            'mype:id,name,ruc',
            'people:id,name,lastname,middlename,documentnumber,email,phone',
            'userupdater.profile'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    // POR ID DE PEOPLE_ID
    public function scopeWithFormalizationAndRelationsId($query, $id)
    {
        return $query->where('people_id', $id)
            ->with([
                'user.profile',
                'people:id,name,lastname,middlename,documentnumber',
                'userupdater.profile',
                'mype:id,name,ruc'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function scopeByUserId($query, $userId)
    {
        return $query->whereHas('user', function($q) use ($userId) {
            $q->where('id', $userId);
        });
    }

    public function supervisor()
    {
        return $this->belongsTo('App\Models\SupervisorUser', 'user_id', 'supervisado_id');
    }

    public function supervisado()
    {
        return $this->belongsTo('App\Models\SupervisorUser', 'user_id', 'supervisado_id');
    }

    public function scopeAllFormalizations20($query)
    {
        return $query->with([
            'modality',
            'people.gender:id,name',

            'supervisor.supervisorUser.profile',

            'supervisado.supervisadoUser.profile',
            'supervisado.supervisadoUser.profile.cde:id,name',

            'mype:id,name,ruc',
            'comercialactivity',
            'regime',
            'notary:id,name,price',
            'economicsector',

            'city',
            'province',
            'district'
        ])
        ->orderBy('created_at', 'desc')->get()->map(function ($item) {
            $item->asesorsupervisor = optional($item->supervisor)->supervisorUser->profile ?? auth()->user()->profile;
            return $item;
        });
    }

    // todas las formalizaciones tipo 20
    public function scopeWithAllFomalizations20($query)
    {
        return $query->with([
            'modality',
            'people.gender:id,name',
            'people.typedocument:id,name',

            'supervisor.supervisorUser.profile',

            'supervisado.supervisadoUser.profile',
            'supervisado.supervisadoUser.profile.cde:id,name',

            'mype:id,name,ruc',
            'comercialactivity',
            'regime',
            'notary:id,name,price',
            'economicsector',

            'city',
            'province',
            'district'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    }
}
