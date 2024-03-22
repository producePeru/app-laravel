<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormalizationDigital extends Model
{
    use HasFactory;

    protected $table = 'formalization_digital';

    protected $fillable = [
        // 'id_person',
        'dni_person',
        'id_gps',
        'status',
        'booking',
        'is_delete',
        'count'
    ];

    public function cdes()
    {
        return $this->hasOne(Gpscde::class, 'id', 'id_gps');
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'dni_person', 'number_document',);
    }
    public function digital()
    {
        return $this->hasOne(FormalizationDigital::class, 'id_gps', 'id_gps');
    }
}