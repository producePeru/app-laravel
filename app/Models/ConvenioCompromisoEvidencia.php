<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvenioCompromisoEvidencia extends Model
{
    use HasFactory;

    protected $table = 'convenios_compromisos_evidencias';

    protected $fillable = [
        'compromiso_id',
        'avance_id',
        'user_id',
        'nombre_original',
        'ruta',
        'mime',
        'tamano',
    ];

    protected $casts = [
        'tamano' => 'integer',
    ];

    public function compromiso(): BelongsTo
    {
        return $this->belongsTo(ConvenioCompromiso::class, 'compromiso_id');
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(ConvenioCompromisoAvance::class, 'avance_id');
    }

    /**
     * Usuario que subió el archivo.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
