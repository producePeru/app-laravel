<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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


        'social_reason',
        'type_regimen',
        'num_notary',
        'modality',
        'id_notary',
        
        
        'ruc',
        'updated_by',
        'status'
    ];
}
