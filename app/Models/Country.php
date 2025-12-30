<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function advisory()
    {
        return $this->hasMany('App\Models\Advisory');
    }

    public function profiles()
    {
        return $this->hasMany('App\Models\Profile');
    }

    public function people()
    {
        return $this->hasMany(Person::class, 'country_id');
    }

}
