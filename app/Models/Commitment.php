<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'created',
        'idAgreement',
        'entity',
        'isMeta',
        'unitMeasurement',
        'metaNumb',
        'description',
        'status'
    ];
}
