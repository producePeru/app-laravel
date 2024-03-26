<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Calculation\Category;

class Formalization20 extends Model
{
    use HasFactory;

    protected $table = 'formalizations_20';

    protected $fillable = [
        'step',
        'id_person',
        'dni',
        'code_sid_sunarp',

        'economy_sector',
        'department',
        'category',
        'province',
        'district',
        'address',
        'created_by',
        'created_dni',

        'social_reason',
        'type_regimen',
        'num_notary',
        'modality',
        'id_notary',
        
        
        'ruc',
        'updated_by',
        'status'
    ];

    public function acreated()
    {
        return $this->belongsTo(People::class, 'created_by', 'id');
    }

    public function aupdated()
    {
        return $this->belongsTo(People::class, 'updated_by', 'id');
    }

    public function categories()
    {
        return $this->belongsTo(ComercialActivity::class, 'category', 'id');
    }
    public function supervisorx()
    {
        return $this->belongsTo(AdviserSupervisor::class, 'created_by', 'id_adviser');
    }
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
    public function notary()
    {
        return $this->belongsTo(Notary::class, 'id_notary', 'id');
    }
    public function solicitante()
    {
        return $this->belongsTo(People::class, 'id_person', 'id');
    }
}




    
    