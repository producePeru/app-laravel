<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agreement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'agreements';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'alliedEntity',
        'homeOperations',
        'startDate',
        'endDate',
        'city_id',
        'province_id',
        'district_id',
        'years',
        'observations',
        'ruc',
        'components',
        'aliado',
        'aliadoPhone',
        'focal',
        'focalCargo',
        'focalPhone',
        'renovation',
        'entity',
        'created_id',
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

    // Modelo Agreement
    public function commitments()
    {
        return $this->hasMany(AgreementCommitments::class, 'agreement_id');
    }

    public function filesAgreements()
    {
        return $this->hasMany(AgreementFiles::class, 'agreements_id');
    }

    // Nuevo
    public function compromisos()
    {
        return $this->hasMany(Commitment::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'created_id', 'user_id');
    }



    // SCOPE SEARCH
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('alliedEntity', 'like', "%{$search}%")
                ->orWhereHas('region', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('provincia', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            });
        }
    }

}
