<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CdePnte extends Model
{
    use HasFactory;

    protected $table = 'cde_pnte';

    protected $fillable = [
        'nombre',
        'region_id',
        'provincia_id',
        'distrito_id',
        'direccion',
    ];

    public function region()
    {
        return $this->belongsTo(City::class, 'region_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Province::class, 'provincia_id');
    }

    public function distrito()
    {
        return $this->belongsTo(District::class, 'distrito_id');
    }
}
