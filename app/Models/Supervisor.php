<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    use HasFactory;

    protected $table = 'supervisores';

    protected $guarded = ['id'];

    protected $fillable = ['user_id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->hasMany('App\Models\User', 'id');
    }
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'user_id');
    }
}
