<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Fair extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'subTitle',
        'description',
        'fairtype_id',
        'modality_id',
        'startDate',
        'endDate',
        'metaMypes',
        'city_id',
        'fecha',
        'place',
        'hours',
        'msgEndForm',
        'msgSendEmail',
        'created_by',
        'updated_by',
        'image_id'
    ];


    protected $dates = ['startDate', 'endDate', 'deleted_at'];

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

    public function fairPostulate()
    {
        return $this->hasMany(FairPostulate::class, 'fair_id');
    }

    public function fairType()
    {
        return $this->belongsTo(FairType::class, 'fairtype_id');
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class, 'modality_id');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function postulantes()
    {
        return $this->hasMany(UgsePostulante::class, 'event_id');
    }

    public function postulantesWow()
    {
        return $this->hasMany(CyberwowParticipant::class, 'event_id');
    }


    // SCOPE SEARCH
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('title', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('typeFair', 'like', '%' . $search . '%')
                ->orWhereHas('region', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%'); // Buscar por nombre de la ciudad
                })
                ->orWhereHas('provincia', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%'); // Buscar por nombre de la provincia
                })
                ->orWhereHas('profile', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%'); // Buscar por nombre del perfil
                });
        }
        return $query;
    }

    public function scopeWithItems($query, $filters)
    {
        $query->with([
            'modality',
            'region',
            'fairType',
            'image',
        ])->withCount(['postulantes', 'postulantesWow']);


        if (!empty($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nameEvent', 'like', '%' . $filters['name'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['name'] . '%');
            });
        }


        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereBetween('startDate', [$filters['startDate'], $filters['endDate']])
                    ->orWhereBetween('endDate', [$filters['startDate'], $filters['endDate']]);
            });
        }

        if (!empty($filters['year'])) {

            $query->whereYear('created_at', $filters['year']);
        }

        $today = Carbon::today();

        if (!empty($filters['orderby'])) {
            switch ($filters['orderby']) {
                case 1:
                    // Más recientes (por creación)
                    // $query->orderBy('created_at', 'desc');
                    $query->whereDate('endDate', '>=', $today)
                        ->orderBy('endDate', 'asc');
                    break;
                case 2:
                    // Vigentes (endDate >= hoy)
                    $query->whereDate('endDate', '<', $today)
                        ->orderBy('endDate', 'desc');
                    break;
                case 3:
                    // Finalizados (ya terminaron)
                    $query->orderBy('created_at', 'desc');
                    break;
                case 4:
                    // Orden por fecha de finalización, descendente
                    $query->orderBy('endDate', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            // Por defecto: más recientes
            $query->orderBy('created_at', 'desc');
        }
    }
}
