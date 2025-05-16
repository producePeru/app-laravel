<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notary extends Model
{
    use HasFactory;

    protected $table = 'notaries';

    protected $fillable = [
        // 'address',
        // 'biometrico',
        // 'city_id',
        // 'district_id',
        // 'gastos',
        // 'tarifa1',
        // 'tarifa2',
        // 'tarifa3',
        // 'tarifa4',
        // 'infocontacto',
        // 'name',
        // 'province_id',
        // 'sociointerveniente',
        // 'tarifanormal',
        // 'tarifasocial',

        'name',
        'city_id',
        'province_id',
        'district_id',
        'addressNotary',
        'gasto1',
        'gasto1Detail',
        'gasto2',
        'gasto2Detail',
        'gasto3',
        'gasto3Detail',
        'gasto4',
        'gasto4Detail',
        'gasto5',
        'gasto5Detail',
        'gasto6',
        'gasto6Detail',
        'testimonio',
        'legalization',
        'biometric',
        'aclaratory',
        'socio',
        'conditions',
        'contactName',
        'contactEmail',
        'contactPhone',
        'normalTarifa',
        'user_id',
        'status'
    ];

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

    public function formalization20()
    {
        return $this->hasMany('App\Models\Formalization20');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function scopeWithItems($query, $filters)
    {
        $query->with(['city', 'province', 'district', 'user.profile']);

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        return $query->orderBy('id', 'desc');
    }



    public function profiles()
    {
        return $this->hasMany(Profile::class, 'notary_id');
    }

    // public function getGastosAttribute($value)
    // {
    //     return json_decode($value, true);
    // }

    public function scopeWithNotariesAndRelations($query, $cityId = null)
    {
        return $query->where(function ($query) {
            $query->where('status', '!=', 0)
                ->orWhereNull('status'); // Permite los registros con status NULL
        })
            ->when($cityId, function ($query) use ($cityId) {
                return $query->where('city_id', $cityId); // Aplicar filtro si 'city' existe
            })
            ->with([
                'city',
                'province',
                'district',
                'user.profile'
            ])
            ->orderBy('city_id', 'asc')
            ->paginate(50);
    }

    // public function scopeWithItems($query, $filters)
    // {

    // }
}


// ALTER TABLE notaries
// ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER user_id;
