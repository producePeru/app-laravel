<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notary extends Model
{
    use HasFactory;

    protected $table = 'notaries';

    protected $fillable = [
        'address',
        'biometrico',
        'city_id',
        'district_id',
        'gastos',
        'infocontacto',
        'name',
        'province_id',
        'sociointerveniente',
        'tarifanormal',
        'tarifasocial',
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

    public function scopeWithNotariesById($query, $filters)
    {
        $query = $query->with(['city', 'province', 'district', 'user.profile'])
            ->orderBy('created_at', 'asc');

        if (isset($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (isset($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        // Filtrar por nombres de provincia, ciudad o distrito
        if (isset($filters['city_name'])) {
            $query->whereHas('city', function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['city_name'] . '%');
            });
        }

        if (isset($filters['province_name'])) {
            $query->whereHas('province', function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['province_name'] . '%');
            });
        }

        if (isset($filters['district_name'])) {
            $query->whereHas('district', function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['district_name'] . '%');
            });
        }

        return $query->paginate(200);
    }


    public function profiles()
    {
        return $this->hasMany(Profile::class, 'notary_id');
    }

    public function getGastosAttribute($value)
    {
        return json_decode($value, true);
    }

    public function scopeWithNotariesAndRelations($query, $cityId = null)
    {
        return $query->where('status', '!=', 0) // Excluir notarías inactivas
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
}


// ALTER TABLE notaries
// ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER user_id;
