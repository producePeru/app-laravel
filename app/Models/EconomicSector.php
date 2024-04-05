<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EconomicSector extends Model
{
    use HasFactory;

    protected $table = 'economicsectors';

    protected $guarded = ['id'];

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
