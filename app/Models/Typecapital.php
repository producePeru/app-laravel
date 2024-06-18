<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Typecapital extends Model
{
    use HasFactory;

    protected $table = 'typecapital';

    protected $fillable = ['name'];

    public function formalization20()
    {
        return $this->hasMany('App\Models\Formalization20');
    }
}
