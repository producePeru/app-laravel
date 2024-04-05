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
}
