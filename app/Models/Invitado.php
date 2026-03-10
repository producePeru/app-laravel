<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitado extends Model
{
    protected $table = 'invitados';

    protected $fillable = [
        'nombre',
        'telefono',
        'slug',
        'asistira',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ genera slug único automáticamente al crear
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($invitado) {
            $invitado->slug = static::generateUniqueSlug($invitado->nombre);
        });

        // ✅ si cambia el nombre, actualiza el slug
        static::updating(function ($invitado) {
            if ($invitado->isDirty('nombre')) {
                $invitado->slug = static::generateUniqueSlug($invitado->nombre, $invitado->id);
            }
        });
    }

    // ✅ genera slug único — si existe agrega sufijo numérico
    public static function generateUniqueSlug(string $nombre, ?int $ignoreId = null): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $i    = 1;

        while (
            static::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
