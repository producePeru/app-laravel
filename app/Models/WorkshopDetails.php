<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'te1',
        'te2',
        'te3',
        'te4',
        'te5',
        'te_note',
        'ts1',
        'ts2',
        'ts3',
        'ts4',
        'ts5',
        'ts_note',
        'average_final',
        'c1',
        'c2',
        'c3',
        'average_satisfaction',
        'suggestions',
        // 'email',
        
        'ruc_mype',
        'dni_mype',
        'workshop_id'
    ];

    public function mype()
    {
        return $this->belongsTo(Mype::class, 'ruc_mype');
    }
}
