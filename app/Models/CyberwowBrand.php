<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CyberwowBrand extends Model
{
    use HasFactory;

    protected $table = 'cyberwowbrand';

    protected $fillable = [
        'isService',
        'red',
        'description',
        'url',
        'logo256_id',
        'logo160_id',
        'wow_id',
        'user_id',
        'company_id'
    ];

    // 🔗 Relación con la tabla de imágenes (Image)
    public function logo256()
    {
        return $this->belongsTo(Image::class, 'logo256_id');
    }

    public function logo160()
    {
        return $this->belongsTo(Image::class, 'logo160_id');
    }

    // (Opcional) Si tienes relación con participante o usuario
    public function participant()
    {
        return $this->belongsTo(CyberwowParticipant::class, 'company_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
