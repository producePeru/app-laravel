<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpTema extends Model
{
    use HasFactory;

    protected $table = 'cp_temas';

    protected $fillable = [
        'name',
        'component_id'
    ];
}
