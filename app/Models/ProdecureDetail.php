<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdecureDetail extends Model
{
    use HasFactory;

    protected $table = 'prodecure_detail';

    protected $fillable = [
        'name'
    ];
}
