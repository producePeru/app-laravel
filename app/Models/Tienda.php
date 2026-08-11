<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'socials' => 'array',
    ];

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }
}
