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
        'city_id',
        'province_id',
        'district_id',
        'alliedEntity',
        'homeOperations',
        'startDate',
        'years',
        'endDate',
        'observations',
        'created_id'
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
