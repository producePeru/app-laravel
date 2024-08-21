<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionPlans extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'actionsplans';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'people_id',
        'asesor_id',
        'cde_id',
        'component_1',
        'component_2',
        'component_3',
        'ruc',
        'numberSessions',
        'startDate',
        'endDate',
        'totalDate',
        'actaCompromiso',
        'envioCorreo'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'asesor_id');
    }

    public function cde()
    {
        return $this->belongsTo('App\Models\Cde', 'cde_id');
    }

    public function businessman()
    {
        return $this->belongsTo('App\Models\People', 'people_id');
    }

    public function component1()
    {
        return $this->belongsTo('App\Models\Component', 'component_1');
    }

    public function component2()
    {
        return $this->belongsTo('App\Models\Component', 'component_2');
    }

    public function component3()
    {
        return $this->belongsTo('App\Models\Component', 'component_3');
    }
}
