<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CyberwowBrand extends Model
{
    use HasFactory;

    protected $table = 'cyberwowbrand';

    protected $fillable = [
        'isService',
        'description',
        'url',
        'logo256_id',
        'logo160_id',
        'wow_id',
        'user_id',
        'company_id'
    ];
}
