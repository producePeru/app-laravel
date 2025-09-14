<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingDimension extends Model
{
    use HasFactory;

    protected $table = 'trainingDimensions';

    protected $fillable = [
        'name',
    ];
}
