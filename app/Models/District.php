<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $table = 'distrito';

    protected $primaryKey = 'idDistrito';

    protected $fillable = [
        'idDistrito',
        'descripcion',
        'idUsuario',
        'idProvincia',
        'estado'
    ];

    public function department()
    {
        return $this->hasMany(Province::class, 'idDistrito');
    }
}
