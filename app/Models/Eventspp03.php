<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eventspp03 extends Model
{
    use HasFactory;

    protected $table = 'eventspp03';

    protected $fillable = [
        'nameEvent',
        'city_id',
        'place',
        'modality_id',
        'dateStart',
        'dateEnd',
        'hours',
        'description',
        'slug'
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class, 'modality_id');
    }

    public function scopeWithItems($query, $filters)
    {
        $query = $query->with([
            'modality',
            'city',
            ]);


        if (!empty($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nameEvent', 'like', '%' . $filters['name'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['name'] . '%');
            });
        }


        if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereBetween('dateStart', [$filters['dateStart'], $filters['dateEnd']])
                  ->orWhereBetween('dateEnd', [$filters['dateStart'], $filters['dateEnd']]);
            });
        }


        if (!empty($filters['year'])) {

            $query->whereYear('created_at', $filters['year']);
        }

        if (!empty($filters['orderby']) && $filters['orderby'] == 1) {

            $query->orderBy('attendance_list_count', 'desc');

        } else if (!empty($filters['orderby']) && $filters['orderby'] == 2) {

            $query->orderBy('attendance_list_count', 'asc');

        } else if (!empty($filters['orderby']) && $filters['orderby'] == 3) {

            $query->orderBy('finally', 'desc');

        } else {

            $query->orderBy('created_at', 'desc');

        }
    }
}
