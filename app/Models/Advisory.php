<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Advisory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'people_id',
        'component_id',
        'theme_id',
        'modality_id',
        'province_id',
        'city_id',
        'district_id'
    ];

    public function component()
    {
        return $this->belongsTo('App\Models\Component');
    }

    public function theme()
    {
        return $this->belongsTo('App\Models\Themecomponent');
    }

    public function modality()
    {
        return $this->belongsTo('App\Models\Modality');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function asesor()
    {
        return $this->belongsTo('App\Models\Profile', 'user_id');
    }

    public function people()
    {
        return $this->belongsTo('App\Models\People');
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

    // relacion 
    public function gender()
    {
        return $this->belongsTo('App\Models\Gender', 'people.gender_id', 'genders.id');
    }

    public function supervisorx()
    {
        return $this->belongsTo(Profile::class, 'user_id')
            ->join('supervisor_user', 'supervisor_user.supervisor_id', '=', 'profiles.user_id');
    }

    public function scopeAllNotaries($query)
    {
        return $query->with([
            'modality',
            'people.gender:id,name',
            'asesor.cde:id,name',
            'theme',
            'component',
            'city',
            'province',
            'district'
        ])
        ->orderBy('created_at', 'desc')->get();
    }


    public function scopeWithProfileAndRelations($query)
    {
        return $query->with([
            'modality',
            'people:id,name,lastname,middlename,documentnumber,email,phone',
            'theme',
            'component',
            'city',
            'province',
            'district'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    }

    public function scopeByUserId($query, $userId)
    {
        return $query->whereHas('user', function($q) use ($userId) {
            $q->where('id', $userId);
        });
    }

    
}
