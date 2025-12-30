<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPDiagnosticoResponse extends Model
{
    use HasFactory;

    protected $table = 'mp_diag_respuestas';

    protected $fillable = [
        'participant_id',
        'question_id',
        'answer_option_id',
        'answer_text'
    ];

    public function participant()
    {
        return $this->belongsTo(MPParticipant::class, 'participant_id');
    }

    public function question()
    {
        return $this->belongsTo(MPDiagnostico::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(MPDiagnosticoOption::class, 'answer_option_id');
    }
}
