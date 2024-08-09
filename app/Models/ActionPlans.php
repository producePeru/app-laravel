<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionPlans extends Model
{
    use HasFactory;

    protected $fillable = [
        'component1_id',
        'component1_name',
        'component2_id',
        'component2_name',
        'component3_id',
        'component3_name',
        'numberSession',
        'startDay',
        'endDay',
        'totalDays',
        'actaCompromiso',
        'envioCorreo'
    ];
}
