<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SedQuestion extends Model
{
    use HasFactory;

    protected $table = 'sedquestions';

    protected $fillable = [
        'question_1',
        'question_2',
        'question_3',
        'question_4',
        'question_5',
        'documentnumber',
        'event_id'
    ];

    public function ugsePostulante()
    {
        return $this->belongsTo(UgsePostulante::class, 'documentnumber', 'documentnumber')
            ->whereColumn('event_id', 'event_id');
    }
}
