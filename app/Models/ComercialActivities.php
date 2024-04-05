<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComercialActivities extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'comercialactivities';

    protected $hidden = ['created_at', 'updated_at'];

    public function formalization10()
    {
        return $this->hasMany('App\Models\Formalization10');
    }

    public function formalization20()
    {
        return $this->hasMany('App\Models\Formalization20');
    }
}
