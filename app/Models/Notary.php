<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notary extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department',
        'province',
        'district',
        'address',
        'normal_rate',
        'social_rate',
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
