<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsesoriaCooperativa extends Model
{
    use HasFactory;

    protected $table = "advisories_cooperativa";

    protected $fillable = [
        'advisory_id',
        'ruc',
        'nombre'
    ];

    public function advisory()
    {
        return $this->belongsTo(Advisory::class, 'advisory_id', 'id');
    }
}
