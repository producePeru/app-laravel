<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mype extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruc',
        'social_reason',
        'category',
        'type',
        'department',
        'district',
        'name_complete',
        'dni_number',
        'sex',
        'phone',
        'email',

        'registration_date',

        'added'
    ];
}
