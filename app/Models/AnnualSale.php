<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualSale extends Model
{
    use HasFactory;

    protected $table = 'annualsales';

    protected $fillable = [
        'name'
    ];
}
