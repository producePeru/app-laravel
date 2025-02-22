<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_pnte',
        'title',
        'organiza',
        'gps',
        'numMypes',
        // 'date',
        'dateStart',
        'dateEnd',
        'start',
        'end',
        'description',
        'nameUser',
        'link',
        'user_id',
        'resultado'
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
