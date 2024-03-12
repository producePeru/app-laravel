<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Componenttheme extends Model
{
    use HasFactory;

    protected $table = 'component_theme';

    protected $fillable = [
        'name',
        'status'
    ];

}
