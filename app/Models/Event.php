<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'office',
        'title',
        'date',
        'allDay',
        'start',
        'end',
        'description',
        'nameUser',
        'user_id'
    ];

    // public function recurrences()
    // {
    //     return $this->hasMany(EventRecurrence::class);
    // }

    // public static function listAllEvents()
    // {
    //     $events = self::all();

    //     foreach ($events as $event) {
    //         if ($event->color == 'yellow') {
    //             $event->textColor = 'black';
    //         }
    //     }

    //     return $events;
    // }
}
