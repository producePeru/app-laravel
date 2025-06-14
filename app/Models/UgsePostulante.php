<?php

namespace App\Models;

use Carbon\Carbon;
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
        'attended'
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
        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filters['dateStart'])->startOfDay(),
                Carbon::parse($filters['dateEnd'])->endOfDay()
            ]);
        }

        if (!empty($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('ruc', 'like', '%' . $filters['name'] . '%')
                    ->orWhere('documentnumber', 'like', '%' . $filters['name'] . '%');
            });
        }
    }
}
