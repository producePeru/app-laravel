<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory;

    protected $table = 'questions_options';

    protected $fillable = [
        'question_id',
        'label',
        'value',
        'status',
        'position'
    ];
}
