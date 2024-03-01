<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    
    protected $table = 'companies';

    protected $fillable = [
        'ruc',
        'social_reason',
        'category',
        'person_type',
        'department',
        'province',
        'district',
        'address',
        'email',
        'phone',
        'created_by',
        'update_by',
        'status'
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
