<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceList extends Model
{
    use HasFactory;

    protected $table = 'ugo_postulantes';

    protected $fillable = [
        'ruc',
        'comercialName',
        'socialReason',
        'economicsector_id',
        'comercialactivity_id',
        'category_id',

        'howKnowEvent_id',
        'slug',

        'typedocument_id',
        'documentnumber',
        'name',
        'lastname',
        'middlename',
        'gender_id',
        'sick',
        'email',
        'phone',

        'mercado',
        'fechaRegistro',

        'comercialActivity',
        'attendancelist_id',
        'mercado',
        'fecha',

        'country_id',
        'city_id',
        'province_id',
        'district_id',

        'is_asesoria',
        'was_formalizado'
    ];

    public function rubro()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function dictrict()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function typedocument()
    {
        return $this->belongsTo('App\Models\Typedocument');
    }
    public function gender()
    {
        return $this->belongsTo('App\Models\Gender');
    }
    public function economicsector()
    {
        return $this->belongsTo('App\Models\EconomicSector');
    }
    // public function comercialactivity()
    // {
    //     return $this->belongsTo('App\Models\ComercialActivities');
    // }
    public function list()
    {
        return $this->belongsTo(Attendance::class, 'attendancelist_id');
    }

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


    public function scopeSearchApplicants($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(name, ' ', lastname, ' ', middlename) LIKE ?", ['%' . $search . '%'])
                    ->orWhere('documentnumber', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('ruc', 'like', '%' . $search . '%')
                    ->orWhere('socialReason', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }
}
