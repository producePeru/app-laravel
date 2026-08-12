<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tienda extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tiendas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ruc',
        'envio_id',
        'celular',
        'correo',
        'image_id',
        'socials',
        'categoria',
    ];

    protected $casts = [
        'socials' => 'array',
    ];

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }
}
