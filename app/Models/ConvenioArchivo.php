<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvenioArchivo extends Model
{
    use HasFactory;

    protected $table = 'convenios_archivos';

    protected $fillable = [
        'convenio_id',
        'nombre_original',
        'ruta',
        'mime',
        'tamano',
    ];

    protected $casts = [
        'tamano' => 'integer',
    ];

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }
}
