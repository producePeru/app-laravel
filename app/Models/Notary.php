<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['city_id', 'province_id', 'district_id', 'user_id', 'created_at', 'updated_at'];

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

    public function scopeWithNotariesAndRelations($query)
    {
        return $query->with([
            'city',
            'province',
            'district',
            'user.profile'
        ])
            ->orderBy('created_at', 'asc')
            ->paginate(200);
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
}
