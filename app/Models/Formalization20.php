<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Formalization20 extends Model
{
    use HasFactory;

    protected $table = 'formalizations20';

    use SoftDeletes;

    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];

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

    public function typecapital()
    {
        return $this->belongsTo('App\Models\Typecapital');
    }

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

    public function sede()
    {
        return $this->belongsTo('App\Models\Cde', 'cde_id');
    }

    public function cde()
    {
        return $this->belongsTo('App\Models\Cde');
    }

    public function asesor()
    {
        return $this->belongsTo('App\Models\Profile', 'user_id');
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
            'notary:id,name',
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
        return $query->whereHas('user', function ($q) use ($userId) {
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

    public function scopeAllFormalizations20($query, $filters)
    {
        if ($filters['dateStart'] && $filters['dateEnd']) {
            $endDate = date('Y-m-d', strtotime($filters['dateEnd'] . ' + 1 day'));
            $query->whereBetween('created_at', [$filters['dateStart'], $endDate]);
        }

        return $query->with([
            'modality',
            'people.gender:id,name',
            'supervisor.supervisorUser.profile',
            'supervisado.supervisadoUser.profile',
            'supervisado.supervisadoUser.profile.cde',
            'sede',

            'mype:id,name,ruc',
            'comercialactivity',
            'regime',
            'notary:id,name',
            'economicsector',
            'city',
            'province',
            'district',
            'typecapital'
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
            'notary:id,name',
            'economicsector',

            'city',
            'province',
            'district'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    //filters
    public function scopeWithFormalizationRangeDate($query, $filters)
    {
        $query->with([

            'city:id,name',
            'comercialactivity:id,name',
            'district:id,name',
            'economicsector:id,name',
            'modality:id,name',
            'mype:id,name,ruc',
            'notary:id,name',
            'people:id,documentnumber,birthday,lastname,middlename,name,gender_id,country_id,typedocument_id,sick,hasSoon,phone,email',
            'people.typedocument:id,avr',
            'province:id,name',
            'regime',
            'sede',
            'user'

        ])->orderBy('created_at', 'desc');

        if (!empty($filters['asesor'])) {
            $query->where('user_id', $filters['asesor']);
        }

        if (!empty($filters['name'])) {
            $name = trim($filters['name']);

            $query->whereHas('people', function ($q) use ($name) {
                $q->where(function ($sub) use ($name) {
                    $sub->where('documentnumber', 'like', "%{$name}%")
                        ->orWhere('name', 'like', "%{$name}%")
                        ->orWhere('lastname', 'like', "%{$name}%")
                        ->orWhere('middlename', 'like', "%{$name}%")
                        ->orWhereRaw("CONCAT_WS(' ', name, lastname, middlename) LIKE ?", ["%{$name}%"]);
                });
            });
        }

        if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
            $endDate = date('Y-m-d', strtotime($filters['dateEnd'] . ' +1 day'));
            $query->whereBetween('created_at', [$filters['dateStart'], $endDate]);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        if (!empty($filters['typeCdes'])) {
            $query->whereHas('sede', function ($q) use ($filters) {
                $q->where('cdetype_id', $filters['typeCdes']);
            });
        }
    }
}
