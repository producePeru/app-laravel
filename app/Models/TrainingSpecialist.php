<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSpecialist extends Model
{
    use HasFactory;

    protected $table = 'trainingSpecialists';

    protected $fillable = [
        'name',
        'ocupation',
        'color'
    ];
}
