<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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

    public function gender()
    {
        return $this->belongsTo('App\Models\Gender');
    }

    public function user()
    {
        return $this->belongsToMany(User::class);
    }

    public function from()
    {
        return $this->belongsToMany(From::class);
    }

    public function mype()
    {
        return $this->belongsToMany(Mype::class);
    }

    public function typedocument()
    {
        return $this->belongsTo('App\Models\Typedocument');
    }

    public function advisory()
    {
        return $this->hasMany('App\Models\Advisory');
    }

    public function formalization10()
    {
        return $this->belongsTo('App\Models\Formalization10');
    }

    public function formalization20()
    {
        return $this->belongsTo('App\Models\Formalization20');
    }

    public function idadvisory()
    {
        return $this->hasMany('App\Models\Advisory', 'people_id');
    }

    public function idformalization10()
    {
        return $this->hasMany('App\Models\Formalization10', 'people_id');
    }

    public function idformalization20()
    {
        return $this->hasMany('App\Models\Formalization20', 'people_id');
    }

    public function scopeWithProfileAndRelations($query)
    {
        return $query->with(['city', 'province', 'district', 'gender', 'typedocument', 'from', 'user.profile'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            $user->from()->detach();
            $user->user()->detach();
        });
    }
}
