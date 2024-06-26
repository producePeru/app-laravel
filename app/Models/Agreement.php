<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    use HasFactory;

    protected $table = 'agreements';

    protected $fillable = [
        'denomination', 'alliedEntity', 'homeOperations', 'address',
        'reference', 'resolution', 'initials', 'startDate','endDate',
        'city_id', 'province_id', 'district_id', 'operationalstatus_id', 'agreementstatus_id', 'createdBy'
    ];

    public function estadoOperatividad()
    {
        return $this->belongsTo(AgreementOperationalStatus::class, 'operationalstatus_id');
    }

    public function estadoConvenio()
    {
        return $this->belongsTo(AgreementStatus::class, 'agreementstatus_id');
    }

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

    public function acciones()
    {
        return $this->hasMany(AgreementActions::class, 'agreements_id');
    }

    public function archivosConvenios()
    {
        return $this->hasMany(AgreementFiles::class, 'id');
    }
}
