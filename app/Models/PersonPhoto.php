<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonPhoto extends Model
{
    use HasFactory;

    protected $table = 'person_photo';

    protected $fillable = [
        'nombre',
        'ruta',
        'id_person',
        'dni'
    ];

}
