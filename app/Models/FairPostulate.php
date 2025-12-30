<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FairPostulate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'fairpostulate';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'fair_id',
        'mype_id',
        'person_id',            // representante de la empresa
        'invitado_id',          // persona invitado
        'ruc',
        'dni',
        'email',
        'hasParticipatedProduce',
        'nameService',
        'hasParticipatedFair',
        'nameFair',
        'propagandamedia_id',
        'positionUser1',        // cargo representante de la empresa
        'positionUser2',        // cargo invitado
        'howKnowEvent_id'
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

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('email', 'like', '%' . $search . '%')
                ->orWhereHas('mype', function ($q) use ($search) {
                    $q->where(function ($q) use ($search) {
                        $q->where('comercialName', 'like', '%' . $search . '%')
                            ->orWhere('ruc', 'like', '%' . $search . '%')
                            ->orWhere('socialReason', 'like', '%' . $search . '%')
                            ->orWhere('businessSector', 'like', '%' . $search . '%')
                            ->orWhere('nameGremio', 'like', '%' . $search . '%');
                    });
                })
                ->orWhereHas('person', function ($q) use ($search) {
                    $q->where(function ($q) use ($search) {
                        $q->where('documentnumber', 'like', '%' . $search . '%')
                            ->orWhere('lastname', 'like', '%' . $search . '%')
                            ->orWhere('middlename', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                });
        }

        return $query;
    }
}
