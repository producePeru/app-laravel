<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UgsePostulante extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruc',
        'comercialName',
        'socialReason',
        'economicsector_id',
        'comercialactivity_id',
        'category_id',
        'city_id',
        'typeAsistente',
        'typedocument_id',
        'documentnumber',
        'lastname',
        'middlename',
        'name',
        'gender_id',
        'sick',
        'phone',
        'email',
        'birthday',
        'positionCompany',
        'bringsGuest',
        'howKnowEvent_id',
        'event_id',
        'instagram',
        'facebook',
        'web',
    ];

    // Relaciones

    public function economicsector()
    {
        return $this->belongsTo(EconomicSector::class);
    }

    public function comercialactivity()
    {
        return $this->belongsTo(ComercialActivities::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function typedocument()
    {
        return $this->belongsTo(Typedocument::class, 'typedocument_id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function howKnowEvent()
    {
        return $this->belongsTo(PropagandaMedia::class, 'howKnowEvent_id');
    }

    public function event()
    {
        return $this->belongsTo(Fair::class, 'event_id');
    }


    public function scopeWithBasicFilters($query, $filters)
    {
        $query->orderBy('id', 'desc');

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['document'])) {
            $query->where('documentnumber', 'like', '%' . $filters['document'] . '%');
        }

        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['phone'])) {
            $query->where('phone', 'like', '%' . $filters['phone'] . '%');
        }

        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }
    }

}

