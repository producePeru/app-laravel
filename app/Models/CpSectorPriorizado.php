<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpSectorPriorizado extends Model
{
    use HasFactory;

    protected $table = 'cp_sector_priorizado';

    protected $fillable = [
        'name'
    ];
}
