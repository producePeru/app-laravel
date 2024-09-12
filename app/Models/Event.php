<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'nameEvent',
        'startDate',
        'endDate',
        'description',
        'linkVideo',
        'category_id',
        'repetir',
        'color',
        'user_id'
    ];

    public static function listAllEvents()
    {
        $events = self::all();

        foreach ($events as $event) {
            if ($event->color == 'yellow') {
                $event->textColor = 'black';
            }
        }

        return $events;
    }
}
