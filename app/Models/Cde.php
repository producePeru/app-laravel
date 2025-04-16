<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cde extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function profiles()
    {
        return $this->hasMany('App\Models\Profile');
    }

    public function supervisor()
    {
        return $this->belongsTo('App\Models\Supervisor', 'cde_id', 'cde_id');
    }

    public function cdetype()
    {
        return $this->belongsTo('App\Models\CdeType');
    }

    public function formalizationdigital()
    {
        return $this->hasMany('App\Models\FormalizationDigital');
    }

    public function region()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function distrito()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
