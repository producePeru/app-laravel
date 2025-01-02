<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DgtifSurveys extends Model
{
    use HasFactory;

    protected $table = 'dgtifsurveys';

    protected $fillable = [
        'question1',
        'question2',
        'question3',
        'question4',
        'total',
        'status'
    ];

}
