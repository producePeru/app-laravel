<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Drive extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function driveUsers()
    {
        return $this->hasMany(DriveUser::class);
    }

}
