<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function people()
    {
        return $this->hasMany('App\Models\People');
    }

    public function profile()
    {
        return $this->hasMany('App\Models\Profile');
    }

    public function advisory()
    {
        return $this->hasMany('App\Models\Advisory');
    }

    public function formalization10()
    {
        return $this->hasMany('App\Models\Formalization10');
    }

    public function formalization20()
    {
        return $this->hasMany('App\Models\Formalization20');
    }

    public function convenios()
    {
        return $this->hasMany('App\Models\Agreement');
    }
}
