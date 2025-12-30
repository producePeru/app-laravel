<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CdeType extends Model
{
    use HasFactory;

    protected $table = 'cdesType';

    protected $fillable = ['name'];
}
