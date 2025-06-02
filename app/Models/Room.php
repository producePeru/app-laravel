<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'sala',
        'inicio',
        'fin',
        'descripcion',
        'unidad',
        'created_by',
        'updated_by'
    ];

    protected $dates = ['inicio', 'fin', 'deleted_at'];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'created_by');
    }
}
