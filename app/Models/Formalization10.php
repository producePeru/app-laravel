<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formalization10 extends Model
{
    use HasFactory;

    protected $table = 'formalizations10';

    protected $guarded = ['id'];

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
}
