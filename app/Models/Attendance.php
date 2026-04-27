<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

        'dates',
        'hora',
        'user_id',
        'updated_by',
        'asesorId',
        'description',
        'finally',

        // 'people_id',
        // nuevos campos
        'theme',
        'entidad',
        'entidad_aliada',
        'pasaje',
        'monto',
        'beneficiarios',
        'team',

        'totalAsesorias',
        'totalFormalizaciones',
    ];

    protected $casts = [
        'dates' => 'array', // Laravel hace JSON parse/stringify automático
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

    public function registrador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pnte()
    {
        return $this->belongsTo(OfficePnte::class, 'eventsoffice_id');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('title', 'like', '%'.$search.'%');
        }

        return $query;
    }

    public function scopeWithItems($query, $filters)
    {
        $query->with([
            'attendanceList',
            'region',
            'provincia',
            'distrito',
            'profile:id,user_id,name,lastname,middlename',
            'asesor',
            'pnte',
            'registrador',
        ])
            ->withCount('attendanceList')
            ->withCount([
                'attendanceList as total_asesorias' => function ($q) {
                    $q->where('is_asesoria', 's');
                },
                'attendanceList as total_formalizaciones' => function ($q) {
                    $q->where('was_formalizado', 's');
                },
            ]);

        // 🔍 NOMBRE (title)
        if (! empty($filters['name'])) {
            $query->where('theme', 'like', '%'.$filters['name'].'%');
        }

        // 👤 ASESOR
        if (! empty($filters['asesor'])) {
            $query->where('asesorId', $filters['asesor']);
        }

        // 🏢 MODALIDAD
        if (! empty($filters['modalidad'])) {
            $modalidad = $filters['modalidad'] === 'presencial' ? 'p' : 'v';
            $query->where('modality', $modalidad);
        }

        // 📅 AÑO (startDate)
        if (! empty($filters['year'])) {
            $query->whereYear('startDate', $filters['year']);
        }

        // 📅 FECHA EXACTA (created_at)
        if (! empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        // 📅 RANGO (startDate)
        if (! empty($filters['rangeDate']) && count($filters['rangeDate']) === 2) {
            $query->whereBetween('startDate', [
                $filters['rangeDate'][0],
                $filters['rangeDate'][1],
            ]);
        }

        // 📅 RANGO (date)
        if (! empty($filters['dateStart']) && ! empty($filters['dateEnd'])) {

            $start = Carbon::createFromFormat('Y/m/d', $filters['dateStart'])->format('Y-m-d');
            $end = Carbon::createFromFormat('Y/m/d', $filters['dateEnd'])->format('Y-m-d');

            $query->where(function ($q) use ($start, $end) {
                $q->whereDate('startDate', '<=', $end)
                    ->whereDate('endDate', '>=', $start);
            });
        }

        // 🌎 UBICACIÓN
        if (! empty($filters['city'])) {
            $query->where('city_id', $filters['city']);
        }

        if (! empty($filters['province'])) {
            $query->where('province_id', $filters['province']);
        }

        if (! empty($filters['district'])) {
            $query->where('district_id', $filters['district']);
        }

        // 🚦 STATUS (LO MÁS IMPORTANTE 🔥)
        if (! empty($filters['status'])) {

            $today = Carbon::today();

            switch ($filters['status']) {

                case '1. PROGRAMACION DIARIA':
                    $query->whereDate('created_at', $today);
                    break;

                case '2. PROGRAMACION CONSOLIDADA':
                    $query->whereDate('created_at', '<', $today)
                        ->whereDate('endDate', '>=', $today)
                        ->having('attendance_list_count', 0);
                    break;

                case '3. PENDIENTE DE RESULTADOS':
                    $query->whereDate('endDate', '<', $today)
                        ->having('attendance_list_count', 0);
                    break;

                case '4. FINALIZADOS':
                    $query->having('attendance_list_count', '>', 0);
                    break;
            }
        }

        // 🔽 ORDEN
        if (! empty($filters['orderby'])) {
            switch ($filters['orderby']) {
                case 1:
                    $query->orderBy('attendance_list_count', 'desc');
                    break;
                case 2:
                    $query->orderBy('attendance_list_count', 'asc');
                    break;
                case 3:
                    $query->orderBy('finally', 'desc');
                    break;
            }
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
            'pnte',
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
