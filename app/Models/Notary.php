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
            'city', 'province', 'district', 'user.profile'
            ])
            // ->orderBy('created_at', 'desc')
            ->paginate(200);
    }

    public function scopeWithNotariesById($query, $cityId)
    {
        return $query->with([
            'city', 'province', 'district', 'user.profile'
            ])
            ->where('city_id', $cityId) // Filtro por city_id
            ->orderBy('created_at', 'desc')
            ->paginate(200);
    }
}
