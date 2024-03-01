<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFormalization extends Model
{
    use HasFactory;

    protected $table = 'form_formalization';

    protected $fillable = [
        'dni',
        'name_lastname',
        'email',
        'phone',
        'departament',
        'province',
        'district',
        'address',
        'id_gps_cdes',
        'count'
    ];
}
