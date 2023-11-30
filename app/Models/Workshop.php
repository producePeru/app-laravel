<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'exponent_id',
        'workshop_date',
        'type_intervention',
        'testin_id',
        'testout_id',
        'invitation_id',
        'status',
        'registered',
        'link',
        'rrss',
        'sms',
        'correo',
        'user_id'
    ];

    public function exponent()
    {
        return $this->belongsTo(Exponent::class, 'exponent_id');
    }
}
