<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by', 
        'id_user', 
        'views', 
        'exclusions'
    ];

    protected $casts = [
        'views' => 'json', // Indica que el campo 'views' debe ser tratado como JSON
    ];
}
