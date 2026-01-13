<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPEvent extends Model
{
    use HasFactory;

    protected $table = 'mp_eventos';

    protected $fillable = [
        'title',
        'slug',
        'component',
        'capacitador_id',
        'city_id',
        'province_id',
        'district_id',
        'modality_id',
        'place',
        'date',
        // 'hours',
        'training_time',
        'startDate',
        'endDate',

        'link',
        'aliado',
        'hourStart',
        'hourEnd'
    ];


    public function capacitador()
    {
        return $this->belongsTo(MPCapacitador::class, 'capacitador_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class, 'modality_id');
    }

    public function attendances()
    {
        return $this->hasMany(MPAttendance::class, 'event_id');
    }



    public function scopeWithItems($query, $filters)
    {
        $query = $query->with([
            'capacitador',
            'modality',
            'city',
        ]);

        if (!empty($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['name'] . '%');
            });
        }

        // filtra fechas de la columna date
        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('date', [
                $filters['startDate'],
                $filters['endDate']
            ]);
        }

        if (!empty($filters['year'])) {

            $query->whereYear('created_at', $filters['year']);
        }
    }
}
