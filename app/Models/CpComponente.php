<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpComponente extends Model
{
    use HasFactory;

    protected $table = 'cp_componentes';

    protected $fillable = [
        'name'
    ];
}
