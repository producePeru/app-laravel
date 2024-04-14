<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisorUser extends Model
{
    use HasFactory;

    protected $table = 'supervisor_user';

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'supervisado_id');
    }

    public function profilesupervisor()
    {
        return $this->hasOne(Profile::class, 'user_id', 'supervisor_id');
    }


    // public function user()
    // {
    //     return $this->hasMany(User::class, 'id', 'supervisado_id');
    // }

    public function supervisorUser()
    {
        return $this->belongsTo('App\Models\User', 'supervisor_id');
    }

    public function supervisadoUser()
    {
        return $this->belongsTo('App\Models\User', 'supervisado_id');
    }

}
