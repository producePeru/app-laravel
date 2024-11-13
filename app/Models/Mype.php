<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mype extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function people()
    {
        return $this->belongsToMany(People::class);
    }

    public function formalization20()
    {
        return $this->hasMany('App\Models\Formalization20');
    }

    public function region()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
