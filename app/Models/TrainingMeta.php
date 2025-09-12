<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingMeta extends Model
{
    use HasFactory;

    protected $table = 'trainingMetas';

    protected $fillable = [
        'month',
        'capacitaciones',
        'participantes',
        'empresas',
    ];
}
