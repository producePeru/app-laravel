<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPParticipant extends Model
{
    use HasFactory;

    protected $table = 'mp_participantes';

    protected $fillable = [
        'ruc',
        'social_reason',
        'economic_sector_id',
        'rubro_id',
        'comercial_activity_id',
        'city_id',
        'province_id',
        'district_id',
        't_doc_id',
        'doc_number',
        'country_id',
        'date_of_birth',
        'names',
        'last_name',
        'middle_name',
        'civil_status_id',
        'num_soons',
        'gender_id',
        'sick',
        'academicdegree_id',
        'phone',
        'email',
        'role_company_id',
        'obs_ruc',
        'obs_dni'
    ];

    public function rubro()
    {
        return $this->belongsTo(Category::class, 'rubro_id');
    }

    public function economicSector()
    {
        return $this->belongsTo(EconomicSector::class, 'economic_sector_id');
    }

    public function comercialActivity()
    {
        return $this->belongsTo(Activity::class, 'comercial_activity_id');
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

    public function typeDocument()
    {
        return $this->belongsTo(Typedocument::class, 't_doc_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function civilStatus()
    {
        return $this->belongsTo(CivilStatus::class, 'civil_status_id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    public function degree()
    {
        return $this->belongsTo(AcademicDegree::class, 'academicdegree_id');
    }

    public function roleCompany()
    {
        return $this->belongsTo(RoleCompany::class, 'role_company_id');
    }

    public function diagnosticoResponses()
    {
        return $this->hasMany(MPDiagnosticoResponse::class, 'participant_id');
    }
}
