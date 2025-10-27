<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CyberwowOffer extends Model
{
    use HasFactory;

    protected $table = 'cyberwowoffers';

    protected $fillable = [
        'wow_id',
        'company_id',
        'imgFull',
        'img',
        'title',
        'link',
        'category',
        'tipo',
        'beneficio',
        'moneda',
        'precioAnterior',
        'precioOferta',
        'descripcion',
        'dia'
    ];

    public function fair()
    {
        return $this->belongsTo(Fair::class, 'wow_id');
    }

    public function company()
    {
        return $this->belongsTo(CyberwowParticipant::class, 'company_id');
    }

    public function imageFull()
    {
        return $this->belongsTo(Image::class, 'imgFull');
    }

    public function imagePhone()
    {
        return $this->belongsTo(Image::class, 'img');
    }

    public function brand()
    {
        return $this->belongsTo(CyberwowBrand::class, 'company_id', 'company_id');
    }
}
