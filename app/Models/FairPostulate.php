<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FairPostulate extends Model
{
    use HasFactory;

    protected $table = 'fairpostulate';

    protected $fillable = [
        'fair_id',
        'mype_id',
        'person_id',
        'ruc',
        'dni',
        'email',
        'hasParticipatedProduce',
        'nameService',
        'hasParticipatedFair',
        'nameFair'
    ];

    public function mype()
    {
        return $this->belongsTo(Mype::class, 'mype_id');
    }

    public function person()
    {
        return $this->belongsTo(People::class, 'person_id');
    }

    public function fair()
    {
        return $this->belongsTo(Fair::class, 'fair_id');
    }

    public function region()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function distrit()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
