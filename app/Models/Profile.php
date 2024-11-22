<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function office()
    {
        return $this->belongsTo('App\Models\Office');
    }

    public function cde()
    {
        return $this->belongsTo('App\Models\Cde');
    }

    public function gender()
    {
        return $this->belongsTo('App\Models\Gender');
    }

    public function people()
    {
        return $this->belongsTo('App\Models\People');
    }

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'user_id', 'user_id');         //inversa
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }

    public function province()
    {
        return $this->belongsTo('App\Models\Province');
    }

    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }


    protected static function booted()
    {
        static::deleting(function ($profile) {
            $profile->user()->delete();
        });
    }

    public function notary()
    {
        return $this->belongsTo(Notary::class, 'notary_id');
    }
}
