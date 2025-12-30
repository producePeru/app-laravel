<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Advisory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];

    protected $hidden = [
        // 'people_id',
        'component_id',
        // 'theme_id',
        'modality_id',
        'province_id',
        'city_id',
        'district_id'
    ];

    public function component()
    {
        return $this->belongsTo('App\Models\Component');
    }

    public function theme()
    {
        return $this->belongsTo('App\Models\Themecomponent');
    }

    public function modality()
    {
        return $this->belongsTo('App\Models\Modality');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function asesor()
    {
        return $this->belongsTo('App\Models\Profile', 'user_id');
    }

    public function people()
    {
        return $this->belongsTo('App\Models\People');
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country', 'people.country_id', 'countries.id');
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

    public function cde()
    {
        return $this->belongsTo('App\Models\Cde');
    }
    public function sede()
    {
        return $this->belongsTo('App\Models\Cde', 'cde_id');
    }

    // relacion
    public function gender()
    {
        return $this->belongsTo('App\Models\Gender', 'people.gender_id', 'genders.id');
    }

    public function typedocument()
    {
        return $this->belongsTo('App\Models\Gender', 'people.typedocument_id', 'typedocuments.id');
    }

    public function supervisor()
    {
        return $this->belongsTo('App\Models\SupervisorUser', 'user_id', 'supervisado_id');
    }

    public function supervisado()
    {
        return $this->belongsTo('App\Models\SupervisorUser', 'user_id', 'supervisado_id');
    }

    public function notary()
    {
        return $this->belongsTo('App\Models\Profile', 'notary_id', 'user_id');
    }

    public function economicsector()
    {
        return $this->belongsTo('App\Models\EconomicSector');
    }
    public function comercialactivity()
    {
        return $this->belongsTo('App\Models\ComercialActivities');
    }


    // DESCARGAR EXCEL DE ASESORIAS
    public function scopeDescargaExcelAsesorias($query, $filters)
    {
        if ($filters['dateStart'] && $filters['dateEnd']) {
            $endDate = date('Y-m-d', strtotime($filters['dateEnd'] . ' + 1 day'));
            $query->whereBetween('created_at', [$filters['dateStart'], $endDate]);
        }

        return $query->with([
            'modality',
            'comercialactivity',
            'economicsector',
            'people.gender:id,name',
            'people.typedocument:id,avr',
            'supervisor.supervisorUser.profile',
            'supervisado.supervisadoUser.profile',
            'supervisado.supervisadoUser.profile.cde',
            'sede',
            'theme',
            'component',
            'city',
            'province',
            'district'
        ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->asesorsupervisor = optional($item->supervisor)->supervisorUser->profile ?? auth()->user()->profile;
                return $item;
            });
    }

    // todas las asesorias y paginadas...
    public function scopeWithAllAdvisories($query)
    {
        return $query->with([
            'modality',
            'people.gender:id,name',
            'people.typedocument:id,name',

            'supervisor.supervisorUser.profile',

            'supervisado.supervisadoUser.profile',
            'supervisado.supervisadoUser.profile.cde:id,name',

            'theme',
            'component',
            'city',
            'province',
            'district'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }


    public function scopeWithProfileAndRelations($query)
    {
        return $query->with([
            'modality',
            'people:id,name,lastname,middlename,documentnumber,email,phone',
            'theme',
            'component',
            'city',
            'province',
            'district'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function scopeByUserId($query, $userId)
    {
        return $query->whereHas('user', function ($q) use ($userId) {
            $q->where('id', $userId);
        });
    }

    // ULTIMO 2025 ***
    public function scopeWithAdvisoryRangeDate($query, $filters)
    {
        $query = $query->with([
            'city:id,name',
            'comercialactivity:id,name',
            'component:id,name',
            'district:id,name',
            'economicsector:id,name',
            'modality:id,name',
            'people:id,documentnumber,birthday,lastname,middlename,name,gender_id,country_id,typedocument_id,sick,hasSoon,phone,email',
            'people.gender:id,name',
            'people.pais:id,name',
            'people.typedocument:id,avr',
            'province:id,name',
            'sede',
            'theme:id,name',
            'user:id,name,lastname,middlename',
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
