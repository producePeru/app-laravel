<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etnia extends Model
{
    use HasFactory;

    protected $table = 'etnia';

    protected $fillable = [
        'name',
    ];
}
