<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventUgo extends Model
{
    use HasFactory;

    protected $table = 'eventsugopostulate';

    protected $fillable = [
        'id_mype',
        'id_businessman',
        'id_form',
        'comercialName',
        'sick',
        'phone',
        'email'
    ];
}
