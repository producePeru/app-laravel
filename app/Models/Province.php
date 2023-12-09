<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $table = 'provincia';

    protected $primaryKey = 'idProvincia';

    protected $fillable = [
        'idProvincia',
        'descripcion',
        'idUsuario',
        'idDepartamento',
        'estado'
    ];

    public function department()
    {
        return $this->hasMany(Province::class, 'idProvincia');
    }
}   
