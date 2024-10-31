<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FairType extends Model
{
    use HasFactory;

    protected $table = 'fairtypes';

    protected $fillable = ['name'];


    // public function fair()
    // {
    //     return $this->hasMany('App\Models\Fair', 'id');
    // }

}
