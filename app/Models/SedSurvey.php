<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SedSurvey extends Model
{
    use HasFactory;

    protected $table = 'sedsurvey';

    protected $fillable = [
        'sed_id',
        'title',
        'question_id'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
