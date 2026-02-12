<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class MPAdvice extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'mp_personalized_advice';

    protected $fillable = [
        'title',
        'description',
        'requirements',
        'capacitador_id',
        'user_id',
        'image_id',
        'date',
        'hourStart',
        'hourEnd',
        'link',
        'mype_id'
    ];

    public function capacitador()
    {
        return $this->belongsTo(MPCapacitador::class, 'capacitador_id');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }
}
