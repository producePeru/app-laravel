<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresario extends Model
{
    use HasFactory;

    protected $fillable = [
        'typedocument_id',
        'dni',
        'name',
        'lastname',
        'middlename',
        'gender_id',
        'birthday',
        'phone',
    ];
}
