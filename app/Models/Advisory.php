<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advisory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'user_id',
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

    public function scopeWithProfileAndRelations($query)
    {
        return $query->with([
            'modality',
            'people:id,name,lastname,middlename,documentnumber,email,phone',
            'user.profile',
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
