<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Formalization10 extends Model
{
    use HasFactory;

    protected $table = 'formalizations10';

    use SoftDeletes;

    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];

    public function economicsector()
    {
        return $this->belongsTo('App\Models\EconomicSector');
    }

    public function detailprocedure()
    {
        return $this->belongsTo('App\Models\DetailProcedure');
    }

    public function comercialactivity()
    {
        return $this->belongsTo('App\Models\ComercialActivities');
    }

    public function modality()
    {
        return $this->belongsTo('App\Models\Modality');
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
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    public function people()
    {
        return $this->belongsTo('App\Models\People');
    }

    public function idpeople()
    {
        return $this->belongsTo('App\Models\People', 'people_id');
    }

    public function scopeWithFormalizationAndRelations($query)
    {
        return $query->with([
            'economicsector',
            'detailprocedure',
            'comercialactivity',
            'modality',
            'city',
            'province',
            'district',
            'user.profile',
            'people:id,name,lastname,middlename,documentnumber,email,phone',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function scopeByUserId($query, $userId)
    {
        return $query->whereHas('user', function($q) use ($userId) {
            $q->where('id', $userId);
        });
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

    // DESCARGAR EXCEL DE FORMALIZACIONES CON RUC 10
    public function scopeAllFormalizations10($query, $filters)
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
            'detailprocedure',
            'economicsector',
            'comercialactivity',
            'city',
            'province',
            'district'
        ])
        ->orderBy('created_at', 'desc')->get()->map(function ($item) {
            $item->asesorsupervisor = optional($item->supervisor)->supervisorUser->profile ?? auth()->user()->profile;
            return $item;
        });
    }

    // todas las formalizaciones de tipo RUC 10
    public function scopeWithAllFomalizations10($query)
    {
        return $query->with([
            'modality',
            'people.gender:id,name',
            'people.typedocument:id,name',

            'supervisor.supervisorUser.profile',

            'supervisado.supervisadoUser.profile',
            'supervisado.supervisadoUser.profile.cde:id,name',

            'detailprocedure',
            'economicsector',
            'comercialactivity',
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
            'modality',
            'people.gender:id,name',
            'people.typedocument:id,name',
            'supervisor.supervisorUser.profile',
            'supervisado.supervisadoUser.profile',
            'user.profile',
            'supervisado.supervisadoUser.profile.cde:id,name',
            'detailprocedure',
            'economicsector',
            'comercialactivity',
            'city',
            'province',
            'district'
        ])->orderBy('created_at', 'desc');

        if ($filters['user_id'] !== null) {
            $query->whereIn('user_id', $filters['user_id']);
        }

        if ($filters['dateStart'] && $filters['dateEnd']) {
            $endDate = date('Y-m-d', strtotime($filters['dateEnd'] . ' + 1 day'));
            $query->whereBetween('created_at', [$filters['dateStart'], $endDate]);
        }

        return $query->paginate(20);
    }
}
