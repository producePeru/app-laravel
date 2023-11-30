<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testin extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_end',
        'question1',
        'question1_opt1',
        'question1_opt2',
        'question1_opt3',
        'question1_resp',
        'question2',
        'question2_opt1',
        'question2_opt2',
        'question2_opt3',
        'question2_resp',
        'question3',
        'question3_opt1',
        'question3_opt2',
        'question3_opt3',
        'question3_resp',
        'question4',
        'question4_opt1',
        'question4_opt2',
        'question4_opt3',
        'question4_resp',
        'question5',
        'question5_opt1',
        'question5_opt2',
        'question5_opt3',
        'question5_resp',

        'workshop_id',
        'user_id'
    ];
}
