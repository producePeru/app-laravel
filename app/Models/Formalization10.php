<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formalization10 extends Model
{
    use HasFactory;

    protected $table = 'formalizations_10';

    protected $fillable = [
        'id_person',
        'detail_procedure',
        'modality',
        'economy_sector',
        'category',
        'department',
        'province',
        'district',
        'created_by',
        'created_dni',
        'status'
    ];

    public function acreated()
    {
        return $this->belongsTo(People::class, 'created_by', 'id');
    }

    public function categories()
    {
        return $this->belongsTo(ComercialActivity::class, 'category', 'id');
    }
    // public function supervisor()
    // {
    //     return $this->belongsTo(AdviserSupervisor::class, 'created_by', 'created_by');
    // }
    public function departmentx()
    {
        return $this->belongsTo(Departament::class, 'department', 'idDepartamento');
    }
    public function provincex()
    {
        return $this->belongsTo(Province::class, 'province', 'idProvincia');
    }
    public function districtx()
    {
        return $this->belongsTo(District::class, 'district', 'idDistrito');
    }
    public function prodecuredetail()
    {
        return $this->belongsTo(ProdecureDetail::class, 'detail_procedure', 'id');
    }
    public function economicsectors()
    {
        return $this->belongsTo(EconomicSectors::class, 'economy_sector', 'id');
    }
    public function supervisorx()
    {
        return $this->belongsTo(AdviserSupervisor::class, 'created_by', 'id_adviser');
    }
}
