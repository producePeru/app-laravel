<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
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
}
