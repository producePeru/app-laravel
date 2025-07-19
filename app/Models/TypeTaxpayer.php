<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeTaxpayer extends Model
{
    use HasFactory;

    protected $table = 'typetaxpayers';

    protected $fillable = [
        'name'
    ];
}
