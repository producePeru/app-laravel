<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MPAdviceDate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mp_advice_dates';

    protected $fillable = [
        'mp_personalized_advice_id',
        'mype_id',
        'date',
        'startTime',
        'endTime'
    ];

    protected $casts = [
        'date' => 'date',
        // 'startTime' => 'datetime:H:i',
        // 'endTime' => 'datetime:H:i',
    ];

    // 🔹 RELACIÓN CON PARTICIPANTE
    public function participant()
    {
        return $this->belongsTo(MPParticipant::class, 'mype_id');
    }
}
