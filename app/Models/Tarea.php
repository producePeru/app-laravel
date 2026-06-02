<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    protected $fillable = [
        'titulo',
        'unidad',
        'detalle',
        'completada',
        'orden',
    ];

    protected $casts = [
        'completada' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivas($query)
    {
        return $query->where('completada', false)->orderBy('orden');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('completada', true)->latest('updated_at');
    }
}
