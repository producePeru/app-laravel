<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advisery extends Model
{
    use HasFactory;

    protected $table = 'adviseries';

    protected $fillable = [
        'id_person',
        'component',
        'tema_compoment',
        'modality',
        'department',
        'province',
        'district',
        'description',
        'created_by',
        'created_dni',
        'status'
    ];

    public function acreated()
    {
        return $this->belongsTo(People::class, 'created_by', 'id');
    }

    public function theme()
    {
        return $this->belongsTo(Componenttheme::class, 'tema_compoment', 'id');
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
    public function person()
    {
        return $this->belongsTo(People::class, 'id_person', 'id');
    }
    public function components()
    {
        return $this->belongsTo(Component::class, 'component', 'id');
    }
    public function supervisorx()
    {
        return $this->belongsTo(AdviserSupervisor::class, 'created_by', 'id_adviser');
    }
}
