<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EconomicSectors extends Model
{
    use HasFactory;

    protected $table = 'economic_sectors';

    protected $fillable = [
        'name'
    ];
}
