<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['province_id', 'created_at', 'updated_at'];

    public function people()
    {
        return $this->hasMany('App\Models\People');
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

    public function profile()
    {
        return $this->hasMany('App\Models\Profile');
    }
}
