<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PntTest extends Model
{
    use HasFactory;

    protected $table = 'pnte_test';

    protected $fillable = [
        'test_entrada',
        'test_salida',
        'caso_practico',
        'slug',
    ];

    protected $casts = [
        'test_entrada' => 'array',
        'test_salida'  => 'array',
    ];
}
