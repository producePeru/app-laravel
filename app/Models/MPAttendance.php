<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPAttendance extends Model
{
    use HasFactory;

    protected $table = 'mp_asistencias';

    protected $fillable = [
        'event_id',
        'participant_id',
        'attendance'
    ];

    public function event()
    {
        return $this->belongsTo(MPEvent::class, 'event_id');
    }

    public function participant()
    {
        return $this->belongsTo(MPParticipant::class, 'participant_id');
    }
}
