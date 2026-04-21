<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sedQuestionAnswer extends Model
{
    use HasFactory;

    protected $table = 'sed_questions_answers';

    protected $fillable = [
        'dni',
        'ruc',
        'sed_id',
        'question',
        'answer',
        'order'
    ];
}
