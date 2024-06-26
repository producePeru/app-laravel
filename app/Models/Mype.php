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
}
