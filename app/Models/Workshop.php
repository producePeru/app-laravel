<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    use HasFactory;

    protected $table = 'workshop';

    protected $fillable = [
        'workshopName',
        'date',
        'hour',
        'link',
        'description',
        'expositor',
        'status_te',
        'status_ts'
    ];

    public function scopeMapWorkshopItems($query, $filters)
    {
        if ($filters['workshopName']) {
            $query->where('workshopName', 'like', "%{$filters['workshopName']}%");
        }
    }
}
