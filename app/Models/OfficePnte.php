<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficePnte extends Model
{
    use HasFactory;

    protected $table = 'eventsoffice';

    protected $fillable = [
        'name',
        'office',
        'color',
        'avr'
    ];
}
