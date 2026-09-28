<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadPntePapa extends Model
{
    use HasFactory;

    protected $table = 'actividad_pnte_papa';

    protected $fillable = [
        'actividad_id',
        'empresario_id',
        'prioridad_1',
        'prioridad_2',
        'id_image_1',
        'id_image_2',
    ];

    public function actividad()
    {
        return $this->belongsTo(ActividadPnte::class);
    }

    public function empresario()
    {
        return $this->belongsTo(Empresario::class);
    }

    public function image1()
    {
        return $this->belongsTo(Image::class, 'id_image_1');
    }

    public function image2()
    {
        return $this->belongsTo(Image::class, 'id_image_2');
    }
}
