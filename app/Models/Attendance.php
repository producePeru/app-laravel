<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'attendancelist';

    protected $fillable = [
        'eventsoffice_id',
        'title',
        'slug',
        'startDate',
        'endDate',
        'modality',
        'city_id',
        'province_id',
        'district_id',
        'address',
        'fecha',
        'hora',
        'user_id',
        // 'people_id',
        'asesorId',
        'description',
        'finally',

        // nuevos campos
        'theme',
        'entidad',
        'entidad_aliada',
        'pasaje',
        'monto',
        'beneficiarios',

        'totalAsesorias',
        'totalFormalizaciones'
    ];

    public function region()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function distrito()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'user_id', 'user_id');
    }

    public function attendanceList()
    {
        return $this->hasMany(AttendanceList::class, 'attendancelist_id');
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesorId');
    }

    public function pnte()
    {
        return $this->belongsTo(OfficePnte::class, 'eventsoffice_id');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('title', 'like', '%' . $search . '%');
        }
        return $query;
    }

    public function scopeWithItems($query, $filters)
    {
        $query = $query->with([
            'attendanceList',
            'region',
            'provincia',
            'distrito',
            'profile:id,user_id,name,lastname,middlename',
            'asesor',
            'pnte'
        ])
            ->withCount('attendanceList')
            ->withCount([
                'attendanceList as total_asesorias' => function ($q) {
                    $q->where('is_asesoria', 's');
                },
                'attendanceList as total_formalizaciones' => function ($q) {
                    $q->where('was_formalizado', 's');
                }
            ]);

        // if (!empty($filters['asesor'])) {
        //     $query->where('people_id', $filters['asesor']);
        // }

        if (!empty($filters['asesor'])) {
            $query->where('asesorId', $filters['asesor']);
        }


        if (!empty($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['name'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['name'] . '%')
                    ->orWhere('slug', 'like', '%' . $filters['name'] . '%');
            });
        }


        if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereBetween('startDate', [$filters['dateStart'], $filters['dateEnd']])
                    ->orWhereBetween('endDate', [$filters['dateStart'], $filters['dateEnd']]);
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

    // eventos asignados a un asesor

    public function scopeWithEvents($query, $filters)
    {
        $query = $query->with([
            'attendanceList',
            'region',
            'provincia',
            'distrito',
            'profile:id,user_id,name,lastname,middlename',
            'asesor',
            'pnte'
        ])->withCount('attendanceList');
    }

    public function getEstado()
    {
        $today = Carbon::today();

        // 4️⃣ FINALIZADOS (tiene registros)
        if ($this->attendance_list_count > 0) {
            return '4. FINALIZADOS';
        }

        // 3️⃣ PENDIENTE DE RESULTADOS (ya pasó endDate)
        if (Carbon::parse($this->endDate)->lt($today)) {
            return '3. PENDIENTE DE RESULTADOS';
        }

        // 1️⃣ PROGRAMACION DIARIA (creado hoy)
        if (Carbon::parse($this->created_at)->isToday()) {
            return '1. PROGRAMACION DIARIA';
        }

        // 2️⃣ PROGRAMACION CONSOLIDADA (todo lo demás)
        return '2. PROGRAMACION CONSOLIDADA';
    }
}
