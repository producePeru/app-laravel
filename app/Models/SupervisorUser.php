<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisorUser extends Model
{
    use HasFactory;

    protected $table = 'supervisor_user';

    public function profiles()
    {
        return $this->hasMany('App\Models\Advisory', 'user_id');
    }

}
