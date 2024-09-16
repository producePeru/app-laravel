<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRecurrence extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'recurrence_start', 'recurrence_end'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

}
