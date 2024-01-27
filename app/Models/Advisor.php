<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advisor extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'number_document',
        'last_name',
        'middle_name',
        'name',
        'department',
        'province',
        'district',
        'email',
        'phone',
        'status',
        'id_supervisor',
        'id_sede',
        'created_by'
    ];

    public function departament()
    {
        return $this->belongsTo(Departament::class, 'department');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district');
    }
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'id_sede');
    }
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'id_supervisor');
    }
}
