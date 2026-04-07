<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory;

    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'id_pnte',
        'title',
        'city_id',
        'province_id',
        'district_id',
        'place',
        'dateStart',
        'dateEnd',
        'organiza',
        'numMypes',
        'description',
        'nameUser',
        'link',

        'gps',
        'start',
        'end',
        'user_id',
        'resultado',
        'rescheduled',
        'canceled'
    ];

    public function officePnte()
    {
        return $this->belongsTo(OfficePnte::class, 'id_pnte');
    }

    public function region()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
    public function province()
    {
        return $this->belongsTo('App\Models\Province');
    }

    public function district()
    {
        return $this->belongsTo('App\Models\District');
    }

    // ADMIN v 1.0
    // public function scopeWithAdvisoryRangeDate($query, $filters)
    // {
    //     $query = $query->with([
    //         'officePnte',
    //         'region'
    //     ])->orderBy('dateStart', 'desc');

    //     if (!empty($filters['name'])) {
    //         $query->where('title', 'like', '%' . $filters['name'] . '%');
    //     }

    //     if (!empty($filters['offices']) && $filters['offices'] != 3) {
    //         $officeGroups = [
    //             1 => [1, 3, 5, 8], // UGO
    //             2 => [2, 4, 6, 7]  // UGSE
    //         ];

    //         if (isset($officeGroups[$filters['offices']])) {
    //             $query->whereIn('id_pnte', $officeGroups[$filters['offices']]);
    //         }
    //     }

    //     if (!empty($filters['type'])) {
    //         $types = is_array($filters['type']) ? $filters['type'] : [$filters['type']];
    //         $query->whereIn('id_pnte', $types);
    //     }

    //     if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
    //         $dateStart = Carbon::createFromFormat('Y/m/d', $filters['dateStart'])->format('Y-m-d');
    //         $dateEnd = Carbon::createFromFormat('Y/m/d', $filters['dateEnd'])->format('Y-m-d');

    //         $query->where(function ($q) use ($dateStart, $dateEnd) {
    //             $q->whereBetween('dateStart', [$dateStart, $dateEnd])
    //                 ->orWhereBetween('dateEnd', [$dateStart, $dateEnd])
    //                 ->orWhere(function ($q) use ($dateStart, $dateEnd) {
    //                     $q->where('dateStart', '<=', $dateStart)
    //                         ->where('dateEnd', '>=', $dateEnd);
    //                 });
    //         });
    //     }
    // }


    public function scopeWithAdvisoryRangeDate($query, $filters)
    {
        $query->with([
            'officePnte',
            'region',
            'province',
            'district'
        ])->orderBy('dateStart', 'desc');

        // 🔍 Nombre
        if (!empty($filters['name'])) {
            $query->where('title', 'like', '%' . $filters['name'] . '%');
        }

        // 📅 Año (dateStart)
        if (!empty($filters['year'])) {
            $query->whereYear('dateStart', $filters['year']);
        }

        // 🏢 PNTE
        if (!empty($filters['pnte'])) {
            $query->where('id_pnte', $filters['pnte']);
        }

        // 📍 Ubicación
        if (!empty($filters['city'])) {
            $query->where('city_id', $filters['city']);
        }

        if (!empty($filters['province'])) {
            $query->where('province_id', $filters['province']);
        }

        if (!empty($filters['district'])) {
            $query->where('district_id', $filters['district']);
        }

        // 📅 Fecha exacta
        if (!empty($filters['date'])) {
            $query->whereDate('dateStart', $filters['date']);
        }

        // 📆 Rango de fechas
        if (!empty($filters['rangeDate']) && count($filters['rangeDate']) === 2) {
            $query->whereBetween('dateStart', [
                $filters['rangeDate'][0],
                $filters['rangeDate'][1]
            ]);
        }

        // 🧠 Modalidad (según link)
        if (!empty($filters['modalidad'])) {
            if ($filters['modalidad'] === 'presencial') {
                $query->whereNull('link');
            }

            if ($filters['modalidad'] === 'virtual') {
                $query->whereNotNull('link');
            }
        }
    }
}
