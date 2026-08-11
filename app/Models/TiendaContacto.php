<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiendaContacto extends Model
{
    use HasFactory;

    protected $table = 'tiendas_contactos';

    protected $fillable = [
        'nombre',
        'celular',
        'correo',
        'productos',
        'id_empresa',
    ];

    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class, 'id_empresa');
    }
}
